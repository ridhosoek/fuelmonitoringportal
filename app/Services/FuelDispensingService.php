<?php

namespace App\Services;

use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\FuelOrderItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FuelDispensingService
{
    public function __construct(
        private readonly NumberGenerator $numberGenerator,
        private readonly FuelOrderService $fuelOrderService,
        private readonly StockService $stockService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, User $user): FuelDispensing
    {
        return DB::transaction(function () use ($data, $user): FuelDispensing {
            $unit = Unit::query()->findOrFail($data['unit_id']);
            $orderItem = FuelOrderItem::query()->findOrFail($data['fuel_order_item_id']);
            $data['meter_type'] = $unit->meter_type;
            $data['delivery_method'] = $orderItem->delivery_method;

            if ($data['delivery_method'] === FuelOrderItem::DELIVERY_DIRECT_STORAGE) {
                $data['fuel_truck_id'] = null;
            }
            $data['previous_meter_value'] ??= $this->lastMeterValue($unit);
            $data['production_unit'] = filled($data['production_quantity'] ?? null)
                ? $unit->production_unit
                : null;

            $dispensing = FuelDispensing::create([
                ...$data,
                'dispensing_number' => $data['dispensing_number'] ?? $this->numberGenerator->next('DSP', FuelDispensing::class, 'dispensing_number', $data['dispensing_date'] ?? null),
                'status' => $data['status'] ?? FuelDispensing::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            $this->auditLogger->log('CREATE', 'Fuel Dispensing', $dispensing, null, $dispensing->toArray(), $user, $dispensing->dispensing_number);

            return $dispensing->fresh(['fuelOrder', 'fuelOrderItem.unit', 'storage.terminal', 'unit', 'fuelTruck']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAndPost(array $data, User $user): FuelDispensing
    {
        return DB::transaction(function () use ($data, $user): FuelDispensing {
            $dispensing = $this->createDraft($data, $user);

            return $this->post($dispensing, $user);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, FuelDispensing>
     */
    public function createBatch(array $data, User $user, bool $postImmediately = true): Collection
    {
        return DB::transaction(function () use ($data, $user, $postImmediately): Collection {
            $submittedItems = collect($data['items'] ?? [])
                ->filter(fn (array $item): bool => filled($item['quantity_liter'] ?? null))
                ->values();

            if ($submittedItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Isi Qty Real minimal pada satu unit yang akan diproses.',
                ]);
            }

            $fuelOrder = FuelOrder::query()
                ->activeForDispensing()
                ->lockForUpdate()
                ->find($data['fuel_order_id']);

            if ($fuelOrder === null) {
                throw ValidationException::withMessages([
                    'fuel_order_id' => 'Fuel Order harus berstatus OPEN atau PARTIAL.',
                ]);
            }

            $orderItems = FuelOrderItem::query()
                ->with('unit')
                ->whereBelongsTo($fuelOrder)
                ->where('status', 'ACTIVE')
                ->whereIn('id', $submittedItems->pluck('fuel_order_item_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($orderItems->count() !== $submittedItems->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Terdapat item yang tidak aktif atau tidak termasuk dalam Fuel Order yang dipilih.',
                ]);
            }

            unset($data['items']);

            return $submittedItems->map(function (array $submittedItem) use ($data, $fuelOrder, $orderItems, $postImmediately, $user): FuelDispensing {
                $orderItem = $orderItems->get((int) $submittedItem['fuel_order_item_id']);
                $dispensingData = [
                    ...$data,
                    'fuel_order_id' => $fuelOrder->id,
                    'fuel_order_item_id' => $orderItem->id,
                    'unit_id' => $orderItem->unit_id,
                    'delivery_method' => $orderItem->delivery_method,
                    'fuel_truck_id' => $orderItem->delivery_method === FuelOrderItem::DELIVERY_MOBILE_TANKER
                        ? ($submittedItem['fuel_truck_id'] ?? null)
                        : null,
                    'previous_meter_value' => $submittedItem['previous_meter_value'] ?? null,
                    'meter_value' => $submittedItem['meter_value'] ?? null,
                    'quantity_liter' => $submittedItem['quantity_liter'],
                    'production_quantity' => $submittedItem['production_quantity'] ?? null,
                ];

                return $postImmediately
                    ? $this->createAndPost($dispensingData, $user)
                    : $this->createDraft($dispensingData, $user);
            });
        });
    }

    public function post(FuelDispensing $dispensing, User $user): FuelDispensing
    {
        return DB::transaction(function () use ($dispensing, $user): FuelDispensing {
            $dispensing = FuelDispensing::query()
                ->with(['fuelOrderItem', 'fuelOrder', 'storage', 'unit', 'fuelTruck'])
                ->lockForUpdate()
                ->findOrFail($dispensing->id);

            if ($dispensing->status !== FuelDispensing::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya dispensing DRAFT yang dapat diposting.',
                ]);
            }

            $this->validatePosting($dispensing, $user);

            $oldValues = $dispensing->toArray();
            $usesOverride = filled($dispensing->override_reason);
            $dispensing->update([
                'status' => FuelDispensing::STATUS_POSTED,
                'posted_by' => $user->id,
                'posted_at' => now(),
                'approved_by' => $usesOverride ? $user->id : null,
                'approved_at' => $usesOverride ? now() : null,
            ]);

            $this->stockService->recordMovement(
                $dispensing->storage_id,
                StockMovement::TYPE_DISPENSING,
                'fuel_dispensing',
                $dispensing->id,
                $dispensing->dispensing_number,
                -1 * (float) $dispensing->quantity_liter,
                $user,
                $dispensing->dispensing_date->toDateString(),
                $dispensing->dispensing_time,
                'Posted fuel dispensing',
            );

            $this->fuelOrderService->refreshStatus($dispensing->fuelOrder);
            $this->auditLogger->log('POST', 'Fuel Dispensing', $dispensing, $oldValues, $dispensing->fresh()->toArray(), $user, $dispensing->dispensing_number);

            return $dispensing->fresh(['fuelOrder', 'fuelOrderItem.unit', 'storage.terminal', 'unit', 'fuelTruck']);
        });
    }

    public function reverse(FuelDispensing $dispensing, User $user, string $reason): FuelDispensing
    {
        return DB::transaction(function () use ($dispensing, $user, $reason): FuelDispensing {
            $dispensing = FuelDispensing::query()
                ->with('fuelOrder')
                ->lockForUpdate()
                ->findOrFail($dispensing->id);

            if ($dispensing->status !== FuelDispensing::STATUS_POSTED) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya dispensing POSTED yang dapat direverse.',
                ]);
            }

            if ($dispensing->bastItem()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Dispensing yang sudah masuk BAST tidak dapat direverse sebelum BAST dikendalikan.',
                ]);
            }

            $oldValues = $dispensing->toArray();

            $this->stockService->reverseReference('fuel_dispensing', $dispensing->id, $user, $reason);

            $dispensing->update([
                'status' => FuelDispensing::STATUS_REVERSED,
                'reversed_by' => $user->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            $this->fuelOrderService->refreshStatus($dispensing->fuelOrder);
            $this->auditLogger->log('REVERSE', 'Fuel Dispensing', $dispensing, $oldValues, $dispensing->fresh()->toArray(), $user, $dispensing->dispensing_number);

            return $dispensing->fresh(['fuelOrder', 'fuelOrderItem.unit', 'storage', 'unit']);
        });
    }

    public function lastMeterValue(Unit $unit): ?float
    {
        $value = FuelDispensing::query()
            ->where('unit_id', $unit->id)
            ->where('status', FuelDispensing::STATUS_POSTED)
            ->latest('dispensing_date')
            ->latest('id')
            ->value('meter_value');

        return $value === null ? null : (float) $value;
    }

    private function validatePosting(FuelDispensing $dispensing, User $user): void
    {
        if ($dispensing->unit->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['unit_id' => 'Unit harus aktif.']);
        }

        if ($dispensing->storage->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['storage_id' => 'VHS harus aktif.']);
        }

        if (! in_array($dispensing->fuelOrder->status, [FuelOrder::STATUS_OPEN, FuelOrder::STATUS_PARTIAL], true)) {
            throw ValidationException::withMessages(['fuel_order_id' => 'FO harus OPEN atau PARTIAL untuk dispensing.']);
        }

        if ((int) $dispensing->fuelOrderItem->fuel_order_id !== (int) $dispensing->fuel_order_id) {
            throw ValidationException::withMessages(['fuel_order_item_id' => 'Detail FO tidak sesuai dengan header FO.']);
        }

        if ((int) $dispensing->fuelOrderItem->unit_id !== (int) $dispensing->unit_id) {
            throw ValidationException::withMessages(['unit_id' => 'Unit harus sesuai dengan detail FO yang dipilih.']);
        }

        if ($dispensing->delivery_method !== $dispensing->fuelOrderItem->delivery_method) {
            throw ValidationException::withMessages(['delivery_method' => 'Metode dispensing harus sesuai dengan permintaan Fuel Order.']);
        }

        if ($dispensing->delivery_method === FuelOrderItem::DELIVERY_DIRECT_STORAGE && $dispensing->unit->mobility_type !== Unit::MOBILITY_MOVABLE) {
            throw ValidationException::withMessages(['delivery_method' => 'Unit tidak bergerak harus dilayani menggunakan mobil tangki.']);
        }

        if ($dispensing->delivery_method === FuelOrderItem::DELIVERY_MOBILE_TANKER) {
            if ($dispensing->unit->mobility_type !== Unit::MOBILITY_STATIONARY) {
                throw ValidationException::withMessages(['delivery_method' => 'Distribusi mobil tangki hanya untuk unit tidak bergerak.']);
            }

            if ($dispensing->fuelTruck === null || $dispensing->fuelTruck->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['fuel_truck_id' => 'Mobil tangki aktif wajib dipilih.']);
            }

            if ((float) $dispensing->quantity_liter > (float) $dispensing->fuelTruck->capacity_liter) {
                throw ValidationException::withMessages(['quantity_liter' => 'Quantity dispensing melebihi kapasitas mobil tangki.']);
            }
        }

        if ($dispensing->fuelOrder->planned_delivery_date !== null
            && $dispensing->dispensing_date->lt($dispensing->fuelOrder->planned_delivery_date)
            && ! $this->canUseOverride($dispensing, $user)) {
            throw ValidationException::withMessages(['dispensing_date' => 'Dispensing tidak boleh dilakukan sebelum tanggal rencana Fuel Order.']);
        }

        $quantity = (float) $dispensing->quantity_liter;

        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity_liter' => 'Quantity dispensing harus lebih besar dari 0.']);
        }

        $remaining = $this->fuelOrderService->remainingForItem($dispensing->fuelOrderItem);

        if ($quantity > $remaining && ! $this->canUseOverride($dispensing, $user)) {
            throw ValidationException::withMessages([
                'quantity_liter' => 'Quantity tidak boleh melebihi remaining FO tanpa supervisor override dan reason.',
            ]);
        }

        $this->stockService->ensureAvailable($dispensing->storage, $quantity);

        $lastMeter = $this->lastMeterValue($dispensing->unit);

        if ($dispensing->previous_meter_value === null) {
            throw ValidationException::withMessages([
                'previous_meter_value' => 'HM/KM awal wajib diisi sebelum posting.',
            ]);
        }

        if ((float) $dispensing->meter_value < (float) $dispensing->previous_meter_value) {
            throw ValidationException::withMessages([
                'meter_value' => 'HM/KM akhir tidak boleh lebih kecil dari HM/KM awal.',
            ]);
        }

        if ($lastMeter !== null && (float) $dispensing->previous_meter_value < $lastMeter && ! $this->canUseOverride($dispensing, $user)) {
            throw ValidationException::withMessages([
                'previous_meter_value' => 'HM/KM awal tidak boleh lebih rendah dari meter akhir transaksi terakhir tanpa supervisor override dan reason.',
            ]);
        }
    }

    private function canUseOverride(FuelDispensing $dispensing, User $user): bool
    {
        return filled($dispensing->override_reason) && $user->canOverrideOperationalControl();
    }
}
