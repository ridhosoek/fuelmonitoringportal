<?php

namespace Tests\Feature;

use App\Models\FuelDispensing;
use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\Terminal;
use App\Models\Unit;
use App\Models\User;
use App\Services\OperationalUsageReportService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OperationalFuelReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_bnct_master_contains_terminal_capacity_configuration(): void
    {
        $this->seed();

        $this->assertSame(5, Storage::query()->count());
        $this->assertSame(4, Storage::query()->where('capacity_liter', 20000)->count());
        $this->assertSame(1, Storage::query()->where('capacity_liter', 15000)->count());
        $this->assertSame(2, Terminal::query()->count());
        $this->assertSame('BOX', Unit::query()->where('unit_code', 'CC-08')->value('production_unit'));
    }

    public function test_dispensing_rejects_end_meter_below_start_meter(): void
    {
        $this->seed();

        $operator = User::query()->where('username', 'operator')->firstOrFail();
        $dispensing = FuelDispensing::query()->firstOrFail();

        $this->actingAs($operator)
            ->post(route('dispensings.store', absolute: false), [
                'dispensing_date' => now()->toDateString(),
                'dispensing_time' => '12:00',
                'fuel_order_id' => $dispensing->fuel_order_id,
                'fuel_order_item_id' => $dispensing->fuel_order_item_id,
                'storage_id' => $dispensing->storage_id,
                'unit_id' => $dispensing->unit_id,
                'delivery_method' => $dispensing->delivery_method,
                'previous_meter_value' => 200,
                'meter_value' => 199,
                'quantity_liter' => 25,
                'operator_name' => 'Operator Test',
            ])
            ->assertSessionHasErrors([
                'meter_value' => 'HM/KM akhir tidak boleh lebih kecil dari HM/KM awal.',
            ]);

        $this->assertSame(2, FuelDispensing::query()->count());
    }

    public function test_dispensing_form_exposes_start_end_meter_and_production_inputs(): void
    {
        $this->withoutVite();
        $this->seed();

        $operator = User::query()->where('username', 'operator')->firstOrFail();

        $this->actingAs($operator)
            ->get(route('dispensings.create', absolute: false))
            ->assertOk()
            ->assertSee('data-field="previous_meter_value"', false)
            ->assertSee('data-field="meter_value"', false)
            ->assertSee('data-field="production_quantity"', false)
            ->assertSee('Qty Real (L)')
            ->assertSee('Delta:')
            ->assertSee('VHS / Tangki Timbun');
    }

    public function test_vhs_minimum_stock_cannot_exceed_capacity(): void
    {
        $this->seed();

        $admin = User::query()->where('username', 'superadmin')->firstOrFail();
        $storage = Storage::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('master.store', ['resource' => 'storages'], false), [
                'site_id' => $storage->site_id,
                'terminal_id' => $storage->terminal_id,
                'storage_code' => 'VHS-CAPACITY-TEST',
                'storage_name' => 'VHS Capacity Test',
                'capacity_liter' => 20000,
                'minimum_stock_liter' => 21000,
                'status' => 'ACTIVE',
            ])
            ->assertSessionHasErrors([
                'minimum_stock_liter' => 'Batas minimum stok tidak boleh melebihi kapasitas VHS.',
            ]);

        $this->assertDatabaseMissing('storages', ['storage_code' => 'VHS-CAPACITY-TEST']);
    }

    public function test_operational_report_calculates_working_hours_and_weighted_ratios(): void
    {
        $this->seed();

        $report = app(OperationalUsageReportService::class)->build((int) now()->year);
        $craneRow = $report['groups']['CONTAINER CRANE']['rows']->first();

        $this->assertSame(8.0, $craneRow['total']['working_hours']);
        $this->assertSame(200.0, $craneRow['total']['fuel']);
        $this->assertSame(450.0, $craneRow['total']['production']);
        $this->assertSame(25.0, $craneRow['total']['liter_per_hour']);
        $this->assertEqualsWithDelta(0.444444, $craneRow['total']['liter_per_production'], 0.000001);

        $this->assertSame(58.0, $report['totals']['total']['working_hours']);
        $this->assertSame(300.0, $report['totals']['total']['fuel']);
        $this->assertEqualsWithDelta(5.172414, $report['totals']['total']['liter_per_hour'], 0.000001);
    }

    public function test_operational_report_renders_and_exports_xlsx(): void
    {
        $this->withoutVite();
        $this->seed();

        $admin = User::query()->where('username', 'superadmin')->firstOrFail();
        $url = route('reports.index', [
            'report' => 'operational-usage',
            'year' => now()->year,
        ], false);

        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertSee('Rekap Pemakaian BBM, Produksi, dan Working Hours')
            ->assertSee('VHS BNCT 01')
            ->assertSee('CONTAINER CRANE');

        $response = $this->actingAs($admin)->get($url.'&export=1');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $response->getContent());
    }

    public function test_stock_movement_cannot_exceed_vhs_capacity(): void
    {
        $this->seed();

        $storage = Storage::query()->where('storage_code', 'VHS-BNCT')->firstOrFail();
        $admin = User::query()->where('username', 'superadmin')->firstOrFail();
        $movementCount = StockMovement::query()->count();

        try {
            app(StockService::class)->recordMovement(
                $storage->id,
                StockMovement::TYPE_ADJUSTMENT_IN,
                'capacity_test',
                1,
                'CAPACITY-TEST',
                13000,
                $admin,
                now()->toDateString(),
            );

            $this->fail('Expected a validation exception for stock above VHS capacity.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Saldo setelah transaksi melebihi kapasitas VHS 20,000 liter.',
                $exception->errors()['quantity_liter'][0],
            );
        }

        $this->assertSame($movementCount, StockMovement::query()->count());
    }
}
