<?php

namespace App\Services;

use App\Models\Bast;
use App\Models\BastItem;
use App\Models\FuelDispensing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BastService
{
    public function __construct(
        private readonly NumberGenerator $numberGenerator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, User $user): Bast
    {
        return DB::transaction(function () use ($data, $user): Bast {
            $dispensingIds = array_values(array_unique($data['fuel_dispensing_ids'] ?? []));
            $dispensings = FuelDispensing::query()
                ->with(['fuelOrder.customer', 'fuelOrder.site', 'unit'])
                ->whereIn('id', $dispensingIds)
                ->where('status', FuelDispensing::STATUS_POSTED)
                ->get();

            if ($dispensings->count() !== count($dispensingIds)) {
                throw ValidationException::withMessages([
                    'fuel_dispensing_ids' => 'Semua dispensing harus berstatus POSTED dan valid.',
                ]);
            }

            if ($dispensings->pluck('fuel_order_id')->unique()->count() !== 1) {
                throw ValidationException::withMessages([
                    'fuel_dispensing_ids' => 'Satu BAST hanya boleh mereferensikan dispensing dalam FO yang sama.',
                ]);
            }

            if (BastItem::query()->whereIn('fuel_dispensing_id', $dispensingIds)->exists()) {
                throw ValidationException::withMessages([
                    'fuel_dispensing_ids' => 'Ada dispensing yang sudah digunakan dalam BAST.',
                ]);
            }

            $first = $dispensings->first();
            $order = $first->fuelOrder;
            $totalQuantity = (float) $dispensings->sum('quantity_liter');

            $bast = Bast::create([
                'bast_number' => $data['bast_number'] ?? $this->numberGenerator->next('BAST', Bast::class, 'bast_number', $data['bast_date'] ?? null),
                'bast_date' => $data['bast_date'],
                'fuel_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'site_id' => $order->site_id,
                'received_by' => $data['received_by'],
                'operator_name' => $data['operator_name'] ?? null,
                'quantity_liter' => number_format($totalQuantity, 3, '.', ''),
                'notes' => $data['notes'] ?? null,
                'status' => Bast::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            foreach ($dispensings as $dispensing) {
                BastItem::create([
                    'bast_id' => $bast->id,
                    'fuel_dispensing_id' => $dispensing->id,
                    'unit_id' => $dispensing->unit_id,
                    'quantity_liter' => $dispensing->quantity_liter,
                    'meter_type' => $dispensing->meter_type,
                    'meter_value' => $dispensing->meter_value,
                ]);
            }

            $this->auditLogger->log('CREATE', 'BAST', $bast, null, $bast->fresh('items')->toArray(), $user, $bast->bast_number);

            return $bast->fresh(['fuelOrder', 'items.fuelDispensing', 'items.unit']);
        });
    }

    public function finalize(Bast $bast, User $user): Bast
    {
        return DB::transaction(function () use ($bast, $user): Bast {
            $bast = Bast::query()->lockForUpdate()->findOrFail($bast->id);

            if ($bast->status !== Bast::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya BAST DRAFT yang dapat difinalisasi.',
                ]);
            }

            $oldValues = $bast->toArray();
            $bast->update([
                'status' => Bast::STATUS_FINAL,
                'finalized_by' => $user->id,
                'finalized_at' => now(),
            ]);

            $this->auditLogger->log('APPROVE', 'BAST', $bast, $oldValues, $bast->fresh()->toArray(), $user, $bast->bast_number);

            return $bast->fresh(['fuelOrder', 'items.fuelDispensing', 'items.unit']);
        });
    }
}
