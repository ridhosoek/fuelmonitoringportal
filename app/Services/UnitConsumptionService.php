<?php

namespace App\Services;

use App\Models\FuelDispensing;
use App\Models\Unit;
use Illuminate\Support\Collection;

class UnitConsumptionService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function summaries(): Collection
    {
        return Unit::query()
            ->with(['site.customer'])
            ->oldest('unit_code')
            ->get()
            ->map(fn (Unit $unit): array => $this->summaryForUnit($unit));
    }

    /**
     * @return array<string, mixed>
     */
    public function summaryForUnit(Unit $unit): array
    {
        $dispensings = FuelDispensing::query()
            ->where('unit_id', $unit->id)
            ->where('status', FuelDispensing::STATUS_POSTED)
            ->oldest('dispensing_date')
            ->oldest('id')
            ->get();

        $fuel = (float) $dispensings->sum('quantity_liter');
        $openingMeter = $dispensings->first()?->previous_meter_value;
        $closingMeter = $dispensings->last()?->meter_value;
        $deltaMeter = (float) $dispensings->sum(
            fn (FuelDispensing $dispensing): float => $dispensing->workingHours() ?? 0.0,
        );
        $efficiency = $fuel > 0 ? $deltaMeter / $fuel : 0.0;
        $consumptionRate = $deltaMeter > 0 ? $fuel / $deltaMeter : 0.0;

        return [
            'unit' => $unit,
            'opening_meter' => $openingMeter,
            'closing_meter' => $closingMeter,
            'distance_or_hours' => $deltaMeter,
            'fuel' => $fuel,
            'refueling_count' => $dispensings->count(),
            'last_refueling' => $dispensings->last()?->dispensing_date,
            'efficiency' => $unit->meter_type === 'KM' ? $efficiency : $consumptionRate,
            'efficiency_label' => $unit->meter_type === 'KM' ? 'KM/L' : 'L/HM',
        ];
    }
}
