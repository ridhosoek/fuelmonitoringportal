<?php

namespace App\Http\Controllers;

use App\Models\DailyReconciliation;
use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\FuelReceiving;
use App\Models\Site;
use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\Unit;
use App\Services\ExcelExportService;
use App\Services\FuelOrderService;
use App\Services\MonthlyUnitUsagePdfService;
use App\Services\OperationalUsageReportService;
use App\Services\UnitConsumptionService;
use App\Services\UnitUsageReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(
        Request $request,
        ExcelExportService $excel,
        FuelOrderService $fuelOrderService,
        UnitConsumptionService $unitConsumptionService,
        OperationalUsageReportService $operationalUsageReportService,
        UnitUsageReportService $unitUsageReportService,
        MonthlyUnitUsagePdfService $monthlyUnitUsagePdfService,
    ): View|Response {
        $filters = $request->validate([
            'report' => ['nullable', 'in:operational-usage,unit-usage,receiving,dispensing,stock-movement,unit-consumption,fo-reconciliation,daily-reconciliation'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'year' => ['nullable', 'integer', 'between:2020,2100'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'storage_id' => ['nullable', 'integer', 'exists:storages,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'period' => ['nullable', 'in:daily,weekly,monthly'],
            'month' => ['nullable', 'date_format:Y-m'],
            'export' => ['nullable', 'boolean'],
            'download' => ['nullable', 'in:pdf'],
        ]);

        $report = $filters['report'] ?? 'operational-usage';
        $operationalReport = null;
        $unitUsageReport = null;

        if ($report === 'unit-usage') {
            $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
            $dateTo = $filters['date_to'] ?? now()->toDateString();
            $period = $filters['period'] ?? 'daily';
            $unitUsageReport = $unitUsageReportService->build(
                $dateFrom,
                $dateTo,
                $period,
                isset($filters['unit_id']) ? (int) $filters['unit_id'] : null,
                isset($filters['site_id']) ? (int) $filters['site_id'] : null,
            );

            if (($filters['download'] ?? null) === 'pdf') {
                $month = Carbon::createFromFormat('Y-m', $filters['month'] ?? now()->format('Y-m'))->startOfMonth();
                $monthlyReport = $unitUsageReportService->monthly(
                    (int) $month->year,
                    (int) $month->month,
                    isset($filters['site_id']) ? (int) $filters['site_id'] : null,
                );
                $binary = $monthlyUnitUsagePdfService->render(
                    $monthlyReport,
                    'LAPORAN PEMAKAIAN BBM PER UNIT - '.strtoupper($month->format('F Y')),
                );

                return response($binary, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="laporan-pemakaian-unit-'.$month->format('Y-m').'.pdf"',
                    'Cache-Control' => 'no-store, no-cache',
                ]);
            }

            [$headers, $rows] = $this->unitUsageRows($unitUsageReport);

            if ((bool) ($filters['export'] ?? false)) {
                return $excel->download('pemakaian-unit-'.$dateFrom.'-'.$dateTo.'.xlsx', $headers, $rows);
            }

            return view('reports.index', [
                'report' => $report,
                'headers' => $headers,
                'rows' => $rows,
                'filters' => $filters,
                'operationalReport' => $operationalReport,
                'unitUsageReport' => $unitUsageReport,
                'sites' => Site::query()->active()->orderBy('site_name')->get(),
                'storages' => Storage::query()->with(['site', 'terminal'])->active()->orderBy('storage_name')->get(),
                'units' => Unit::query()->active()->orderBy('unit_code')->get(),
            ]);
        }

        if ($report === 'operational-usage') {
            $year = (int) ($filters['year'] ?? now()->year);
            $operationalReport = $operationalUsageReportService->build(
                $year,
                isset($filters['site_id']) ? (int) $filters['site_id'] : null,
                isset($filters['storage_id']) ? (int) $filters['storage_id'] : null,
            );

            if ((bool) ($filters['export'] ?? false)) {
                return $excel->downloadOperationalUsage(
                    'rekap-operasional-bnct-'.$year.'.xlsx',
                    $operationalReport,
                );
            }

            return view('reports.index', [
                'report' => $report,
                'headers' => [],
                'rows' => [],
                'filters' => $filters,
                'operationalReport' => $operationalReport,
                'unitUsageReport' => $unitUsageReport,
                'sites' => Site::query()->active()->orderBy('site_name')->get(),
                'storages' => Storage::query()->with(['site', 'terminal'])->active()->orderBy('storage_name')->get(),
                'units' => Unit::query()->active()->orderBy('unit_code')->get(),
            ]);
        }

        [$headers, $rows] = $this->buildReport($report, $filters, $fuelOrderService, $unitConsumptionService);

        if ((bool) ($filters['export'] ?? false)) {
            return $excel->download($report.'-'.now()->format('Ymd-His').'.xlsx', $headers, $rows);
        }

        return view('reports.index', [
            'report' => $report,
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $filters,
            'operationalReport' => $operationalReport,
            'unitUsageReport' => $unitUsageReport,
            'sites' => Site::query()->active()->orderBy('site_name')->get(),
            'storages' => Storage::query()->with(['site', 'terminal'])->active()->orderBy('storage_name')->get(),
            'units' => Unit::query()->active()->orderBy('unit_code')->get(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: list<string>, 1: list<array<int, scalar|null>>}
     */
    private function unitUsageRows(array $report): array
    {
        $rows = $report['rows']->map(fn (array $row): array => [
            $row['period_label'],
            $row['unit']->unit_code,
            $row['unit']->unit_name,
            $row['unit']->unit_type,
            $row['fuel'],
            $row['refueling_count'],
            $row['working_hours'],
            $row['production'],
            $row['liter_per_hour'],
            $row['liter_per_production'],
            $row['direct_fuel'],
            $row['mobile_tanker_fuel'],
        ])->all();

        return [[
            'Periode', 'Kode Unit', 'Nama Unit', 'Jenis', 'BBM Liter', 'Transaksi',
            'Working Hours', 'Produksi', 'Liter/Jam', 'Liter/Produksi',
            'Langsung Terminal', 'Mobil Tangki',
        ], $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: list<string>, 1: list<array<int, scalar|null>>}
     */
    private function buildReport(string $report, array $filters, FuelOrderService $fuelOrderService, UnitConsumptionService $unitConsumptionService): array
    {
        return match ($report) {
            'dispensing' => $this->dispensingRows($filters),
            'stock-movement' => $this->stockMovementRows($filters),
            'unit-consumption' => $this->unitConsumptionRows($unitConsumptionService),
            'fo-reconciliation' => $this->foRows($filters, $fuelOrderService),
            'daily-reconciliation' => $this->reconciliationRows($filters),
            default => $this->receivingRows($filters),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: list<string>, 1: list<array<int, scalar|null>>}
     */
    private function receivingRows(array $filters): array
    {
        $rows = FuelReceiving::query()
            ->with(['site', 'storage.terminal', 'fuelSource'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('receiving_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('receiving_date', '<=', $date))
            ->latest('receiving_date')
            ->get()
            ->map(fn (FuelReceiving $receiving): array => [
                $receiving->receiving_date?->toDateString(),
                $receiving->receiving_number,
                $receiving->site?->site_name,
                $receiving->storage?->terminal?->terminal_name,
                $receiving->storage?->storage_name,
                $receiving->fuelSource?->name,
                $receiving->delivery_document_number,
                $receiving->truck_plate_number,
                $receiving->quantity_liter,
                $receiving->status,
            ])
            ->all();

        return [['Date', 'Receiving No', 'Site', 'Terminal', 'VHS', 'Fuel Source', 'Delivery Doc', 'Truck', 'Qty Liter', 'Status'], $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: list<string>, 1: list<array<int, scalar|null>>}
     */
    private function dispensingRows(array $filters): array
    {
        $rows = FuelDispensing::query()
            ->with(['fuelOrder', 'unit', 'storage.terminal', 'fuelTruck'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('dispensing_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('dispensing_date', '<=', $date))
            ->latest('dispensing_date')
            ->get()
            ->map(fn (FuelDispensing $dispensing): array => [
                $dispensing->dispensing_date?->toDateString(),
                $dispensing->dispensing_number,
                $dispensing->fuelOrder?->fo_number,
                $dispensing->unit?->unit_code,
                $dispensing->unit?->mobility_type,
                $dispensing->delivery_method,
                $dispensing->fuelTruck?->truck_code,
                $dispensing->storage?->terminal?->terminal_name,
                $dispensing->storage?->storage_name,
                $dispensing->meter_type,
                $dispensing->previous_meter_value,
                $dispensing->meter_value,
                $dispensing->workingHours(),
                $dispensing->quantity_liter,
                $dispensing->literPerHour(),
                $dispensing->production_quantity,
                $dispensing->production_unit,
                $dispensing->literPerProductionUnit(),
                $dispensing->status,
            ])
            ->all();

        return [['Date', 'Dispensing No', 'FO No', 'Unit', 'Mobility', 'Delivery Method', 'Fuel Truck', 'Terminal', 'VHS', 'Meter Type', 'HM/KM Awal', 'HM/KM Akhir', 'Working Hours', 'Qty Liter', 'Liter/Jam', 'Production', 'Production Unit', 'Liter/Production', 'Status'], $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: list<string>, 1: list<array<int, scalar|null>>}
     */
    private function stockMovementRows(array $filters): array
    {
        $rows = StockMovement::query()
            ->with(['storage.site', 'storage.terminal', 'creator'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('movement_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('movement_date', '<=', $date))
            ->latest('movement_date')
            ->get()
            ->map(fn (StockMovement $movement): array => [
                $movement->movement_date?->toDateString(),
                $movement->movement_time,
                $movement->storage?->terminal?->terminal_name,
                $movement->storage?->storage_name,
                $movement->movement_type,
                $movement->reference_number,
                $movement->quantity_liter,
                $movement->balance_after,
                $movement->creator?->name,
            ])
            ->all();

        return [['Date', 'Time', 'Terminal', 'VHS', 'Type', 'Reference', 'Qty Liter', 'Balance After', 'Created By'], $rows];
    }

    /**
     * @return array{0: list<string>, 1: list<array<int, scalar|null>>}
     */
    private function unitConsumptionRows(UnitConsumptionService $service): array
    {
        $rows = $service->summaries()
            ->map(fn (array $summary): array => [
                $summary['unit']->unit_code,
                $summary['unit']->unit_name,
                $summary['unit']->unit_type,
                $summary['unit']->meter_type,
                $summary['fuel'],
                $summary['refueling_count'],
                $summary['distance_or_hours'],
                $summary['efficiency'],
                $summary['efficiency_label'],
                $summary['last_refueling']?->toDateString(),
            ])
            ->all();

        return [['Unit Code', 'Unit Name', 'Type', 'Meter Type', 'Fuel Liter', 'Refueling Count', 'KM/HM Delta', 'Efficiency', 'Unit', 'Last Refueling'], $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: list<string>, 1: list<array<int, scalar|null>>}
     */
    private function foRows(array $filters, FuelOrderService $service): array
    {
        $orders = FuelOrder::query()
            ->with(['customer', 'site', 'items.unit', 'dispensings', 'basts'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('fo_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('fo_date', '<=', $date))
            ->latest('fo_date')
            ->get();

        $rows = $orders->flatMap(fn (FuelOrder $order) => $order->items->map(fn ($item): array => [
            $order->fo_date?->toDateString(),
            $order->requested_time,
            $order->planned_delivery_date?->toDateString(),
            $order->fo_number,
            $order->customer?->customer_name,
            $order->site?->site_name,
            $item->unit?->unit_code,
            $item->unit?->mobility_type,
            $item->delivery_method,
            $item->requested_quantity,
            $item->dispensedQuantity(),
            $service->remainingForItem($item),
            $order->basts->count(),
            $order->status,
        ]))->all();

        return [['Request Date', 'Request Time', 'Planned Delivery', 'FO No', 'Customer', 'Site', 'Unit', 'Mobility', 'Delivery Method', 'Requested', 'Dispensed', 'Remaining', 'BAST Count', 'Status'], $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: list<string>, 1: list<array<int, scalar|null>>}
     */
    private function reconciliationRows(array $filters): array
    {
        $rows = DailyReconciliation::query()
            ->with(['storage.site', 'storage.terminal', 'preparer', 'approver'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('reconciliation_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('reconciliation_date', '<=', $date))
            ->latest('reconciliation_date')
            ->get()
            ->map(fn (DailyReconciliation $reconciliation): array => [
                $reconciliation->reconciliation_date?->toDateString(),
                $reconciliation->storage?->terminal?->terminal_name,
                $reconciliation->storage?->storage_name,
                $reconciliation->opening_stock,
                $reconciliation->total_receiving,
                $reconciliation->total_dispensing,
                $reconciliation->total_adjustment,
                $reconciliation->system_closing_stock,
                $reconciliation->physical_closing_stock,
                $reconciliation->variance,
                $reconciliation->variance_percentage,
                $reconciliation->status,
            ])
            ->all();

        return [['Date', 'Terminal', 'VHS', 'Opening', 'Receiving', 'Dispensing', 'Adjustment', 'System Closing', 'Physical Closing', 'Variance', 'Variance %', 'Status'], $rows];
    }
}
