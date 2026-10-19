<?php

namespace App\Services;

use App\Models\DailyReconciliation;
use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters = []): array
    {
        $dateTo = Carbon::parse($filters['date_to'] ?? today())->toDateString();
        $dateFrom = Carbon::parse($filters['date_from'] ?? Carbon::parse($dateTo)->subDays(29))->toDateString();
        $storageIds = $this->storageQuery($filters)->pluck('id');

        $currentStock = $storageIds->sum(fn (int $storageId): float => $this->stockService->currentStock($storageId));
        $todayReceiving = $this->movementSum($storageIds, StockMovement::TYPE_RECEIVING, $dateFrom, $dateTo);
        $todayConsumption = abs($this->movementSum($storageIds, StockMovement::TYPE_DISPENSING, $dateFrom, $dateTo));
        $transactionCount = StockMovement::query()
            ->whereIn('storage_id', $storageIds)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->count();

        return [
            'filters' => compact('dateFrom', 'dateTo'),
            'kpis' => [
                'current_stock' => $currentStock,
                'today_receiving' => $todayReceiving,
                'today_consumption' => $todayConsumption,
                'today_transactions' => $transactionCount,
                'active_units' => Unit::query()->active()->count(),
                'open_fo' => FuelOrder::query()->activeForDispensing()->count(),
                'stock_variance' => (float) DailyReconciliation::query()
                    ->whereIn('storage_id', $storageIds)
                    ->whereBetween('reconciliation_date', [$dateFrom, $dateTo])
                    ->sum('variance'),
            ],
            'stockTrend' => $this->stockTrend($storageIds, $dateFrom, $dateTo),
            'receivingVsConsumption' => $this->receivingVsConsumption($storageIds, $dateFrom, $dateTo),
            'topUnits' => $this->topConsumingUnits($storageIds, $dateFrom, $dateTo),
            'consumptionByUnitType' => $this->consumptionByUnitType($storageIds, $dateFrom, $dateTo),
            'efficiency' => $this->efficiencySeries($storageIds, $dateFrom, $dateTo),
            'latestDispensings' => FuelDispensing::query()
                ->with(['fuelOrder', 'unit', 'storage'])
                ->whereIn('storage_id', $storageIds)
                ->where('status', FuelDispensing::STATUS_POSTED)
                ->whereBetween('dispensing_date', [$dateFrom, $dateTo])
                ->latest('dispensing_date')
                ->latest('id')
                ->limit(8)
                ->get(),
            'alerts' => $this->alerts($storageIds),
        ];
    }

    /**
     * @param  Collection<int, int>  $storageIds
     */
    private function movementSum(Collection $storageIds, string $type, string $dateFrom, string $dateTo): float
    {
        return (float) StockMovement::query()
            ->whereIn('storage_id', $storageIds)
            ->where('movement_type', $type)
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->sum('quantity_liter');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function storageQuery(array $filters)
    {
        return Storage::query()
            ->when($filters['site_id'] ?? null, fn ($query, $siteId) => $query->where('site_id', $siteId))
            ->when($filters['storage_id'] ?? null, fn ($query, $storageId) => $query->where('id', $storageId));
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return array{labels: list<string>, values: list<float>}
     */
    private function stockTrend(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $labels = $this->datePeriod($dateFrom, $dateTo);

        $values = $labels->map(function (string $date) use ($storageIds): float {
            return (float) StockMovement::query()
                ->whereIn('storage_id', $storageIds)
                ->whereDate('movement_date', '<=', $date)
                ->sum('quantity_liter');
        });

        return [
            'labels' => $labels->all(),
            'values' => $values->all(),
        ];
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return array{labels: list<string>, receiving: list<float>, consumption: list<float>}
     */
    private function receivingVsConsumption(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $period = $this->datePeriod($dateFrom, $dateTo);

        return [
            'labels' => $period->all(),
            'receiving' => $period->map(fn (string $date): float => $this->movementSum($storageIds, StockMovement::TYPE_RECEIVING, $date, $date))->all(),
            'consumption' => $period->map(fn (string $date): float => abs($this->movementSum($storageIds, StockMovement::TYPE_DISPENSING, $date, $date)))->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function topConsumingUnits(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $rows = FuelDispensing::query()
            ->select('unit_id', DB::raw('SUM(quantity_liter) as fuel'))
            ->with('unit')
            ->whereIn('storage_id', $storageIds)
            ->where('status', FuelDispensing::STATUS_POSTED)
            ->whereBetween('dispensing_date', [$dateFrom, $dateTo])
            ->groupBy('unit_id')
            ->orderByDesc('fuel')
            ->limit(10)
            ->get();

        return [
            'labels' => $rows->map(fn (FuelDispensing $row): string => $row->unit?->unit_code ?? 'Unknown')->all(),
            'values' => $rows->map(fn (FuelDispensing $row): float => (float) $row->fuel)->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function consumptionByUnitType(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $rows = FuelDispensing::query()
            ->join('units', 'units.id', '=', 'fuel_dispensings.unit_id')
            ->select('units.unit_type', DB::raw('SUM(fuel_dispensings.quantity_liter) as fuel'))
            ->whereIn('fuel_dispensings.storage_id', $storageIds)
            ->where('fuel_dispensings.status', FuelDispensing::STATUS_POSTED)
            ->whereBetween('fuel_dispensings.dispensing_date', [$dateFrom, $dateTo])
            ->groupBy('units.unit_type')
            ->orderByDesc('fuel')
            ->get();

        return [
            'labels' => $rows->pluck('unit_type')->all(),
            'values' => $rows->map(fn ($row): float => (float) $row->fuel)->all(),
        ];
    }

    /**
     * @return array{km: array{labels: list<string>, values: list<float>}, hm: array{labels: list<string>, values: list<float>}}
     */
    private function efficiencySeries(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $dispensings = FuelDispensing::query()
            ->with('unit')
            ->whereIn('storage_id', $storageIds)
            ->where('status', FuelDispensing::STATUS_POSTED)
            ->whereBetween('dispensing_date', [$dateFrom, $dateTo])
            ->get()
            ->groupBy('unit_id');

        $km = [];
        $hm = [];

        foreach ($dispensings as $unitDispensings) {
            $unit = $unitDispensings->first()->unit;
            $delta = (float) $unitDispensings->sum(
                fn (FuelDispensing $dispensing): float => $dispensing->workingHours() ?? 0.0,
            );
            $fuel = (float) $unitDispensings->sum('quantity_liter');

            if ($fuel <= 0 || $delta <= 0) {
                continue;
            }

            if ($unit->meter_type === 'KM') {
                $km[$unit->unit_code] = round($delta / $fuel, 2);
            } else {
                $hm[$unit->unit_code] = round($fuel / $delta, 2);
            }
        }

        arsort($km);
        asort($hm);

        return [
            'km' => ['labels' => array_keys($km), 'values' => array_values($km)],
            'hm' => ['labels' => array_keys($hm), 'values' => array_values($hm)],
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function datePeriod(string $dateFrom, string $dateTo): Collection
    {
        $period = collect();
        $cursor = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        while ($cursor->lte($end)) {
            $period->push($cursor->toDateString());
            $cursor->addDay();
        }

        return $period;
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return list<array{level: string, title: string, message: string}>
     */
    private function alerts(Collection $storageIds): array
    {
        $alerts = [];

        Storage::query()
            ->whereIn('id', $storageIds)
            ->with('site')
            ->get()
            ->each(function (Storage $storage) use (&$alerts): void {
                $stockStatus = $storage->stockStatus($this->stockService->currentStock($storage));

                if ($stockStatus === Storage::STOCK_STATUS_EMPTY) {
                    $alerts[] = [
                        'level' => 'danger',
                        'title' => 'EMPTY STOCK',
                        'message' => $storage->storage_name.' tidak memiliki stok BBM.',
                    ];
                } elseif ($stockStatus === Storage::STOCK_STATUS_LOW) {
                    $alerts[] = [
                        'level' => 'warning',
                        'title' => 'LOW STOCK',
                        'message' => $storage->storage_name.' telah mencapai atau berada di bawah minimum stock.',
                    ];
                }
            });

        if (FuelDispensing::query()->where('status', FuelDispensing::STATUS_DRAFT)->exists()) {
            $alerts[] = [
                'level' => 'info',
                'title' => 'PENDING DISPENSING',
                'message' => 'Ada draft dispensing yang belum diposting.',
            ];
        }

        if (DailyReconciliation::query()->where('status', DailyReconciliation::STATUS_SUBMITTED)->exists()) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'PENDING RECONCILIATION',
                'message' => 'Ada daily reconciliation menunggu approval supervisor.',
            ];
        }

        return $alerts;
    }
}
