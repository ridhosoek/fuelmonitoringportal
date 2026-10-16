<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Site;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class BnctUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = Customer::query()->where('customer_code', 'BNCT')->firstOrFail();
        $site = Site::query()->where('customer_id', $customer->id)->where('site_code', 'BNCT-YARD')->firstOrFail();

        $this->adoptLegacyDemoUnit($site, 'TT-001', 'TT-68');
        $this->adoptLegacyDemoUnit($site, 'CRANE-01', 'CC-08');

        foreach ($this->units() as $unit) {
            Unit::query()->updateOrCreate(
                ['site_id' => $site->id, 'unit_code' => $unit['unit_code']],
                [
                    'customer_id' => $customer->id,
                    'unit_name' => $unit['unit_name'],
                    'plate_number' => null,
                    'unit_type' => $unit['unit_type'],
                    'mobility_type' => $unit['mobility_type'],
                    'brand' => null,
                    'model' => null,
                    'meter_type' => 'HM',
                    'production_unit' => $unit['production_unit'],
                    'status' => 'ACTIVE',
                ],
            );
        }

        Unit::query()
            ->where('site_id', $site->id)
            ->whereIn('unit_code', ['RS-001', 'FLT-001', 'TT-002', 'TT-003', 'CRANE-02', 'GEN-001', 'PICK-001', 'BUS-001'])
            ->get()
            ->each(function (Unit $unit): void {
                $hasHistory = $unit->orderItems()->exists()
                    || $unit->dispensings()->exists()
                    || $unit->bastItems()->exists();

                $hasHistory ? $unit->update(['status' => 'INACTIVE']) : $unit->delete();
            });
    }

    /** @return list<array{unit_code: string, unit_name: string, unit_type: string, mobility_type: string, production_unit: string|null}> */
    private function units(): array
    {
        $units = [];

        foreach ([8, 9, 10, 11] as $number) {
            $units[] = $this->unit(sprintf('CC-%02d', $number), 'Container Crane No.'.$number, 'CONTAINER CRANE', Unit::MOBILITY_STATIONARY, 'BOX');
        }

        foreach ([21, 22, 23, 27, 28, 29, 30, 31, 32, 33] as $number) {
            $units[] = $this->unit('RTG-'.$number, 'Rubber Tyred Gantry Crane No.'.$number, 'RUBBER TYRED GANTRY CRANE', Unit::MOBILITY_STATIONARY, 'BOX');
        }

        foreach ([...range(68, 83), ...range(1, 20)] as $number) {
            $units[] = $this->unit(sprintf('TT-%02d', $number), 'Terminal Tractor No.'.sprintf('%02d', $number), 'TERMINAL TRACTOR', Unit::MOBILITY_MOVABLE, 'BOX');
        }

        return [
            ...$units,
            $this->unit('RS-SEWA-B', 'Reach Stacker Alat Sewa B', 'REACH STACKER', Unit::MOBILITY_MOVABLE),
            $this->unit('MT-5KL', 'Mobil Tangki 5KL', 'MOBIL TANGKI CUSTOMER', Unit::MOBILITY_MOVABLE),
            $this->unit('RS-SEWA-A', 'Reach Stacker Alat Sewa A', 'REACH STACKER', Unit::MOBILITY_MOVABLE),
            $this->unit('ROAD-SWEEPER', 'Road Sweeper', 'ROAD SWEEPER', Unit::MOBILITY_MOVABLE),
            $this->unit('GEN-ARTG', 'Genset ARTG', 'GENSET', Unit::MOBILITY_STATIONARY),
            $this->unit('GEN-POWER', 'Genset Power', 'GENSET', Unit::MOBILITY_STATIONARY),
        ];
    }

    /** @return array{unit_code: string, unit_name: string, unit_type: string, mobility_type: string, production_unit: string|null} */
    private function unit(string $code, string $name, string $type, string $mobility, ?string $productionUnit = null): array
    {
        return [
            'unit_code' => $code,
            'unit_name' => $name,
            'unit_type' => $type,
            'mobility_type' => $mobility,
            'production_unit' => $productionUnit,
        ];
    }

    private function adoptLegacyDemoUnit(Site $site, string $legacyCode, string $targetCode): void
    {
        if (Unit::query()->where('site_id', $site->id)->where('unit_code', $targetCode)->exists()) {
            return;
        }

        Unit::query()
            ->where('site_id', $site->id)
            ->where('unit_code', $legacyCode)
            ->update(['unit_code' => $targetCode, 'plate_number' => null]);
    }
}
