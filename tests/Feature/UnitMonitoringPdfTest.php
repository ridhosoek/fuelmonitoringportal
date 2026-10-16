<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitMonitoringPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_monitoring_list_and_detail_can_be_exported_as_pdf(): void
    {
        $this->seed();
        $viewer = User::query()->where('username', 'bnctviewer')->firstOrFail();
        $unit = Unit::query()->where('unit_code', 'CC-08')->firstOrFail();

        $listPdf = $this->actingAs($viewer)->get(route('units.export-pdf', absolute: false));
        $listPdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('MONITORING UNIT', $listPdf->getContent());

        $detailPdf = $this->actingAs($viewer)->get(route('units.export-unit-pdf', [
            'unit' => $unit,
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'period' => 'daily',
        ], false));
        $detailPdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('PEMAKAIAN BBM UNIT CC-08', $detailPdf->getContent());
    }
}
