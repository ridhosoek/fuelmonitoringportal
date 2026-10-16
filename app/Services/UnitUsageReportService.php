<?php

namespace App\Services;

use App\Models\FuelDispensing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UnitUsageReportService
{
    /** @return array<string, mixed> */
    public function build(
        string $dateFrom,
        string $dateTo,
        string $period = 'daily',
        ?int $unitId = null,
        ?int $siteId = null,
    ): array {
        $dispensings = FuelDispensing::query()
            ->with(['unit.site', 'storage.terminal', 'fuelTruck'])
            ->posted()
            ->whereDate('dispensing_date', '>=', $dateFrom)
            ->whereDate('dispensing_date', '<=', $dateTo)
            ->when($unitId, fn ($query, int $id) => $query->where('unit_id', $id))
            ->when($siteId, fn ($query, int $id) => $query->whereHas('unit', fn ($query) => $query->where('site_id', $id)))
            ->oldest('dispensing_date')
            ->oldest('id')
            ->get();

        $rows = $dispensings
            ->groupBy(fn (FuelDispensing $dispensing): string => $dispensing->unit_id.'|'.$this->periodKey($dispensing, $period))
            ->map(function (Collection $items) use ($period): array {
                $first = $items->first();
                $periodStart = $this->periodStart($first->dispensing_date, $period);

                return [
                    'period_start' => $periodStart,
                    'period_label' => $this->periodLabel($periodStart, $period),
                    'unit' => $first->unit,
                    ...$this->metrics($items),
                ];
            })
            ->sortBy(fn (array $row): string => $row['period_start']->format('Y-m-d').'|'.$row['unit']->unit_code)
            ->values();

        $unitSummaries = $dispensings
            ->groupBy('unit_id')
            ->map(function (Collection $items): array {
                return [
                    'unit' => $items->first()->unit,
                    ...$this->metrics($items),
                ];
            })
            ->sortBy(fn (array $row): string => $row['unit']->unit_code)
            ->values();

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'period' => $period,
            'rows' => $rows,
            'unit_summaries' => $unitSummaries,
            'totals' => $this->metrics($dispensings),
        ];
    }

    /** @return array<string, mixed> */
    public function monthly(int $year, int $month, ?int $siteId = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return $this->build(
            $start->toDateString(),
            $start->copy()->endOfMonth()->toDateString(),
            'monthly',
            null,
            $siteId,
        );
    }

    /** @return array{fuel: float, refueling_count: int, working_hours: float|null, production: float|null, liter_per_hour: float|null, liter_per_production: float|null, direct_fuel: float, mobile_tanker_fuel: float} */
    private function metrics(Collection $dispensings): array
    {
        $fuel = (float) $dispensings->sum('quantity_liter');
        $workingValues = $dispensings->filter(fn (FuelDispensing $dispensing): bool => $dispensing->workingHours() !== null);
        $workingHours = $workingValues->isEmpty()
            ? null
            : (float) $workingValues->sum(fn (FuelDispensing $dispensing): float => $dispensing->workingHours() ?? 0.0);
        $productionValues = $dispensings->whereNotNull('production_quantity');
        $production = $productionValues->isEmpty() ? null : (float) $productionValues->sum('production_quantity');

        return [
            'fuel' => $fuel,
            'refueling_count' => $dispensings->count(),
            'working_hours' => $workingHours,
            'production' => $production,
            'liter_per_hour' => $workingHours > 0 ? $fuel / $workingHours : null,
            'liter_per_production' => $production > 0 ? $fuel / $production : null,
            'direct_fuel' => (float) $dispensings->where('delivery_method', 'DIRECT_STORAGE')->sum('quantity_liter'),
            'mobile_tanker_fuel' => (float) $dispensings->where('delivery_method', 'MOBILE_TANKER')->sum('quantity_liter'),
        ];
    }

    private function periodKey(FuelDispensing $dispensing, string $period): string
    {
        return $this->periodStart($dispensing->dispensing_date, $period)->format('Y-m-d');
    }

    private function periodStart(Carbon $date, string $period): Carbon
    {
        return match ($period) {
            'weekly' => $date->copy()->startOfWeek(Carbon::MONDAY),
            'monthly' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };
    }

    private function periodLabel(Carbon $start, string $period): string
    {
        return match ($period) {
            'weekly' => $start->format('d-m-Y').' s/d '.$start->copy()->endOfWeek(Carbon::SUNDAY)->format('d-m-Y'),
            'monthly' => $this->monthName((int) $start->month).' '.$start->year,
            default => $start->format('d-m-Y'),
        };
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$month];
    }
}
