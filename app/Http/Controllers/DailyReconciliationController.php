<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyReconciliationRequest;
use App\Models\DailyReconciliation;
use App\Models\Storage;
use App\Services\ReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyReconciliationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'storage_id' => ['nullable', 'exists:storages,id'],
            'status' => ['nullable', 'in:DRAFT,SUBMITTED,APPROVED'],
        ]);

        $reconciliations = DailyReconciliation::query()
            ->with(['storage.site', 'storage.terminal', 'preparer', 'approver'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('reconciliation_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('reconciliation_date', '<=', $date))
            ->when($filters['storage_id'] ?? null, fn ($query, $storageId) => $query->where('storage_id', $storageId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('reconciliation_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('reconciliations.index', [
            'reconciliations' => $reconciliations,
            'storages' => Storage::query()->with('terminal')->orderBy('storage_name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('reconciliations.create', [
            'storages' => Storage::query()->with(['site', 'terminal'])->where('status', 'ACTIVE')->orderBy('storage_name')->get(),
        ]);
    }

    public function store(StoreDailyReconciliationRequest $request, ReconciliationService $service): RedirectResponse
    {
        $reconciliation = $service->createDraft($request->validated(), $request->user());

        return redirect()->route('reconciliations.show', $reconciliation)->with('status', 'Daily reconciliation berhasil dihitung sebagai DRAFT.');
    }

    public function show(DailyReconciliation $reconciliation): View
    {
        return view('reconciliations.show', [
            'reconciliation' => $reconciliation->load(['storage.site', 'storage.terminal', 'preparer', 'approver']),
        ]);
    }

    public function submit(DailyReconciliation $reconciliation, ReconciliationService $service): RedirectResponse
    {
        $service->submit($reconciliation, request()->user());

        return back()->with('status', 'Daily reconciliation berhasil disubmit.');
    }

    public function approve(DailyReconciliation $reconciliation, ReconciliationService $service): RedirectResponse
    {
        $service->approve($reconciliation, request()->user());

        return back()->with('status', 'Daily reconciliation berhasil diapprove.');
    }
}
