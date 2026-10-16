<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuelOrderRequest;
use App\Models\Customer;
use App\Models\FuelOrder;
use App\Models\Site;
use App\Models\Unit;
use App\Services\FuelOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuelOrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'planned_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:DRAFT,OPEN,PARTIAL,COMPLETED,CANCELLED'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $fuelOrders = FuelOrder::query()
            ->with(['customer', 'site', 'items.unit', 'dispensings'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('fo_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('fo_date', '<=', $date))
            ->when($filters['planned_date'] ?? null, fn ($query, $date) => $query->whereDate('planned_delivery_date', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['site_id'] ?? null, fn ($query, $siteId) => $query->where('site_id', $siteId))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(
                fn ($searchQuery) => $searchQuery
                    ->where('fo_number', 'like', '%'.$search.'%')
                    ->orWhere('requested_by', 'like', '%'.$search.'%'),
            ))
            ->latest('fo_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('fuel-orders.index', [
            'fuelOrders' => $fuelOrders,
            'sites' => Site::query()->orderBy('site_name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('fuel-orders.create', [
            'customers' => Customer::query()->where('status', 'ACTIVE')->orderBy('customer_name')->get(),
            'sites' => Site::query()->where('status', 'ACTIVE')->orderBy('site_name')->get(),
            'units' => Unit::query()->where('status', 'ACTIVE')->orderBy('unit_code')->get(),
        ]);
    }

    public function store(StoreFuelOrderRequest $request, FuelOrderService $service): RedirectResponse
    {
        $fuelOrder = $service->create($request->validated(), $request->user());

        return redirect()->route('fuel-orders.show', $fuelOrder)->with('status', 'Fuel Order berhasil dibuat.');
    }

    public function show(FuelOrder $fuelOrder, FuelOrderService $service): View
    {
        return view('fuel-orders.show', [
            'fuelOrder' => $fuelOrder->load(['customer', 'site', 'items.unit', 'dispensings.unit', 'basts']),
            'service' => $service,
        ]);
    }
}
