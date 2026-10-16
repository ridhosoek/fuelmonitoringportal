<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Bast;
use App\Models\DailyReconciliation;
use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\FuelReceiving;
use App\Models\FuelSource;
use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\Unit;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelMonitoringWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_dedicated_guest_layout(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('class="auth-shell"', false)
            ->assertSee('class="auth-panel"', false)
            ->assertDontSee('class="app-wrapper"', false)
            ->assertSee('data-lte-color-mode="off"', false)
            ->assertSee('Logo PT Indra Angkola Energy')
            ->assertSee('images/logo-indra-angkola.png')
            ->assertSee('PT Indra Angkola Energy')
            ->assertSee('Copyright')
            ->assertDontSee('BNCT');
    }

    public function test_seeded_tender_demo_matches_required_acceptance_flow(): void
    {
        $this->seed();

        $this->assertSame(6, User::query()->count());
        $this->assertSame(56, Unit::query()->count());
        $this->assertSame(5, Storage::query()->count());
        $this->assertSame(7700.0, (float) StockMovement::query()->sum('quantity_liter'));

        $this->assertSame(FuelReceiving::STATUS_POSTED, FuelReceiving::query()->sole()->status);
        $this->assertSame(FuelOrder::STATUS_COMPLETED, FuelOrder::query()->sole()->status);
        $this->assertSame(300.0, (float) FuelDispensing::query()->posted()->sum('quantity_liter'));
        $this->assertSame(Bast::STATUS_FINAL, Bast::query()->sole()->status);

        $reconciliation = DailyReconciliation::query()->sole();

        $this->assertSame(DailyReconciliation::STATUS_APPROVED, $reconciliation->status);
        $this->assertSame('7700.000', $reconciliation->system_closing_stock);
        $this->assertSame('7700.000', $reconciliation->physical_closing_stock);
        $this->assertSame('0.000', $reconciliation->variance);
        $this->assertGreaterThanOrEqual(12, AuditLog::query()->count());
    }

    public function test_dashboard_loads_for_authenticated_user(): void
    {
        $this->withoutVite();
        $this->seed();

        $admin = User::query()->where('email', 'superadmin@vhs.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Operational Dashboard')
            ->assertSee('Current Stock')
            ->assertSee('Logo PT Indra Angkola Energy')
            ->assertSee('images/logo-indra-angkola.png')
            ->assertSee('Copyright')
            ->assertSee('PT Indra Angkola Energy');
    }

    public function test_dashboard_defaults_to_last_thirty_days_and_includes_older_transactions(): void
    {
        $this->seed();

        $transactionDate = today()->subDays(7)->toDateString();
        StockMovement::query()->update(['movement_date' => $transactionDate]);
        FuelDispensing::query()->update(['dispensing_date' => $transactionDate]);

        $overview = app(DashboardService::class)->overview();

        $this->assertSame(today()->subDays(29)->toDateString(), $overview['filters']['dateFrom']);
        $this->assertSame(today()->toDateString(), $overview['filters']['dateTo']);
        $this->assertSame(8000.0, $overview['kpis']['today_receiving']);
        $this->assertSame(300.0, $overview['kpis']['today_consumption']);
        $this->assertSame(3, $overview['kpis']['today_transactions']);
        $this->assertSame(2, $overview['latestDispensings']->count());
        $this->assertNotEmpty($overview['topUnits']['values']);
        $this->assertNotEmpty($overview['consumptionByUnitType']['values']);
    }

    public function test_dashboard_storage_filter_applies_to_every_transaction_chart(): void
    {
        $this->seed();

        $storageWithoutTransactions = Storage::query()
            ->whereNotIn('id', StockMovement::query()->select('storage_id'))
            ->firstOrFail();
        $overview = app(DashboardService::class)->overview([
            'storage_id' => $storageWithoutTransactions->id,
        ]);

        $this->assertSame([], $overview['topUnits']['values']);
        $this->assertSame([], $overview['consumptionByUnitType']['values']);
        $this->assertSame([], $overview['efficiency']['km']['values']);
        $this->assertSame([], $overview['efficiency']['hm']['values']);
        $this->assertTrue($overview['latestDispensings']->isEmpty());
    }

    public function test_seeded_user_can_login_with_username(): void
    {
        $this->withoutVite();
        $this->seed();

        $user = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->post('/login', [
            'login' => 'superadmin',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_viewer_cannot_open_transaction_create_pages(): void
    {
        $this->seed();

        $viewer = User::query()->where('email', 'viewer@bnct.test')->firstOrFail();

        $this->actingAs($viewer)
            ->get('/receivings/create')
            ->assertForbidden();
    }

    public function test_vhs_operator_can_dispense_but_cannot_receive_supplier_stock(): void
    {
        $this->withoutVite();
        $this->seed();

        $operator = User::query()->where('username', 'operator')->firstOrFail();

        $this->actingAs($operator)
            ->get(route('receivings.create', absolute: false))
            ->assertForbidden();

        $this->actingAs($operator)
            ->get(route('dispensings.create', absolute: false))
            ->assertOk();
    }

    public function test_receiving_posts_immediately_by_default_while_draft_remains_optional(): void
    {
        $this->seed();

        $admin = User::query()->where('username', 'superadmin')->firstOrFail();
        $storage = Storage::query()->with('site')->firstOrFail();
        $fuelSource = FuelSource::query()->firstOrFail();
        $stockService = app(StockService::class);
        $openingStock = $stockService->currentStock($storage);

        $this->actingAs($admin)
            ->post(route('receivings.store', absolute: false), [
                'receiving_date' => now()->toDateString(),
                'receiving_time' => '08:00',
                'site_id' => $storage->site_id,
                'storage_id' => $storage->id,
                'fuel_source_id' => $fuelSource->id,
                'delivery_document_number' => 'DIRECT-POST-001',
                'quantity_liter' => 100,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $postedReceiving = FuelReceiving::query()->latest('id')->firstOrFail();

        $this->assertSame(FuelReceiving::STATUS_POSTED, $postedReceiving->status);
        $this->assertSame($admin->id, $postedReceiving->posted_by);
        $this->assertSame($openingStock + 100, $stockService->currentStock($storage));

        $this->actingAs($admin)
            ->post(route('receivings.store', absolute: false), [
                'receiving_date' => now()->toDateString(),
                'receiving_time' => '09:00',
                'site_id' => $storage->site_id,
                'storage_id' => $storage->id,
                'fuel_source_id' => $fuelSource->id,
                'delivery_document_number' => 'DRAFT-001',
                'quantity_liter' => 50,
                'submission_action' => 'draft',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $draftReceiving = FuelReceiving::query()->latest('id')->firstOrFail();

        $this->assertSame(FuelReceiving::STATUS_DRAFT, $draftReceiving->status);
        $this->assertSame($openingStock + 100, $stockService->currentStock($storage));
    }

    public function test_receiving_list_hides_insignificant_quantity_decimals(): void
    {
        $this->withoutVite();
        $this->seed();

        $admin = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('receivings.index', absolute: false))
            ->assertOk()
            ->assertSee('8,000')
            ->assertDontSee('8,000.000');
    }

    public function test_stock_ledger_eager_loads_terminal_when_lazy_loading_is_disabled(): void
    {
        $this->withoutVite();
        $this->seed();
        Model::preventLazyLoading();

        $admin = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('inventory.movements', absolute: false))
            ->assertOk()
            ->assertSee('Terminal A / VHS BNCT 01');
    }

    public function test_data_entry_forms_use_grouped_two_column_layouts(): void
    {
        $this->withoutVite();
        $this->seed();

        $admin = User::query()->where('email', 'superadmin@vhs.test')->firstOrFail();
        $this->actingAs($admin);

        foreach ([
            route('receivings.create', absolute: false),
            route('fuel-orders.create', absolute: false),
            route('dispensings.create', absolute: false),
            route('basts.create', absolute: false),
            route('reconciliations.create', absolute: false),
        ] as $url) {
            $response = $this->get($url)->assertOk();

            $this->assertSame(2, substr_count($response->getContent(), 'class="entry-pane"'));
        }

        $master = $this->get(route('master.index', absolute: false))->assertOk();

        $this->assertStringContainsString('class="card master-data-card', $master->getContent());
        $this->assertStringNotContainsString('name="customer_code"', $master->getContent());

        $masterCreate = $this->get(route('master.create', 'customers', false))->assertOk();
        $this->assertSame(2, substr_count($masterCreate->getContent(), 'class="entry-pane"'));
    }

    public function test_primary_tender_pages_render_for_admin(): void
    {
        $this->withoutVite();
        $this->seed();

        $admin = User::query()->where('email', 'superadmin@vhs.test')->firstOrFail();
        $receiving = FuelReceiving::query()->sole();
        $fuelOrder = FuelOrder::query()->sole();
        $dispensing = FuelDispensing::query()->where('unit_id', Unit::query()->where('unit_code', 'TT-68')->value('id'))->firstOrFail();
        $bast = Bast::query()->sole();
        $reconciliation = DailyReconciliation::query()->sole();
        $unit = Unit::query()->where('unit_code', 'TT-68')->firstOrFail();

        $this->actingAs($admin);

        foreach ([
            route('dashboard', absolute: false),
            route('receivings.index', absolute: false),
            route('receivings.show', $receiving, false),
            route('fuel-orders.index', absolute: false),
            route('fuel-orders.show', $fuelOrder, false),
            route('dispensings.index', absolute: false),
            route('dispensings.show', $dispensing, false),
            route('basts.index', absolute: false),
            route('basts.show', $bast, false),
            route('reconciliations.index', absolute: false),
            route('reconciliations.show', $reconciliation, false),
            route('inventory.current-stock', absolute: false),
            route('inventory.movements', absolute: false),
            route('units.index', absolute: false),
            route('units.show', $unit, false),
            route('reports.index', absolute: false),
            route('audit-logs.index', absolute: false),
            route('master.index', absolute: false),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_bast_renders_as_printable_professional_document_without_lazy_loading(): void
    {
        $this->withoutVite();
        $this->seed();

        $admin = User::query()->where('email', 'superadmin@vhs.test')->firstOrFail();
        $bast = Bast::query()->sole();

        Model::preventLazyLoading();

        try {
            $response = $this->actingAs($admin)
                ->get(route('basts.show', $bast, false))
                ->assertOk()
                ->assertSee('BERITA ACARA SERAH TERIMA')
                ->assertSee('PT INDRA ANGKOLA ENERGY')
                ->assertSee('Logo PT Indra Angkola Energy')
                ->assertSee('images/logo-indra-angkola.png')
                ->assertSee('id="print-bast"', false)
                ->assertSee('onclick="window.print()"', false)
                ->assertSee('Pihak yang Menyerahkan')
                ->assertSee('Pihak yang Menerima')
                ->assertSee('TT-68')
                ->assertSee('CC-08')
                ->assertSee('TOTAL PENYERAHAN');

            $this->assertSame(2, substr_count($response->getContent(), 'data-bast-item'));
            $this->assertFileExists(public_path('images/logo-indra-angkola.png'));
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
