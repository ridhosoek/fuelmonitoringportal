<?php

namespace App\Services;

use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\FuelOrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FuelOrderService
{
    public function __construct(
        private readonly NumberGenerator $numberGenerator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): FuelOrder
    {
        return DB::transaction(function () use ($data, $user): FuelOrder {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $order = FuelOrder::create([
                ...$data,
                'fo_number' => $data['fo_number'] ?? $this->numberGenerator->next('FO', FuelOrder::class, 'fo_number', $data['fo_date'] ?? null),
                'status' => $data['status'] ?? FuelOrder::STATUS_OPEN,
                'created_by' => $user->id,
            ]);

            foreach ($items as $item) {
                FuelOrderItem::create([
                    'fuel_order_id' => $order->id,
                    'unit_id' => $item['unit_id'],
                    'delivery_method' => $item['delivery_method'],
                    'requested_quantity' => $item['requested_quantity'],
                    'notes' => $item['notes'] ?? null,
                    'status' => 'ACTIVE',
                ]);
            }

            $this->auditLogger->log('CREATE', 'Fuel Order', $order, null, $order->fresh('items')->toArray(), $user, $order->fo_number);

            return $order->fresh(['customer', 'site', 'items.unit']);
        });
    }

    public function remainingForItem(FuelOrderItem $item): float
    {
        $postedQuantity = (float) FuelDispensing::query()
            ->where('fuel_order_item_id', $item->id)
            ->where('status', FuelDispensing::STATUS_POSTED)
            ->sum('quantity_liter');

        return max(0, (float) $item->requested_quantity - $postedQuantity);
    }

    public function refreshStatus(FuelOrder $order): FuelOrder
    {
        if (in_array($order->status, [FuelOrder::STATUS_DRAFT, FuelOrder::STATUS_CANCELLED], true)) {
            return $order;
        }

        $requested = (float) $order->items()->sum('requested_quantity');
        $dispensed = (float) $order->dispensings()
            ->where('status', FuelDispensing::STATUS_POSTED)
            ->sum('quantity_liter');

        $status = FuelOrder::STATUS_OPEN;

        if ($dispensed > 0 && $dispensed < $requested) {
            $status = FuelOrder::STATUS_PARTIAL;
        }

        if ($requested > 0 && $dispensed >= $requested) {
            $status = FuelOrder::STATUS_COMPLETED;
        }

        $order->update(['status' => $status]);

        return $order->fresh(['items.unit', 'dispensings']);
    }
}
