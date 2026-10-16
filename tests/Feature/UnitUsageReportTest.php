<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use App\Services\UnitUsageReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitUsageReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_aggregates_daily_weekly_and_monthly_unit_usage(): void
    {
        $this->seed();

        $service = app(UnitUsageReportService::class);
        $date = now()->toDateString();

        foreach (['daily', 'weekly', 'monthly'] as $period) {
            $report = $service->build($date, $date, $period);

            $this->assertSame(300.0, $report['totals']['fuel']);
            $this->assertSame(2, $report['totals']['refueling_count']);
            $this->assertSame(100.0, $report['totals']['direct_fuel']);
            $this->assertSame(200.0, $report['totals']['mobile_tanker_fuel']);
            $this->assertCount(2, $report['rows']);
        }
    }

    public function test_unit_detail_renders_periodic_consumption(): void
    {
        $this->withoutVite();
        $this->seed();

        $viewer = User::query()->where('username', 'bnctviewer')->firstOrFail();
        $unit = Unit::query()->where('unit_code', 'CC-08')->firstOrFail();

        $this->actingAs($viewer)
            ->get(route('units.show', [
                'unit' => $unit,
                'period' => 'weekly',
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->toDateString(),
            ], false))
            ->assertOk()
            ->assertSee('Pemakaian per Weekly')
            ->assertSee('200')
            ->assertDontSee('200.000');
    }

    public function test_monthly_unit_report_downloads_valid_pdf(): void
    {
        $this->seed();

        $viewer = User::query()->where('username', 'bnctviewer')->firstOrFail();
        $response = $this->actingAs($viewer)->get(route('reports.index', [
            'report' => 'unit-usage',
            'month' => now()->format('Y-m'),
            'download' => 'pdf',
        ], false));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
        $this->assertStringContainsString('LAPORAN PEMAKAIAN BBM PER UNIT', $response->getContent());
    }
}
