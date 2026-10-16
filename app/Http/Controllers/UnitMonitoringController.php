<?php

namespace App\Http\Controllers;

use App\Models\FuelDispensing;
use App\Models\Unit;
use App\Services\TabularPdfService;
use App\Services\UnitConsumptionService;
use App\Services\UnitUsageReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UnitMonitoringController extends Controller
{
    public function index(Request $request, UnitConsumptionService $service): View
    {
        $filters = $this->filters($request);
        $summaries = $this->filteredSummaries($service, $filters);

        return view('units.index', [
            'summaries' => $summaries,
            'unitTypes' => Unit::query()->select('unit_type')->distinct()->orderBy('unit_type')->pluck('unit_type'),
            'filters' => $filters,
        ]);
    }

    public function exportPdf(Request $request, UnitConsumptionService $service, TabularPdfService $pdf): Response
    {
        $filters = $this->filters($request);
        $summaries = $this->filteredSummaries($service, $filters);
        $binary = $pdf->render(
            'MONITORING UNIT',
            'Ringkasan pemakaian BBM dan meter per '.now()->format('d-m-Y H:i'),
            ['Kode', 'Unit', 'Kategori', 'Mobilitas', 'Meter', 'BBM L', 'Transaksi', 'Working', 'Efisiensi', 'Status'],
            $summaries->map(fn (array $summary): array => [
                $summary['unit']->unit_code,
                $summary['unit']->unit_name,
                $summary['unit']->unit_type,
                $summary['unit']->mobility_type,
                $summary['unit']->meter_type,
                $summary['fuel'],
                $summary['refueling_count'],
                $summary['distance_or_hours'],
                number_format($summary['efficiency'], 3, '.', ',').' '.$summary['efficiency_label'],
                $summary['unit']->status,
            ])->all(),
            [
                'Jumlah Unit' => $summaries->count(),
                'Total BBM' => number_format((float) $summaries->sum('fuel'), 3, '.', ',').' L',
                'Transaksi' => $summaries->sum('refueling_count'),
            ],
        );

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="monitoring-unit-'.now()->format('Ymd-His').'.pdf"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function exportUnitPdf(Request $request, Unit $unit, UnitConsumptionService $service, UnitUsageReportService $usageReportService, TabularPdfService $pdf): Response
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();
        $period = $filters['period'] ?? 'daily';
        $summary = $service->summaryForUnit($unit->load(['customer', 'site']));
        $report = $usageReportService->build($dateFrom, $dateTo, $period, $unit->id);
        $binary = $pdf->render(
            'PEMAKAIAN BBM UNIT '.$unit->unit_code,
            $unit->unit_name.' | '.$dateFrom.' s/d '.$dateTo.' | '.strtoupper($period),
            ['Periode', 'BBM L', 'Transaksi', 'Working', 'Produksi', 'L/Jam', 'L/Produksi', 'Langsung', 'Mobil Tangki'],
            $report['rows']->map(fn (array $row): array => [
                $row['period_label'],
                $row['fuel'],
                $row['refueling_count'],
                $row['working_hours'],
                $row['production'],
                $row['liter_per_hour'],
                $row['liter_per_production'],
                $row['direct_fuel'],
                $row['mobile_tanker_fuel'],
            ])->all(),
            [
                'Total BBM' => number_format($report['totals']['fuel'], 3, '.', ',').' L',
                'Transaksi' => $report['totals']['refueling_count'],
                'Working' => number_format((float) $report['totals']['working_hours'], 3, '.', ','),
                'Efisiensi' => number_format($summary['efficiency'], 3, '.', ',').' '.$summary['efficiency_label'],
            ],
        );

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="unit-'.$unit->unit_code.'-'.$dateFrom.'-'.$dateTo.'.pdf"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function show(Request $request, Unit $unit, UnitConsumptionService $service, UnitUsageReportService $usageReportService): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();

        return view('units.show', [
            'summary' => $service->summaryForUnit($unit->load(['customer', 'site'])),
            'usageReport' => $usageReportService->build(
                $dateFrom,
                $dateTo,
                $filters['period'] ?? 'daily',
                $unit->id,
            ),
            'filters' => $filters,
            'dispensings' => FuelDispensing::query()
                ->with(['fuelOrder', 'storage.terminal', 'fuelTruck'])
                ->where('unit_id', $unit->id)
                ->latest('dispensing_date')
                ->latest('id')
                ->paginate(15),
        ]);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'meter_type' => ['nullable', 'in:KM,HM'],
            'unit_type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:ACTIVE,INACTIVE'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function filteredSummaries(UnitConsumptionService $service, array $filters): Collection
    {
        return $service->summaries()
            ->filter(fn (array $summary): bool => blank($filters['meter_type'] ?? null) || $summary['unit']->meter_type === $filters['meter_type'])
            ->filter(fn (array $summary): bool => blank($filters['unit_type'] ?? null) || $summary['unit']->unit_type === $filters['unit_type'])
            ->filter(fn (array $summary): bool => blank($filters['status'] ?? null) || $summary['unit']->status === $filters['status'])
            ->filter(function (array $summary) use ($filters): bool {
                $search = $filters['q'] ?? null;

                if (blank($search)) {
                    return true;
                }

                return str_contains(strtolower($summary['unit']->unit_code), strtolower($search))
                    || str_contains(strtolower($summary['unit']->unit_name), strtolower($search));
            })
            ->values();
    }
}
