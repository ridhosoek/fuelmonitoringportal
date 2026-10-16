<?php

namespace App\Services;

use App\Models\DailyReconciliation;
use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperationalDashboardService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters = []): array
    {
        $dateTo = Carbon::parse($filters['date_to'] ?? today())->startOfDay();
        $dateFrom = Carbon::parse($filters['date_from'] ?? $dateTo->copy()->subDays(29))->startOfDay();
        $storages = $this->storageQuery($filters)->with(['site', 'terminal'])->get();
        $storageIds = $storages->pluck('id');
        $siteIds = $storages->pluck('site_id')->unique()->values();
        $periodDays = (int) $dateFrom->diffInDays($dateTo) + 1;
        $previousDateTo = $dateFrom->copy()->subDay();
        $previousDateFrom = $previousDateTo->copy()->subDays($periodDays - 1);
        $dateFromString = $dateFrom->toDateString();
        $dateToString = $dateTo->toDateString();

        $stockByStorage = $this->stockByStorage($storageIds);
        $currentStock = (float) $stockByStorage->sum();
        $movements = $this->movementSummary($storageIds, $dateFromString, $dateToString);
        $averageDailyConsumption = $movements['consumption'] > 0
            ? $movements['consumption'] / $periodDays
            : null;
        $daysRemaining = $averageDailyConsumption !== null && $averageDailyConsumption > 0
            ? $currentStock / $averageDailyConsumption
            : null;
        $previousConsumption = abs($this->movementSum(
            $storageIds,
            StockMovement::TYPE_DISPENSING,
            $previousDateFrom->toDateString(),
            $previousDateTo->toDateString(),
        ));
        $variance = $this->varianceSummary($storageIds, $dateFromString, $dateToString);
        $stockPositions = $this->stockPositions(
            $storages,
            $stockByStorage,
            $dateFromString,
            $dateToString,
            $periodDays,
        );
        $alerts = $this->alerts($storageIds, $siteIds, $stockPositions, $dateFromString, $dateToString);

        return [
            'filters' => [
                'date_from' => $dateFromString,
                'date_to' => $dateToString,
                'site_id' => $filters['site_id'] ?? null,
                'storage_id' => $filters['storage_id'] ?? null,
            ],
            'last_updated' => Carbon::now('Asia/Jakarta')->format('d M Y H:i').' WIB',
            'summary' => [
                'current_stock' => [
                    'value' => $currentStock,
                    'status' => $stockPositions->contains('status', 'CRITICAL') ? 'CRITICAL' : 'SAFE',
                    'is_realtime' => true,
                ],
                'days_remaining' => [
                    'value' => $daysRemaining === null ? null : round($daysRemaining, 1),
                    'average_daily_consumption' => $averageDailyConsumption === null ? null : round($averageDailyConsumption, 2),
                ],
                'consumption' => [
                    'value' => $movements['consumption'],
                    'comparison_percentage' => $previousConsumption > 0
                        ? round((($movements['consumption'] - $previousConsumption) / $previousConsumption) * 100, 1)
                        : null,
                ],
                'stock_variance' => $variance,
                'receiving' => $movements['receiving'],
                'active_units' => Unit::query()->active()->whereIn('site_id', $siteIds)->count(),
                'open_fo' => FuelOrder::query()
                    ->activeForDispensing()
                    ->whereIn('site_id', $siteIds)
                    ->whereDate('fo_date', '>=', $dateFromString)
                    ->whereDate('fo_date', '<=', $dateToString)
                    ->count(),
                'transactions' => $movements['transactions'],
            ],
            'reconciliation' => [
                'opening_stock' => $movements['opening'],
                'receiving' => $movements['receiving'],
                'consumption' => $movements['consumption'],
                'adjustment' => $movements['adjustment'],
                'closing_stock' => $movements['closing'],
                'current_stock' => $currentStock,
                'matches_current' => $dateTo->isToday()
                    ? abs($movements['closing'] - $currentStock) < 0.001
                    : null,
            ],
            'stock_trend' => $this->stockTrend($storageIds, $storages, $dateFromString, $dateToString),
            'alerts' => $alerts,
            'critical_alert_count' => collect($alerts)->where('severity', 'CRITICAL')->count(),
            'stock_by_vhs' => $stockPositions->values()->all(),
            'fuel_movements' => $this->fuelMovements($storageIds, $dateFromString, $dateToString),
            'top_consumers' => $this->topConsumers(
                $storageIds,
                $dateFromString,
                $dateToString,
                $previousDateFrom->toDateString(),
                $previousDateTo->toDateString(),
            ),
            'consumption_by_unit_type' => $this->consumptionByUnitType($storageIds, $dateFromString, $dateToString),
            'efficiency' => $this->efficiency($storageIds, $dateFromString, $dateToString),
            'exceptions' => $this->exceptions($storageIds, $dateFromString, $dateToString),
            'transactions' => $this->recentTransactions($storageIds, $dateFromString, $dateToString),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Storage>
     */
    private function storageQuery(array $filters): Builder
    {
        return Storage::query()
            ->active()
            ->when($filters['site_id'] ?? null, fn (Builder $query, mixed $siteId): Builder => $query->where('site_id', $siteId))
            ->when($filters['storage_id'] ?? null, fn (Builder $query, mixed $storageId): Builder => $query->whereKey($storageId));
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return Collection<int, float>
     */
    private function stockByStorage(Collection $storageIds): Collection
    {
        return StockMovement::query()
            ->whereIn('storage_id', $storageIds)
            ->select('storage_id', DB::raw('SUM(quantity_liter) as stock'))
            ->groupBy('storage_id')
            ->get()
            ->mapWithKeys(fn (StockMovement $movement): array => [
                (int) $movement->storage_id => (float) $movement->stock,
            ]);
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return array{opening: float, receiving: float, consumption: float, adjustment: float, closing: float, transactions: int}
     */
    private function movementSummary(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $opening = (float) StockMovement::query()
            ->whereIn('storage_id', $storageIds)
            ->whereDate('movement_date', '<', $dateFrom)
            ->sum('quantity_liter');

        $row = StockMovement::query()
            ->whereIn('storage_id', $storageIds)
            ->whereDate('movement_date', '>=', $dateFrom)
            ->whereDate('movement_date', '<=', $dateTo)
            ->selectRaw('COALESCE(SUM(quantity_liter), 0) as net_movement')
            ->selectRaw('COALESCE(SUM(CASE WHEN movement_type = ? THEN quantity_liter ELSE 0 END), 0) as receiving', [StockMovement::TYPE_RECEIVING])
            ->selectRaw('COALESCE(SUM(CASE WHEN movement_type = ? THEN quantity_liter ELSE 0 END), 0) as dispensing', [StockMovement::TYPE_DISPENSING])
            ->selectRaw('COUNT(*) as transactions')
            ->firstOrFail();

        $receiving = (float) $row->receiving;
        $consumption = abs((float) $row->dispensing);
        $netMovement = (float) $row->net_movement;

        return [
            'opening' => $opening,
            'receiving' => $receiving,
            'consumption' => $consumption,
            'adjustment' => $netMovement - $receiving + $consumption,
            'closing' => $opening + $netMovement,
            'transactions' => (int) $row->transactions,
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
            ->whereDate('movement_date', '>=', $dateFrom)
            ->whereDate('movement_date', '<=', $dateTo)
            ->sum('quantity_liter');
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return array{value: ?float, percentage: ?float, coverage: string, available: bool}
     */
    private function varianceSummary(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $reconciliations = DailyReconciliation::query()
            ->whereIn('storage_id', $storageIds)
            ->where('status', DailyReconciliation::STATUS_APPROVED)
            ->whereDate('reconciliation_date', '>=', $dateFrom)
            ->whereDate('reconciliation_date', '<=', $dateTo)
            ->latest('reconciliation_date')
            ->latest('id')
            ->get()
            ->unique('storage_id');

        $coverage = $reconciliations->count().'/'.$storageIds->count().' VHS';

        if ($storageIds->isEmpty() || $reconciliations->count() !== $storageIds->count()) {
            return ['value' => null, 'percentage' => null, 'coverage' => $coverage, 'available' => false];
        }

        $systemStock = (float) $reconciliations->sum('system_closing_stock');
        $variance = (float) $reconciliations->sum('variance');

        return [
            'value' => $variance,
            'percentage' => $systemStock === 0.0 ? null : round(($variance / $systemStock) * 100, 2),
            'coverage' => $coverage,
            'available' => true,
        ];
    }

    /**
     * @param  Collection<int, Storage>  $storages
     * @param  Collection<int, float>  $stockByStorage
     * @return Collection<int, array<string, mixed>>
     */
    private function stockPositions(
        Collection $storages,
        Collection $stockByStorage,
        string $dateFrom,
        string $dateTo,
        int $periodDays,
    ): Collection {
        $consumptionByStorage = StockMovement::query()
            ->whereIn('storage_id', $storages->pluck('id'))
            ->where('movement_type', StockMovement::TYPE_DISPENSING)
            ->whereDate('movement_date', '>=', $dateFrom)
            ->whereDate('movement_date', '<=', $dateTo)
            ->select('storage_id', DB::raw('ABS(SUM(quantity_liter)) as consumption'))
            ->groupBy('storage_id')
            ->get()
            ->mapWithKeys(fn (StockMovement $movement): array => [
                (int) $movement->storage_id => (float) $movement->consumption,
            ]);

        return $storages
            ->map(function (Storage $storage) use ($stockByStorage, $consumptionByStorage, $periodDays): array {
                $currentStock = (float) $stockByStorage->get($storage->id, 0.0);
                $capacity = (float) $storage->capacity_liter;
                $minimum = (float) $storage->minimum_stock_liter;
                $periodConsumption = (float) $consumptionByStorage->get($storage->id, 0.0);
                $averageConsumption = $periodConsumption > 0 ? $periodConsumption / $periodDays : null;

                return [
                    'id' => $storage->id,
                    'name' => $storage->storage_name,
                    'code' => $storage->storage_code,
                    'site' => $storage->site?->site_name,
                    'site_id' => $storage->site_id,
                    'terminal' => $storage->terminal?->terminal_name,
                    'stock' => $currentStock,
                    'capacity' => $capacity > 0 ? $capacity : null,
                    'minimum' => $minimum,
                    'percentage' => $capacity > 0 ? round(($currentStock / $capacity) * 100, 1) : null,
                    'average_daily_consumption' => $averageConsumption,
                    'days_remaining' => $averageConsumption !== null && $averageConsumption > 0
                        ? round($currentStock / $averageConsumption, 1)
                        : null,
                    'status' => $storage->isLowStock($currentStock) ? 'CRITICAL' : 'SAFE',
                ];
            })
            ->sortBy([
                fn (array $position): int => $position['status'] === 'CRITICAL' ? 0 : 1,
                fn (array $position): float => $position['percentage'] ?? PHP_FLOAT_MAX,
            ]);
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @param  Collection<int, int>  $siteIds
     * @param  Collection<int, array<string, mixed>>  $stockPositions
     * @return list<array<string, mixed>>
     */
    private function alerts(
        Collection $storageIds,
        Collection $siteIds,
        Collection $stockPositions,
        string $dateFrom,
        string $dateTo,
    ): array {
        $openFoBySite = FuelOrder::query()
            ->activeForDispensing()
            ->whereIn('site_id', $siteIds)
            ->select('site_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('site_id')
            ->pluck('aggregate', 'site_id');

        $alerts = $stockPositions
            ->where('status', 'CRITICAL')
            ->map(fn (array $position): array => [
                'severity' => 'CRITICAL',
                'title' => 'Critical Stock',
                'subject' => $position['name'],
                'current_stock' => $position['stock'],
                'minimum_stock' => $position['minimum'],
                'average_daily_consumption' => $position['average_daily_consumption'],
                'days_remaining' => $position['days_remaining'],
                'action' => ((int) $openFoBySite->get($position['site_id'], 0)) > 0
                    ? 'FO sedang terbuka'
                    : 'FO belum tersedia',
                'message' => 'Stok berada pada atau di bawah batas minimum VHS.',
            ])
            ->values();

        $draftDispensings = FuelDispensing::query()
            ->whereIn('storage_id', $storageIds)
            ->where('status', FuelDispensing::STATUS_DRAFT)
            ->whereDate('dispensing_date', '>=', $dateFrom)
            ->whereDate('dispensing_date', '<=', $dateTo)
            ->count();

        if ($draftDispensings > 0) {
            $alerts->push([
                'severity' => 'WARNING',
                'title' => 'Pending Dispensing',
                'subject' => $draftDispensings.' transaksi',
                'message' => 'Dispensing masih berstatus draft dan belum memengaruhi stok.',
                'action' => 'Review transaksi draft',
            ]);
        }

        $pendingReconciliations = DailyReconciliation::query()
            ->whereIn('storage_id', $storageIds)
            ->where('status', DailyReconciliation::STATUS_SUBMITTED)
            ->whereDate('reconciliation_date', '>=', $dateFrom)
            ->whereDate('reconciliation_date', '<=', $dateTo)
            ->count();

        if ($pendingReconciliations > 0) {
            $alerts->push([
                'severity' => 'WARNING',
                'title' => 'Reconciliation Pending',
                'subject' => $pendingReconciliations.' reconciliation',
                'message' => 'Physical stock sudah dicatat dan menunggu approval.',
                'action' => 'Tinjau Daily Reconciliation',
            ]);
        }

        return $alerts
            ->sortBy([
                fn (array $alert): int => match ($alert['severity']) {
                    'CRITICAL' => 0,
                    'WARNING' => 1,
                    default => 2,
                },
                fn (array $alert): float => $alert['days_remaining'] ?? PHP_FLOAT_MAX,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @param  Collection<int, Storage>  $storages
     * @return array<string, mixed>
     */
    private function stockTrend(Collection $storageIds, Collection $storages, string $dateFrom, string $dateTo): array
    {
        $opening = (float) StockMovement::query()
            ->whereIn('storage_id', $storageIds)
            ->whereDate('movement_date', '<', $dateFrom)
            ->sum('quantity_liter');
        $dailyNet = StockMovement::query()
            ->whereIn('storage_id', $storageIds)
            ->whereDate('movement_date', '>=', $dateFrom)
            ->whereDate('movement_date', '<=', $dateTo)
            ->select('movement_date', DB::raw('SUM(quantity_liter) as net'))
            ->groupBy('movement_date')
            ->orderBy('movement_date')
            ->get()
            ->mapWithKeys(fn (StockMovement $movement): array => [
                $movement->movement_date->toDateString() => (float) $movement->net,
            ]);
        $minimum = (float) $storages->sum('minimum_stock_liter');
        $balance = $opening;
        $labels = [];
        $stock = [];
        $minimums = [];

        foreach ($this->buckets($dateFrom, $dateTo) as $bucket) {
            $cursor = Carbon::parse($bucket['start']);
            $end = Carbon::parse($bucket['end']);

            while ($cursor->lte($end)) {
                $balance += (float) $dailyNet->get($cursor->toDateString(), 0.0);
                $cursor->addDay();
            }

            $labels[] = $bucket['label'];
            $stock[] = round($balance, 3);
            $minimums[] = $minimum;
        }

        return [
            'labels' => $labels,
            'stock' => $stock,
            'minimum' => $minimums,
            'has_data' => $storageIds->isNotEmpty(),
        ];
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return array<string, mixed>
     */
    private function fuelMovements(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $rows = StockMovement::query()
            ->whereIn('storage_id', $storageIds)
            ->whereIn('movement_type', [StockMovement::TYPE_RECEIVING, StockMovement::TYPE_DISPENSING])
            ->whereDate('movement_date', '>=', $dateFrom)
            ->whereDate('movement_date', '<=', $dateTo)
            ->select('movement_date', 'movement_type', DB::raw('SUM(quantity_liter) as quantity'))
            ->groupBy('movement_date', 'movement_type')
            ->get()
            ->groupBy(fn (StockMovement $movement): string => $movement->movement_date->toDateString());
        $labels = [];
        $receiving = [];
        $consumption = [];
        $net = [];

        foreach ($this->buckets($dateFrom, $dateTo) as $bucket) {
            $received = 0.0;
            $consumed = 0.0;
            $cursor = Carbon::parse($bucket['start']);
            $end = Carbon::parse($bucket['end']);

            while ($cursor->lte($end)) {
                foreach ($rows->get($cursor->toDateString(), collect()) as $row) {
                    if ($row->movement_type === StockMovement::TYPE_RECEIVING) {
                        $received += (float) $row->quantity;
                    } else {
                        $consumed += abs((float) $row->quantity);
                    }
                }

                $cursor->addDay();
            }

            $labels[] = $bucket['label'];
            $receiving[] = $received;
            $consumption[] = $consumed;
            $net[] = $received - $consumed;
        }

        return compact('labels', 'receiving', 'consumption', 'net');
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return list<array<string, mixed>>
     */
    private function topConsumers(
        Collection $storageIds,
        string $dateFrom,
        string $dateTo,
        string $previousDateFrom,
        string $previousDateTo,
    ): array {
        $rows = FuelDispensing::query()
            ->with('unit')
            ->whereIn('storage_id', $storageIds)
            ->posted()
            ->whereDate('dispensing_date', '>=', $dateFrom)
            ->whereDate('dispensing_date', '<=', $dateTo)
            ->select('unit_id', DB::raw('SUM(quantity_liter) as consumption'))
            ->groupBy('unit_id')
            ->orderByDesc('consumption')
            ->limit(10)
            ->get();
        $total = (float) $rows->sum('consumption');
        $previous = FuelDispensing::query()
            ->whereIn('storage_id', $storageIds)
            ->posted()
            ->whereDate('dispensing_date', '>=', $previousDateFrom)
            ->whereDate('dispensing_date', '<=', $previousDateTo)
            ->whereIn('unit_id', $rows->pluck('unit_id'))
            ->select('unit_id', DB::raw('SUM(quantity_liter) as consumption'))
            ->groupBy('unit_id')
            ->pluck('consumption', 'unit_id');

        return $rows->map(function (FuelDispensing $row) use ($total, $previous): array {
            $consumption = (float) $row->consumption;
            $previousConsumption = (float) $previous->get($row->unit_id, 0.0);

            return [
                'unit' => $row->unit?->unit_code ?? 'Unknown',
                'unit_type' => $row->unit?->unit_type,
                'consumption' => $consumption,
                'percentage' => $total > 0 ? round(($consumption / $total) * 100, 1) : null,
                'comparison_percentage' => $previousConsumption > 0
                    ? round((($consumption - $previousConsumption) / $previousConsumption) * 100, 1)
                    : null,
            ];
        })->all();
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return list<array{label: string, value: float, percentage: float}>
     */
    private function consumptionByUnitType(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $rows = FuelDispensing::query()
            ->join('units', 'units.id', '=', 'fuel_dispensings.unit_id')
            ->whereIn('fuel_dispensings.storage_id', $storageIds)
            ->where('fuel_dispensings.status', FuelDispensing::STATUS_POSTED)
            ->whereDate('fuel_dispensings.dispensing_date', '>=', $dateFrom)
            ->whereDate('fuel_dispensings.dispensing_date', '<=', $dateTo)
            ->select('units.unit_type', DB::raw('SUM(fuel_dispensings.quantity_liter) as consumption'))
            ->groupBy('units.unit_type')
            ->orderByDesc('consumption')
            ->get();
        $total = (float) $rows->sum('consumption');

        return $rows->map(fn (object $row): array => [
            'label' => $row->unit_type,
            'value' => (float) $row->consumption,
            'percentage' => $total > 0 ? round(((float) $row->consumption / $total) * 100, 1) : 0.0,
        ])->all();
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return array{vehicle: list<array<string, mixed>>, equipment: list<array<string, mixed>>}
     */
    private function efficiency(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $rows = FuelDispensing::query()
            ->with('unit')
            ->whereIn('storage_id', $storageIds)
            ->posted()
            ->whereDate('dispensing_date', '>=', $dateFrom)
            ->whereDate('dispensing_date', '<=', $dateTo)
            ->whereNotNull('previous_meter_value')
            ->whereColumn('meter_value', '>', 'previous_meter_value')
            ->select('unit_id', 'meter_type')
            ->selectRaw('SUM(quantity_liter) as fuel')
            ->selectRaw('SUM(meter_value - previous_meter_value) as meter_delta')
            ->groupBy('unit_id', 'meter_type')
            ->get();

        $vehicle = [];
        $equipment = [];

        foreach ($rows as $row) {
            $fuel = (float) $row->fuel;
            $meterDelta = (float) $row->meter_delta;

            if ($fuel <= 0 || $meterDelta <= 0) {
                continue;
            }

            $item = [
                'unit' => $row->unit?->unit_code ?? 'Unknown',
                'unit_type' => $row->unit?->unit_type,
                'fuel' => $fuel,
                'meter_delta' => $meterDelta,
                'value' => $row->meter_type === 'KM'
                    ? round($meterDelta / $fuel, 2)
                    : round($fuel / $meterDelta, 2),
            ];

            if ($row->meter_type === 'KM') {
                $vehicle[] = $item;
            } else {
                $equipment[] = $item;
            }
        }

        usort($vehicle, fn (array $a, array $b): int => $b['value'] <=> $a['value']);
        usort($equipment, fn (array $a, array $b): int => $a['value'] <=> $b['value']);

        return ['vehicle' => $vehicle, 'equipment' => $equipment];
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return array{count: int, items: list<array<string, mixed>>}
     */
    private function exceptions(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        $query = FuelDispensing::query()
            ->with(['unit', 'storage'])
            ->whereIn('storage_id', $storageIds)
            ->posted()
            ->whereDate('dispensing_date', '>=', $dateFrom)
            ->whereDate('dispensing_date', '<=', $dateTo)
            ->where(function (Builder $query): void {
                $query->whereNull('previous_meter_value')
                    ->orWhereColumn('meter_value', '<=', 'previous_meter_value');
            });

        $count = (clone $query)->count();
        $items = $query
            ->latest('dispensing_date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (FuelDispensing $dispensing): array => [
                'number' => $dispensing->dispensing_number,
                'date' => $dispensing->dispensing_date?->toDateString(),
                'unit' => $dispensing->unit?->unit_code,
                'storage' => $dispensing->storage?->storage_name,
                'reason' => $dispensing->previous_meter_value === null
                    ? 'Meter awal tidak tersedia'
                    : 'Selisih meter tidak valid',
            ])
            ->all();

        return compact('count', 'items');
    }

    /**
     * @param  Collection<int, int>  $storageIds
     * @return list<array<string, mixed>>
     */
    private function recentTransactions(Collection $storageIds, string $dateFrom, string $dateTo): array
    {
        return FuelDispensing::query()
            ->with(['unit.site', 'storage.site'])
            ->whereIn('storage_id', $storageIds)
            ->posted()
            ->whereDate('dispensing_date', '>=', $dateFrom)
            ->whereDate('dispensing_date', '<=', $dateTo)
            ->latest('dispensing_date')
            ->latest('dispensing_time')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function (FuelDispensing $dispensing): array {
                $delta = $dispensing->workingHours();
                $fuel = (float) $dispensing->quantity_liter;
                $efficiency = null;

                if ($delta !== null && $delta > 0 && $fuel > 0) {
                    $efficiency = $dispensing->meter_type === 'KM'
                        ? $delta / $fuel
                        : $fuel / $delta;
                }

                return [
                    'id' => $dispensing->id,
                    'date' => $dispensing->dispensing_date?->toDateString(),
                    'time' => $dispensing->dispensing_time,
                    'unit' => $dispensing->unit?->unit_code,
                    'unit_type' => $dispensing->unit?->unit_type,
                    'site' => $dispensing->storage?->site?->site_name,
                    'storage' => $dispensing->storage?->storage_name,
                    'quantity' => $fuel,
                    'meter_type' => $dispensing->meter_type,
                    'meter_start' => $dispensing->previous_meter_value === null ? null : (float) $dispensing->previous_meter_value,
                    'meter_end' => (float) $dispensing->meter_value,
                    'efficiency' => $efficiency === null ? null : round($efficiency, 2),
                    'status' => $dispensing->status,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{label: string, start: string, end: string}>
     */
    private function buckets(string $dateFrom, string $dateTo): array
    {
        $start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);
        $days = (int) $start->diffInDays($end) + 1;
        $buckets = [];

        if ($days <= 31) {
            while ($start->lte($end)) {
                $date = $start->toDateString();
                $buckets[] = ['label' => $start->format('d M'), 'start' => $date, 'end' => $date];
                $start->addDay();
            }

            return $buckets;
        }

        if ($days <= 180) {
            while ($start->lte($end)) {
                $bucketStart = $start->copy();
                $bucketEnd = $start->copy()->addDays(6)->min($end);
                $buckets[] = [
                    'label' => $bucketStart->format('d M').' - '.$bucketEnd->format('d M'),
                    'start' => $bucketStart->toDateString(),
                    'end' => $bucketEnd->toDateString(),
                ];
                $start = $bucketEnd->copy()->addDay();
            }

            return $buckets;
        }

        while ($start->lte($end)) {
            $bucketStart = $start->copy();
            $bucketEnd = $start->copy()->endOfMonth()->min($end);
            $buckets[] = [
                'label' => $bucketStart->format('M Y'),
                'start' => $bucketStart->toDateString(),
                'end' => $bucketEnd->toDateString(),
            ];
            $start = $bucketEnd->copy()->addDay();
        }

        return $buckets;
    }
}
