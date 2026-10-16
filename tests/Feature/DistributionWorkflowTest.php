<?php

namespace Tests\Feature;

use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\FuelTruck;
use App\Models\Storage;
use App\Models\Terminal;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_distribution_structure_matches_two_terminal_operation(): void
    {
        $this->seed();

        $terminalA = Terminal::query()->where('terminal_code', 'TERM-A')->firstOrFail();
        $terminalB = Terminal::query()->where('terminal_code', 'TERM-B')->firstOrFail();

        $this->assertSame(4, Storage::query()->whereBelongsTo($terminalA)->count());
        $this->assertSame(1, Storage::query()->whereBelongsTo($terminalB)->count());
        $this->assertSame('15000.000', Storage::query()->whereBelongsTo($terminalB)->sole()->capacity_liter);
        $this->assertSame(1, FuelTruck::query()->active()->count());
        $this->assertSame(56, Unit::query()->active()->count());
        $this->assertSame(4, Unit::query()->where('unit_type', 'CONTAINER CRANE')->count());
        $this->assertSame(10, Unit::query()->where('unit_type', 'RUBBER TYRED GANTRY CRANE')->count());
        $this->assertSame(36, Unit::query()->where('unit_type', 'TERMINAL TRACTOR')->count());
    }

    public function test_customer_requester_can_submit_h1_order_but_cannot_dispense(): void
    {
        $this->withoutVite();
        $this->seed();

        $requester = User::query()->where('username', 'bnctrequester')->firstOrFail();

        $this->actingAs($requester)
            ->get(route('fuel-orders.create', absolute: false))
            ->assertOk()
            ->assertSee('Rencana Tanggal Pengisian');

        $this->actingAs($requester)
            ->get(route('dispensings.create', absolute: false))
            ->assertForbidden();
    }

    public function test_order_rejects_same_day_plan_and_wrong_delivery_method(): void
    {
        $this->seed();

        $requester = User::query()->where('username', 'bnctrequester')->firstOrFail();
        $stationaryUnit = Unit::query()->where('mobility_type', Unit::MOBILITY_STATIONARY)->firstOrFail();

        $this->actingAs($requester)
            ->post(route('fuel-orders.store', absolute: false), [
                'fo_date' => '2026-09-22',
                'requested_time' => '16:00',
                'planned_delivery_date' => '2026-09-22',
                'customer_id' => $stationaryUnit->customer_id,
                'site_id' => $stationaryUnit->site_id,
                'requested_by' => 'BNCT Operation',
                'status' => FuelOrder::STATUS_OPEN,
                'items' => [[
                    'unit_id' => $stationaryUnit->id,
                    'delivery_method' => 'DIRECT_STORAGE',
                    'requested_quantity' => 500,
                ]],
            ])
            ->assertSessionHasErrors([
                'planned_delivery_date' => 'Tanggal rencana pengisian harus setelah tanggal permintaan.',
                'items.0.delivery_method' => 'Unit tidak bergerak harus dilayani menggunakan mobil tangki.',
            ]);
    }

    public function test_seeded_dispensing_records_both_distribution_mechanisms(): void
    {
        $this->seed();

        $direct = FuelDispensing::query()->where('delivery_method', 'DIRECT_STORAGE')->sole();
        $mobile = FuelDispensing::query()->where('delivery_method', 'MOBILE_TANKER')->sole();

        $this->assertNull($direct->fuel_truck_id);
        $this->assertNotNull($mobile->fuel_truck_id);
        $this->assertSame(Unit::MOBILITY_MOVABLE, $direct->unit->mobility_type);
        $this->assertSame(Unit::MOBILITY_STATIONARY, $mobile->unit->mobility_type);
    }

    public function test_dispensing_posts_immediately_by_default(): void
    {
        $this->seed();

        $operator = User::query()->where('username', 'operator')->firstOrFail();
        $existing = FuelDispensing::query()
            ->where('delivery_method', 'DIRECT_STORAGE')
            ->with(['fuelOrderItem', 'fuelOrder'])
            ->firstOrFail();

        $existing->fuelOrderItem->update(['requested_quantity' => 125]);
        $existing->fuelOrder->update(['status' => FuelOrder::STATUS_PARTIAL]);

        $this->actingAs($operator)
            ->post(route('dispensings.store', absolute: false), [
                'dispensing_date' => now()->toDateString(),
                'dispensing_time' => '13:00',
                'fuel_order_id' => $existing->fuel_order_id,
                'fuel_order_item_id' => $existing->fuel_order_item_id,
                'storage_id' => $existing->storage_id,
                'unit_id' => $existing->unit_id,
                'delivery_method' => 'DIRECT_STORAGE',
                'previous_meter_value' => $existing->meter_value,
                'meter_value' => (float) $existing->meter_value + 10,
                'quantity_liter' => 25,
                'operator_name' => 'Operator Test',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $postedDispensing = FuelDispensing::query()->latest('id')->firstOrFail();

        $this->assertSame(FuelDispensing::STATUS_POSTED, $postedDispensing->status);
        $this->assertSame($operator->id, $postedDispensing->posted_by);
        $this->assertSame(25.0, (float) $postedDispensing->quantity_liter);
    }
}
