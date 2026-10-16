<?php

namespace App\Services;

use App\Models\FuelDispensing;
use App\Models\Storage;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperationalUsageReportService
{
    /**
     * @return array{
     *     year: int,
     *     site_name: string,
     *     storage_name: string,
     *     months: array<int, string>,
     *     groups: Collection<string, array{label: string, rows: Collection<int, array<string, mixed>>, totals: array<string, mixed>}>,
     *     totals: array<string, mixed>
     * }
     */
    public function build(int $year, ?int $siteId = null, ?int $storageId = null): array
    {
        $selectedStorage = $storageId === null
            ? null
            : Storage::query()->with('site')->findOrFail($storageId);

        if ($selectedStorage !== null) {
            $siteId = $selectedStorage->site_id;
        }

        $units = Unit::query()
            ->with([
                'site.customer',
                'dispensings' => fn ($query) => $query
                    ->with('storage')
                    ->posted()
                    ->whereYear('dispensing_date', $year)
                    ->when($storageId, fn (Builder $query, int $id): Builder => $query->where('storage_id', $id))
                    ->oldest('dispensing_date')
                    ->oldest('id'),
            ])
            ->when($siteId, fn (Builder $query, int $id): Builder => $query->where('site_id', $id))
            ->when($storageId, function (Builder $query, int $id) use ($year): Builder {
                return $query->whereHas('dispensings', fn (Builder $query): Builder => $query
                    ->posted()
                    ->whereYear('dispensing_date', $year)
                    ->where('storage_id', $id));
            })
            ->orderBy('unit_type')
            ->orderBy('unit_code')
            ->get();

        $rows = $units->flatMap(function (Unit $unit) use ($selectedStorage): Collection {
            if ($unit->dispensings->isEmpty()) {
                return collect([$this->unitStorageRow($unit, $selectedStorage, collect())]);
            }

            return $unit->dispensings
                ->groupBy('storage_id')
                ->map(fn (Collection $dispensings): array => $this->unitStorageRow(
                    $unit,
                    $dispensings->first()?->storage,
                    $dispensings,
                ))
                ->values();
        });

        $groups = $rows
            ->groupBy('unit_type')
            ->map(fn (Collection $groupRows, string $unitType): array => [
                'label' => $unitType,
                'rows' => $groupRows->values(),
                'totals' => $this->totalsForRows($groupRows),
            ]);

        return [
            'year' => $year,
            'site_name' => $siteId === null
                ? 'Semua Site'
                : ($units->first()?->site?->site_name ?? $selectedStorage?->site?->site_name ?? 'Site tidak ditemukan'),
            'storage_name' => $selectedStorage?->storage_name ?? 'Semua VHS',
            'months' => $this->monthNames(),
            'groups' => $groups,
            'totals' => $this->totalsForRows($rows),
        ];
    }

    /**
     * @param  Collection<int, FuelDispensing>  $dispensings
     * @return array<string, mixed>
     */
    private function unitStorageRow(Unit $unit, ?Storage $storage, Collection $dispensings): array
    {
        $months = collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                $month => $this->metrics($dispensings->filter(
                    fn (FuelDispensing $dispensing): bool => (int) $dispensing->dispensing_date?->month === $month,
                )),
            ])
            ->all();

        return [
            'unit' => $unit,
            'unit_type' => $unit->unit_type,
            'storage' => $storage,
            'production_unit' => $unit->production_unit,
            'months' => $months,
            'total' => $this->metrics($dispensings),
        ];
    }

    /**
     * @param  Collection<int, FuelDispensing>  $dispensings
     * @return array{fuel: float, production: float|null, working_hours: float|null, liter_per_production: float|null, liter_per_hour: float|null, refueling_count: int}
     */
    private function metrics(Collection $dispensings): array
    {
        $fuel = (float) $dispensings->sum('quantity_liter');
        $productionValues = $dispensings->whereNotNull('production_quantity');
        $production = $productionValues->isEmpty() ? null : (float) $productionValues->sum('production_quantity');
        $meteredDispensings = $dispensings->filter(
            fn (FuelDispensing $dispensing): bool => $dispensing->previous_meter_value !== null && $dispensing->meter_value !== null,
        );
        $workingHours = $meteredDispensings->isEmpty()
            ? null
            : (float) $meteredDispensings->sum(fn (FuelDispensing $dispensing): float => $dispensing->workingHours() ?? 0.0);

        return [
            'fuel' => $fuel,
            'production' => $production,
            'working_hours' => $workingHours,
            'liter_per_production' => $production > 0 ? $fuel / $production : null,
            'liter_per_hour' => $workingHours > 0 ? $fuel / $workingHours : null,
            'refueling_count' => $dispensings->count(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{months: array<int, array<string, mixed>>, total: array<string, mixed>}
     */
    private function totalsForRows(Collection $rows): array
    {
        $monthTotals = collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                $month => $this->combineMetrics($rows->pluck("months.$month")),
            ])
            ->all();

        return [
            'months' => $monthTotals,
            'total' => $this->combineMetrics($rows->pluck('total')),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return array{fuel: float, production: float|null, working_hours: float|null, liter_per_production: float|null, liter_per_hour: float|null, refueling_count: int}
     */
    private function combineMetrics(Collection $metrics): array
    {
        $fuel = (float) $metrics->sum('fuel');
        $productionValues = $metrics->whereNotNull('production');
        $production = $productionValues->isEmpty() ? null : (float) $productionValues->sum('production');
        $workingHourValues = $metrics->whereNotNull('working_hours');
        $workingHours = $workingHourValues->isEmpty() ? null : (float) $workingHourValues->sum('working_hours');

        return [
            'fuel' => $fuel,
            'production' => $production,
            'working_hours' => $workingHours,
            'liter_per_production' => $production > 0 ? $fuel / $production : null,
            'liter_per_hour' => $workingHours > 0 ? $fuel / $workingHours : null,
            'refueling_count' => (int) $metrics->sum('refueling_count'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function monthNames(): array
    {
        return [
            1 => 'JANUARI',
            2 => 'FEBRUARI',
            3 => 'MARET',
            4 => 'APRIL',
            5 => 'MEI',
            6 => 'JUNI',
            7 => 'JULI',
            8 => 'AGUSTUS',
            9 => 'SEPTEMBER',
            10 => 'OKTOBER',
            11 => 'NOVEMBER',
            12 => 'DESEMBER',
        ];
    }
}
