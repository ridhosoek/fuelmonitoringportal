<?php

namespace Tests\Feature;

use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryStockLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_at_minimum_level_is_reported_as_low(): void
    {
        $this->withoutVite();
        $this->seed();

        $storage = Storage::query()->where('storage_code', 'VHS-BNCT')->firstOrFail();
        $admin = User::query()->where('username', 'superadmin')->firstOrFail();
        $currentStock = (float) StockMovement::query()
            ->where('storage_id', $storage->id)
            ->sum('quantity_liter');
        $minimumStock = (float) $storage->minimum_stock_liter;

        StockMovement::query()->create([
            'movement_date' => today()->toDateString(),
            'movement_time' => now()->format('H:i:s'),
            'storage_id' => $storage->id,
            'movement_type' => StockMovement::TYPE_ADJUSTMENT_OUT,
            'reference_type' => 'TEST',
            'reference_id' => $storage->id,
            'reference_number' => 'TEST-MINIMUM-STOCK',
            'quantity_liter' => $minimumStock - $currentStock,
            'balance_after' => $minimumStock,
            'notes' => 'Boundary test for minimum stock alert.',
            'created_by' => $admin->id,
        ]);

        $this->assertTrue($storage->isLowStock($minimumStock));
        $this->assertFalse($storage->isLowStock($minimumStock + 0.001));
        $this->assertSame(Storage::STOCK_STATUS_LOW, $storage->stockStatus($minimumStock));

        $dashboard = app(DashboardService::class)->overview();
        $alert = collect($dashboard['alerts'])
            ->firstWhere('message', $storage->storage_name.' telah mencapai atau berada di bawah minimum stock.');

        $this->assertNotNull($alert);
        $this->assertSame('LOW STOCK', $alert['title']);

        $this->actingAs($admin)
            ->get(route('inventory.current-stock', ['storage_id' => $storage->id], false))
            ->assertOk()
            ->assertSee('LOW')
            ->assertSee('5,000 L');
    }

    public function test_zero_stock_is_reported_as_empty_instead_of_low(): void
    {
        $this->withoutVite();
        $this->seed();

        $storage = Storage::query()
            ->whereNotIn('id', StockMovement::query()->select('storage_id'))
            ->firstOrFail();
        $admin = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->assertTrue($storage->isEmptyStock(0));
        $this->assertFalse($storage->isLowStock(0));
        $this->assertSame(Storage::STOCK_STATUS_EMPTY, $storage->stockStatus(0));

        $dashboard = app(DashboardService::class)->overview();
        $alert = collect($dashboard['alerts'])
            ->firstWhere('message', $storage->storage_name.' tidak memiliki stok BBM.');

        $this->assertNotNull($alert);
        $this->assertSame('EMPTY STOCK', $alert['title']);

        $this->actingAs($admin)
            ->get(route('inventory.current-stock', ['storage_id' => $storage->id], false))
            ->assertOk()
            ->assertSee('EMPTY')
            ->assertDontSee('LOW');
    }
}
