<?php

namespace Tests\Feature;

use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\FuelOrderItem;
use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\Unit;
use App\Models\User;
use App\Services\FuelDispensingService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelDispensingBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_uses_fuel_order_item_grid_without_global_unit_selectors(): void
    {
        $this->withoutVite();
        $this->seed();

        $operator = User::query()->where('username', 'operator')->firstOrFail();

        $this->actingAs($operator)
            ->get(route('dispensings.create', absolute: false))
            ->assertOk()
            ->assertSee('id="dispensingItemsGrid"', false)
            ->assertSee('Qty Real (L)')
            ->assertSee('Daftar unit di bawah otomatis mengikuti Fuel Order yang dipilih.')
            ->assertDontSee('id="fuel_order_item_id"', false)
            ->assertDontSee('id="unit_id"', false)
            ->assertDontSee('name="delivery_method"', false);
    }

    public function test_batch_dispensing_posts_each_entered_order_item_and_derives_its_unit(): void
    {
        $this->seed();

        $operator = User::query()->where('username', 'operator')->firstOrFail();
        $units = Unit::query()
            ->active()
            ->where('mobility_type', Unit::MOBILITY_MOVABLE)
            ->orderBy('id')
            ->limit(2)
            ->get();
        $storage = Storage::query()->findOrFail(
            StockMovement::query()->where('movement_type', StockMovement::TYPE_RECEIVING)->value('storage_id'),
        );
        $order = FuelOrder::create([
            'fo_number' => 'FO-BATCH-TEST-001',
            'fo_date' => today(),
            'requested_time' => '16:00',
            'planned_delivery_date' => today(),
            'customer_id' => $units->first()->customer_id,
            'site_id' => $units->first()->site_id,
            'requested_by' => 'Operation Test',
            'status' => FuelOrder::STATUS_OPEN,
            'created_by' => $operator->id,
        ]);
        $orderItems = $units->values()->map(function (Unit $unit, int $index) use ($order): FuelOrderItem {
            return FuelOrderItem::create([
                'fuel_order_id' => $order->id,
                'unit_id' => $unit->id,
                'delivery_method' => FuelOrderItem::DELIVERY_DIRECT_STORAGE,
                'requested_quantity' => $index === 0 ? 20 : 30,
                'status' => 'ACTIVE',
            ]);
        });
        $dispensingService = app(FuelDispensingService::class);
        $stockService = app(StockService::class);
        $openingStock = $stockService->currentStock($storage);
        $existingDispensingCount = FuelDispensing::query()->count();

        $items = $orderItems->map(function (FuelOrderItem $item, int $index) use ($dispensingService): array {
            $lastMeter = $dispensingService->lastMeterValue($item->unit) ?? 0;

            return [
                'fuel_order_item_id' => $item->id,
                'previous_meter_value' => $lastMeter,
                'meter_value' => $lastMeter + 1,
                'quantity_liter' => $index === 0 ? 20 : 30,
            ];
        })->all();

        $this->actingAs($operator)
            ->post(route('dispensings.store', absolute: false), [
                'dispensing_date' => today()->toDateString(),
                'dispensing_time' => '13:00',
                'fuel_order_id' => $order->id,
                'storage_id' => $storage->id,
                'operator_name' => 'Operator Batch',
                'items' => $items,
            ])
            ->assertRedirect(route('dispensings.index', absolute: false))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', '2 fuel dispensing berhasil diposting dan stok berkurang.');

        $createdDispensings = FuelDispensing::query()
            ->where('fuel_order_id', $order->id)
            ->orderBy('id')
            ->get();

        $this->assertSame($existingDispensingCount + 2, FuelDispensing::query()->count());
        $this->assertSame([20.0, 30.0], $createdDispensings->pluck('quantity_liter')->map(fn ($value): float => (float) $value)->all());
        $this->assertSame($units->pluck('id')->all(), $createdDispensings->pluck('unit_id')->all());
        $this->assertSame([FuelDispensing::STATUS_POSTED], $createdDispensings->pluck('status')->unique()->values()->all());
        $this->assertSame([FuelOrderItem::DELIVERY_DIRECT_STORAGE], $createdDispensings->pluck('delivery_method')->unique()->values()->all());
        $this->assertSame(FuelOrder::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertSame($openingStock - 50, $stockService->currentStock($storage));
    }

    public function test_batch_dispensing_rejects_an_item_from_another_fuel_order(): void
    {
        $this->seed();

        $operator = User::query()->where('username', 'operator')->firstOrFail();
        $orders = FuelOrder::query()->orderBy('id')->limit(2)->get();

        if ($orders->count() < 2) {
            $sourceOrder = $orders->firstOrFail();
            $otherOrder = $sourceOrder->replicate(['fo_number', 'status']);
            $otherOrder->fo_number = 'FO-OTHER-TEST-001';
            $otherOrder->status = FuelOrder::STATUS_OPEN;
            $otherOrder->save();
            $orders->push($otherOrder);
        }

        $selectedOrder = $orders->first();
        $foreignItem = FuelOrderItem::query()->where('fuel_order_id', $orders->last()->id)->first();

        if ($foreignItem === null) {
            $foreignItem = FuelOrderItem::create([
                'fuel_order_id' => $orders->last()->id,
                'unit_id' => Unit::query()->active()->where('mobility_type', Unit::MOBILITY_MOVABLE)->value('id'),
                'delivery_method' => FuelOrderItem::DELIVERY_DIRECT_STORAGE,
                'requested_quantity' => 10,
                'status' => 'ACTIVE',
            ]);
        }

        $this->actingAs($operator)
            ->post(route('dispensings.store', absolute: false), [
                'dispensing_date' => today()->toDateString(),
                'fuel_order_id' => $selectedOrder->id,
                'storage_id' => Storage::query()->value('id'),
                'operator_name' => 'Operator Batch',
                'items' => [[
                    'fuel_order_item_id' => $foreignItem->id,
                    'previous_meter_value' => 0,
                    'meter_value' => 1,
                    'quantity_liter' => 10,
                ]],
            ])
            ->assertSessionHasErrors([
                'items.0.fuel_order_item_id' => 'Item tidak termasuk dalam Fuel Order yang dipilih.',
            ]);

        $this->assertDatabaseMissing('fuel_dispensings', [
            'fuel_order_id' => $selectedOrder->id,
            'fuel_order_item_id' => $foreignItem->id,
        ]);
    }

    public function test_batch_dispensing_requires_real_quantity_when_a_row_is_touched(): void
    {
        $this->seed();

        $operator = User::query()->where('username', 'operator')->firstOrFail();
        $orderItem = FuelOrderItem::query()->firstOrFail();

        $this->actingAs($operator)
            ->post(route('dispensings.store', absolute: false), [
                'dispensing_date' => today()->toDateString(),
                'fuel_order_id' => $orderItem->fuel_order_id,
                'storage_id' => Storage::query()->value('id'),
                'operator_name' => 'Operator Batch',
                'items' => [[
                    'fuel_order_item_id' => $orderItem->id,
                    'previous_meter_value' => 10,
                    'meter_value' => 11,
                ]],
            ])
            ->assertSessionHasErrors([
                'items.0.quantity_liter' => 'Qty Real wajib diisi untuk unit yang diproses.',
            ]);
    }
}
