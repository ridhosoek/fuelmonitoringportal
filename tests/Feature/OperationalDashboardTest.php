<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyReconciliation;
use App\Models\Site;
use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\User;
use App\Services\OperationalDashboardService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_dashboard_requires_authentication(): void
    {
        $this->get(route('operational-dashboard', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_operational_dashboard_uses_actual_ledger_data_and_handles_partial_physical_stock(): void
    {
        $this->withoutVite();
        $this->seed();

        $dashboard = app(OperationalDashboardService::class)->overview();

        $this->assertSame(today()->toDateString(), $dashboard['filters']['date_to']);
        $this->assertSame(today()->subDays(29)->toDateString(), $dashboard['filters']['date_from']);
        $this->assertDatabaseCount('stock_movements', 3);
        $this->assertSame(3, StockMovement::query()
            ->whereDate('movement_date', '>=', $dashboard['filters']['date_from'])
            ->whereDate('movement_date', '<=', $dashboard['filters']['date_to'])
            ->count(), StockMovement::query()->pluck('movement_date')->join(', '));
        $this->assertSame(7700.0, $dashboard['summary']['current_stock']['value']);
        $this->assertSame(8000.0, $dashboard['summary']['receiving']);
        $this->assertSame(300.0, $dashboard['summary']['consumption']['value']);
        $this->assertSame(770.0, $dashboard['summary']['days_remaining']['value']);
        $this->assertFalse($dashboard['summary']['stock_variance']['available']);
        $this->assertNull($dashboard['summary']['stock_variance']['value']);
        $this->assertSame('1/5 VHS', $dashboard['summary']['stock_variance']['coverage']);
        $this->assertSame(4, $dashboard['critical_alert_count']);
        $this->assertCount(5, $dashboard['stock_by_vhs']);
        $this->assertSame(4, collect($dashboard['stock_by_vhs'])->where('status', Storage::STOCK_STATUS_EMPTY)->count());
        $this->assertSame(0, collect($dashboard['stock_by_vhs'])->where('status', Storage::STOCK_STATUS_LOW)->count());
        $this->assertCount(2, $dashboard['top_consumers']);
        $this->assertSame([], $dashboard['efficiency']['vehicle']);
        $this->assertCount(2, $dashboard['efficiency']['equipment']);
        $this->assertCount(2, $dashboard['transactions']);
        $this->assertSame(['CRITICAL'], collect($dashboard['alerts'])->pluck('severity')->unique()->values()->all());

        $admin = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('operational-dashboard', absolute: false))
            ->assertOk()
            ->assertSee('Fuel Monitoring Dashboard')
            ->assertSee('7.700')
            ->assertSee('Physical stock coverage 1/5 VHS.')
            ->assertDontSee('Operational Control');
    }

    public function test_days_remaining_is_unavailable_when_period_has_no_consumption(): void
    {
        $this->withoutVite();
        $this->seed();

        $tomorrow = today()->addDay()->toDateString();
        $dashboard = app(OperationalDashboardService::class)->overview([
            'date_from' => $tomorrow,
            'date_to' => $tomorrow,
        ]);

        $this->assertSame(0.0, $dashboard['summary']['consumption']['value']);
        $this->assertSame(0.0, $dashboard['summary']['receiving']);
        $this->assertNull($dashboard['summary']['days_remaining']['value']);
        $this->assertSame(0, $dashboard['summary']['transactions']);
        $this->assertSame([], $dashboard['transactions']);

        $admin = User::query()->where('username', 'superadmin')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('operational-dashboard', [
                'date_from' => $tomorrow,
                'date_to' => $tomorrow,
            ], false))
            ->assertOk()
            ->assertSee('Average daily consumption unavailable.')
            ->assertSee('No posted transactions in the selected period.');
    }

    public function test_specific_vhs_exposes_approved_variance_without_inventing_missing_coverage(): void
    {
        $this->seed();

        $reconciliation = DailyReconciliation::query()->where('status', DailyReconciliation::STATUS_APPROVED)->sole();
        $dashboard = app(OperationalDashboardService::class)->overview([
            'storage_id' => $reconciliation->storage_id,
        ]);

        $this->assertTrue($dashboard['summary']['stock_variance']['available']);
        $this->assertSame(0.0, $dashboard['summary']['stock_variance']['value']);
        $this->assertSame('1/1 VHS', $dashboard['summary']['stock_variance']['coverage']);
    }

    public function test_storage_filter_is_applied_to_all_operational_components(): void
    {
        $this->seed();

        $storageWithoutTransactions = Storage::query()
            ->whereNotIn('id', StockMovement::query()->select('storage_id'))
            ->firstOrFail();
        $dashboard = app(OperationalDashboardService::class)->overview([
            'storage_id' => $storageWithoutTransactions->id,
        ]);

        $this->assertSame(0.0, $dashboard['summary']['current_stock']['value']);
        $this->assertSame(0.0, $dashboard['summary']['receiving']);
        $this->assertSame(0.0, $dashboard['summary']['consumption']['value']);
        $this->assertSame([], $dashboard['top_consumers']);
        $this->assertSame([], $dashboard['consumption_by_unit_type']);
        $this->assertSame([], $dashboard['efficiency']['vehicle']);
        $this->assertSame([], $dashboard['efficiency']['equipment']);
        $this->assertSame([], $dashboard['transactions']);
        $this->assertSame('CRITICAL', $dashboard['alerts'][0]['severity']);
    }

    public function test_operational_dashboard_distinguishes_low_stock_from_empty_stock(): void
    {
        $this->seed();

        $storage = Storage::query()
            ->whereNotIn('id', StockMovement::query()->select('storage_id'))
            ->firstOrFail();
        $admin = User::query()->where('username', 'superadmin')->firstOrFail();
        $minimumStock = (float) $storage->minimum_stock_liter;

        StockMovement::query()->create([
            'movement_date' => today()->toDateString(),
            'movement_time' => now()->format('H:i:s'),
            'storage_id' => $storage->id,
            'movement_type' => StockMovement::TYPE_ADJUSTMENT_IN,
            'reference_type' => 'TEST',
            'reference_id' => $storage->id,
            'reference_number' => 'TEST-LOW-STOCK',
            'quantity_liter' => $minimumStock,
            'balance_after' => $minimumStock,
            'notes' => 'Boundary test for operational dashboard stock status.',
            'created_by' => $admin->id,
        ]);

        $dashboard = app(OperationalDashboardService::class)->overview([
            'storage_id' => $storage->id,
        ]);

        $this->assertSame(Storage::STOCK_STATUS_LOW, $dashboard['stock_by_vhs'][0]['status']);
        $this->assertSame(0, $dashboard['critical_alert_count']);
        $this->assertSame('WARNING', $dashboard['alerts'][0]['severity']);
        $this->assertSame('Low Stock', $dashboard['alerts'][0]['title']);
    }

    public function test_site_and_vhs_filter_pair_is_validated_server_side(): void
    {
        $this->seed();

        $customer = Customer::query()->firstOrFail();
        $otherSite = Site::query()->create([
            'customer_id' => $customer->id,
            'site_code' => 'OTHER',
            'site_name' => 'Other Site',
            'status' => 'ACTIVE',
        ]);
        $storage = Storage::query()->firstOrFail();
        $admin = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('operational-dashboard', [
                'site_id' => $otherSite->id,
                'storage_id' => $storage->id,
            ], false))
            ->assertSessionHasErrors('storage_id');
    }

    public function test_one_day_period_can_have_consumption_without_receiving(): void
    {
        $this->seed();

        StockMovement::query()
            ->where('movement_type', StockMovement::TYPE_RECEIVING)
            ->update(['movement_date' => today()->subDay()->toDateString()]);
        $dashboard = app(OperationalDashboardService::class)->overview([
            'date_from' => today()->toDateString(),
            'date_to' => today()->toDateString(),
        ]);

        $this->assertSame(0.0, $dashboard['summary']['receiving']);
        $this->assertSame(300.0, $dashboard['summary']['consumption']['value']);
        $this->assertSame(2, $dashboard['summary']['transactions']);
        $this->assertCount(1, $dashboard['fuel_movements']['labels']);
    }

    public function test_specific_site_and_customer_viewer_can_read_dashboard(): void
    {
        $this->withoutVite();
        $this->seed();

        $site = Site::query()->firstOrFail();
        $viewer = User::query()->where('role', User::ROLE_CUSTOMER_VIEWER)->firstOrFail();
        $dashboard = app(OperationalDashboardService::class)->overview([
            'site_id' => $site->id,
            'date_from' => today()->subDays(29)->toDateString(),
            'date_to' => today()->toDateString(),
        ]);

        $this->assertCount(5, $dashboard['stock_by_vhs']);
        $this->assertSame(7700.0, $dashboard['summary']['current_stock']['value']);
        $this->actingAs($viewer)
            ->get(route('operational-dashboard', ['site_id' => $site->id], false))
            ->assertOk();
    }

    public function test_dashboard_aggregation_uses_a_bounded_number_of_queries(): void
    {
        $this->seed();
        $queryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$queryCount): void {
            $queryCount++;
        });

        app(OperationalDashboardService::class)->overview();

        $this->assertLessThanOrEqual(30, $queryCount);
    }
}
