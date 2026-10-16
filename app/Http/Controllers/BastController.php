<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBastRequest;
use App\Models\Bast;
use App\Models\FuelDispensing;
use App\Services\BastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BastController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'in:DRAFT,FINAL,CANCELLED'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $basts = Bast::query()
            ->with(['fuelOrder', 'customer', 'site', 'items'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('bast_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('bast_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('bast_number', 'like', '%'.$search.'%')->orWhere('received_by', 'like', '%'.$search.'%'))
            ->latest('bast_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('basts.index', [
            'basts' => $basts,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('basts.create', [
            'dispensings' => FuelDispensing::query()
                ->with(['fuelOrder', 'unit', 'bastItem'])
                ->where('status', FuelDispensing::STATUS_POSTED)
                ->whereDoesntHave('bastItem')
                ->latest('dispensing_date')
                ->get(),
        ]);
    }

    public function store(StoreBastRequest $request, BastService $service): RedirectResponse
    {
        $bast = $service->createDraft($request->validated(), $request->user());

        return redirect()->route('basts.show', $bast)->with('status', 'BAST berhasil dibuat sebagai DRAFT.');
    }

    public function show(Bast $bast): View
    {
        return view('basts.show', [
            'bast' => $bast->load([
                'fuelOrder',
                'customer',
                'site',
                'items.fuelDispensing.storage.terminal',
                'items.fuelDispensing.fuelTruck',
                'items.unit',
                'creator',
                'finalizer',
            ]),
        ]);
    }

    public function finalize(Bast $bast, BastService $service): RedirectResponse
    {
        $service->finalize($bast, request()->user());

        return back()->with('status', 'BAST berhasil difinalisasi.');
    }
}
