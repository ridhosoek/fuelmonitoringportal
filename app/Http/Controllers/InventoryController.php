<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Storage;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function currentStock(Request $request, StockService $stockService): View
    {
        $filters = $request->validate([
            'site_id' => ['nullable', 'exists:sites,id'],
            'storage_id' => ['nullable', 'exists:storages,id'],
        ]);

        $storages = Storage::query()
            ->with(['site', 'terminal'])
            ->when($filters['site_id'] ?? null, fn ($query, $siteId) => $query->where('site_id', $siteId))
            ->when($filters['storage_id'] ?? null, fn ($query, $storageId) => $query->where('id', $storageId))
            ->orderBy('storage_name')
            ->get()
            ->map(function (Storage $storage) use ($stockService): array {
                $stock = $stockService->currentStock($storage);
                $capacity = (float) $storage->capacity_liter;
                $status = $storage->stockStatus($stock);

                return [
                    'storage' => $storage,
                    'stock' => $stock,
                    'minimum' => (float) $storage->minimum_stock_liter,
                    'capacity' => $capacity,
                    'fill_rate' => $capacity > 0 ? ($stock / $capacity) * 100 : 0,
                    'status' => $status,
                ];
            });

        return view('inventory.current-stock', [
            'stockRows' => $storages,
            'storages' => Storage::query()->with(['site', 'terminal'])->orderBy('storage_name')->get(),
            'filters' => $filters,
        ]);
    }

    public function movements(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'storage_id' => ['nullable', 'exists:storages,id'],
            'movement_type' => ['nullable', 'in:RECEIVING,DISPENSING,ADJUSTMENT_IN,ADJUSTMENT_OUT,REVERSAL'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $movements = StockMovement::query()
            ->with(['storage.site', 'storage.terminal', 'creator'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('movement_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('movement_date', '<=', $date))
            ->when($filters['storage_id'] ?? null, fn ($query, $storageId) => $query->where('storage_id', $storageId))
            ->when($filters['movement_type'] ?? null, fn ($query, $movementType) => $query->where('movement_type', $movementType))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('reference_number', 'like', '%'.$search.'%'))
            ->latest('movement_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.movements', [
            'movements' => $movements,
            'storages' => Storage::query()->with('terminal')->orderBy('storage_name')->get(),
            'filters' => $filters,
        ]);
    }
}
