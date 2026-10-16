<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\FuelOrderItem;
use App\Models\FuelReceiving;
use App\Models\FuelSource;
use App\Models\FuelTruck;
use App\Models\Site;
use App\Models\Storage;
use App\Models\Terminal;
use App\Models\Unit;
use App\Models\User;
use App\Services\BastService;
use App\Services\FuelDispensingService;
use App\Services\FuelOrderService;
use App\Services\FuelReceivingService;
use App\Services\ReconciliationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'superadmin@vhs.test'],
            [
                'name' => 'Super Admin VHS',
                'username' => 'superadmin',
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
                'password' => 'password',
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@vhs.test'],
            [
                'name' => 'VHS Admin',
                'username' => 'vhsadmin',
                'role' => User::ROLE_VHS_ADMIN,
                'is_active' => true,
                'password' => 'password',
            ],
        );

        $operator = User::query()->updateOrCreate(
            ['email' => 'operator@vhs.test'],
            [
                'name' => 'VHS Operator',
                'username' => 'operator',
                'role' => User::ROLE_VHS_OPERATOR,
                'is_active' => true,
                'password' => 'password',
            ],
        );

        $supervisor = User::query()->updateOrCreate(
            ['email' => 'supervisor@vhs.test'],
            [
                'name' => 'Supervisor Fuel',
                'username' => 'supervisor',
                'role' => User::ROLE_SUPERVISOR,
                'is_active' => true,
                'password' => 'password',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'viewer@bnct.test'],
            [
                'name' => 'BNCT Viewer',
                'username' => 'bnctviewer',
                'role' => User::ROLE_CUSTOMER_VIEWER,
                'is_active' => true,
                'password' => 'password',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'requester@bnct.test'],
            [
                'name' => 'BNCT Fuel Requester',
                'username' => 'bnctrequester',
                'role' => User::ROLE_CUSTOMER_REQUESTER,
                'is_active' => true,
                'password' => 'password',
            ],
        );

        $customer = Customer::query()->updateOrCreate(
            ['customer_code' => 'BNCT'],
            [
                'customer_name' => 'PT Berlian Nusantara Container Terminal',
                'address' => 'Terminal BNCT',
                'contact_person' => 'BNCT Operation',
                'phone' => '021-000000',
                'email' => 'ops@bnct.test',
                'status' => 'ACTIVE',
            ],
        );

        $site = Site::query()->updateOrCreate(
            ['customer_id' => $customer->id, 'site_code' => 'BNCT-YARD'],
            [
                'site_name' => 'BNCT Yard',
                'address' => 'Area Operasional BNCT',
                'status' => 'ACTIVE',
            ],
        );

        $terminalA = Terminal::query()->updateOrCreate(
            ['site_id' => $site->id, 'terminal_code' => 'TERM-A'],
            ['terminal_name' => 'Terminal A', 'description' => 'Empat storage utama 20 KL.', 'status' => 'ACTIVE'],
        );
        $terminalB = Terminal::query()->updateOrCreate(
            ['site_id' => $site->id, 'terminal_code' => 'TERM-B'],
            ['terminal_name' => 'Terminal B', 'description' => 'Satu storage 15 KL.', 'status' => 'ACTIVE'],
        );

        $storage = Storage::query()->updateOrCreate(
            ['site_id' => $site->id, 'storage_code' => 'VHS-BNCT'],
            [
                'terminal_id' => $terminalA->id,
                'storage_name' => 'VHS BNCT 01',
                'capacity_liter' => 20000,
                'minimum_stock_liter' => 5000,
                'status' => 'ACTIVE',
            ],
        );

        foreach (range(2, 5) as $number) {
            Storage::query()->updateOrCreate(
                ['site_id' => $site->id, 'storage_code' => sprintf('VHS-BNCT-%02d', $number)],
                [
                    'terminal_id' => $number === 5 ? $terminalB->id : $terminalA->id,
                    'storage_name' => sprintf('VHS BNCT %02d', $number),
                    'capacity_liter' => $number === 5 ? 15000 : 20000,
                    'minimum_stock_liter' => $number === 5 ? 3000 : 5000,
                    'status' => 'ACTIVE',
                ],
            );
        }

        $sourcePertamina = FuelSource::query()->updateOrCreate(
            ['code' => 'DEP-PERTAMINA'],
            [
                'name' => 'Depo Pertamina',
                'source_type' => 'DEPOT',
                'address' => 'Jakarta',
                'status' => 'ACTIVE',
            ],
        );

        $sourceCemara = FuelSource::query()->updateOrCreate(
            ['code' => 'STO-CEMARA'],
            [
                'name' => 'Depo Cemara',
                'source_type' => 'DEPOT',
                'address' => 'Cemara',
                'status' => 'ACTIVE',
            ],
        );

        $fuelTruck = FuelTruck::query()->updateOrCreate(
            ['truck_code' => 'MT-VHS-01'],
            [
                'plate_number' => 'B 9001 VHS',
                'capacity_liter' => 8000,
                'driver_name' => 'Driver Standby VHS',
                'status' => 'ACTIVE',
            ],
        );

        $this->call(BnctUnitSeeder::class);
        $units = Unit::query()->where('site_id', $site->id)->get();

        if (FuelReceiving::query()->whereIn('delivery_document_number', ['SJ-PERTAMINA-8000', 'SJ-CEMARA-8000'])->exists()) {
            FuelReceiving::query()
                ->whereIn('delivery_document_number', ['SJ-PERTAMINA-8000', 'SJ-CEMARA-8000'])
                ->update(['fuel_source_id' => $sourceCemara->id, 'delivery_document_number' => 'SJ-CEMARA-8000']);
            $existingOrder = FuelOrder::query()->whereIn('notes', ['Demo FO for TT-001 and CRANE-01.', 'Demo FO for TT-68 and CC-08.'])->first();

            if ($existingOrder !== null) {
                $existingOrder->update([
                    'fo_date' => today()->subDay()->toDateString(),
                    'requested_time' => '16:00',
                    'planned_delivery_date' => today()->toDateString(),
                    'notes' => 'Demo FO for TT-68 and CC-08.',
                ]);
                FuelOrderItem::query()->where('fuel_order_id', $existingOrder->id)->where('unit_id', $units->firstWhere('unit_code', 'TT-68')->id)->update(['delivery_method' => 'DIRECT_STORAGE']);
                FuelOrderItem::query()->where('fuel_order_id', $existingOrder->id)->where('unit_id', $units->firstWhere('unit_code', 'CC-08')->id)->update(['delivery_method' => 'MOBILE_TANKER']);
            }
            FuelDispensing::query()
                ->where('unit_id', $units->firstWhere('unit_code', 'TT-68')->id)
                ->whereIn('notes', ['Demo dispensing TT-001.', 'Demo dispensing TT-68.'])
                ->update([
                    'previous_meter_value' => 120450,
                    'production_quantity' => 180,
                    'production_unit' => 'BOX',
                    'delivery_method' => 'DIRECT_STORAGE',
                    'fuel_truck_id' => null,
                    'notes' => 'Demo dispensing TT-68.',
                ]);
            FuelDispensing::query()
                ->where('unit_id', $units->firstWhere('unit_code', 'CC-08')->id)
                ->whereIn('notes', ['Demo dispensing CRANE-01.', 'Demo dispensing CC-08.'])
                ->update([
                    'previous_meter_value' => 4812,
                    'production_quantity' => 450,
                    'production_unit' => 'BOX',
                    'delivery_method' => 'MOBILE_TANKER',
                    'fuel_truck_id' => $fuelTruck->id,
                    'notes' => 'Demo dispensing CC-08.',
                ]);

            return;
        }

        $receiving = app(FuelReceivingService::class)->createDraft([
            'receiving_date' => today()->toDateString(),
            'receiving_time' => '08:00',
            'site_id' => $site->id,
            'storage_id' => $storage->id,
            'fuel_source_id' => $sourceCemara->id,
            'delivery_document_number' => 'SJ-CEMARA-8000',
            'truck_plate_number' => 'B 8000 VHS',
            'driver_name' => 'Driver Demo',
            'quantity_liter' => 8000,
            'notes' => 'Demo receiving for tender flow.',
        ], $admin);
        app(FuelReceivingService::class)->post($receiving, $superAdmin);

        $fuelOrder = app(FuelOrderService::class)->create([
            'fo_date' => today()->subDay()->toDateString(),
            'requested_time' => '16:00',
            'planned_delivery_date' => today()->toDateString(),
            'customer_id' => $customer->id,
            'site_id' => $site->id,
            'requested_by' => 'BNCT Operation',
            'notes' => 'Demo FO for TT-68 and CC-08.',
            'status' => 'OPEN',
            'items' => [
                ['unit_id' => $units->firstWhere('unit_code', 'TT-68')->id, 'delivery_method' => 'DIRECT_STORAGE', 'requested_quantity' => 100, 'notes' => 'TT-68'],
                ['unit_id' => $units->firstWhere('unit_code', 'CC-08')->id, 'delivery_method' => 'MOBILE_TANKER', 'requested_quantity' => 200, 'notes' => 'CC-08'],
            ],
        ], $admin);

        $ttItem = FuelOrderItem::query()->where('fuel_order_id', $fuelOrder->id)->where('unit_id', $units->firstWhere('unit_code', 'TT-68')->id)->firstOrFail();
        $craneItem = FuelOrderItem::query()->where('fuel_order_id', $fuelOrder->id)->where('unit_id', $units->firstWhere('unit_code', 'CC-08')->id)->firstOrFail();

        $ttDispensing = app(FuelDispensingService::class)->createDraft([
            'dispensing_date' => today()->toDateString(),
            'dispensing_time' => '10:00',
            'fuel_order_id' => $fuelOrder->id,
            'fuel_order_item_id' => $ttItem->id,
            'storage_id' => $storage->id,
            'unit_id' => $ttItem->unit_id,
            'delivery_method' => 'DIRECT_STORAGE',
            'previous_meter_value' => 120450,
            'meter_value' => 120500,
            'quantity_liter' => 100,
            'production_quantity' => 180,
            'operator_name' => 'VHS Operator',
            'notes' => 'Demo dispensing TT-68.',
        ], $operator);
        app(FuelDispensingService::class)->post($ttDispensing, $operator);

        $craneDispensing = app(FuelDispensingService::class)->createDraft([
            'dispensing_date' => today()->toDateString(),
            'dispensing_time' => '10:30',
            'fuel_order_id' => $fuelOrder->id,
            'fuel_order_item_id' => $craneItem->id,
            'storage_id' => $storage->id,
            'unit_id' => $craneItem->unit_id,
            'delivery_method' => 'MOBILE_TANKER',
            'fuel_truck_id' => $fuelTruck->id,
            'previous_meter_value' => 4812,
            'meter_value' => 4820,
            'quantity_liter' => 200,
            'production_quantity' => 450,
            'operator_name' => 'VHS Operator',
            'notes' => 'Demo dispensing CC-08.',
        ], $operator);
        app(FuelDispensingService::class)->post($craneDispensing, $operator);

        $bast = app(BastService::class)->createDraft([
            'bast_date' => today()->toDateString(),
            'fuel_dispensing_ids' => [$ttDispensing->id, $craneDispensing->id],
            'received_by' => 'BNCT Shift Supervisor',
            'operator_name' => 'VHS Operator',
            'notes' => 'Demo BAST from posted dispensing.',
        ], $operator);
        app(BastService::class)->finalize($bast, $supervisor);

        $reconciliation = app(ReconciliationService::class)->createDraft([
            'reconciliation_date' => today()->toDateString(),
            'storage_id' => $storage->id,
            'physical_closing_stock' => 7700,
            'notes' => 'Opening + receiving - dispensing = physical closing.',
        ], $admin);
        app(ReconciliationService::class)->submit($reconciliation, $admin);
        app(ReconciliationService::class)->approve($reconciliation, $supervisor);
    }
}
