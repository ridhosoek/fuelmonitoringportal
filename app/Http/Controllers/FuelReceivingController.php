<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuelReceivingRequest;
use App\Models\FuelReceiving;
use App\Models\FuelSource;
use App\Models\Site;
use App\Models\Storage;
use App\Services\FuelReceivingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage as FileStorage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FuelReceivingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'in:DRAFT,POSTED,REVERSED'],
            'storage_id' => ['nullable', 'exists:storages,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $receivings = FuelReceiving::query()
            ->with(['site', 'storage.terminal', 'fuelSource'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('receiving_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('receiving_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['storage_id'] ?? null, fn ($query, $storageId) => $query->where('storage_id', $storageId))
            ->when($filters['q'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('receiving_number', 'like', '%'.$search.'%')
                        ->orWhere('delivery_document_number', 'like', '%'.$search.'%')
                        ->orWhere('truck_plate_number', 'like', '%'.$search.'%')
                        ->orWhere('driver_name', 'like', '%'.$search.'%');
                });
            })
            ->latest('receiving_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('receivings.index', [
            'receivings' => $receivings,
            'storages' => Storage::query()->with('terminal')->orderBy('storage_name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('receivings.create', [
            'sites' => Site::query()->where('status', 'ACTIVE')->orderBy('site_name')->get(),
            'storages' => Storage::query()->with('terminal')->where('status', 'ACTIVE')->orderBy('storage_name')->get(),
            'fuelSources' => FuelSource::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreFuelReceivingRequest $request, FuelReceivingService $service): RedirectResponse
    {
        $data = $request->validated();
        $submissionAction = $data['submission_action'] ?? 'post';

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('attachments/receivings', 'private');
        }

        unset($data['attachment'], $data['submission_action']);

        $receiving = $submissionAction === 'draft'
            ? $service->createDraft($data, $request->user())
            : $service->createAndPost($data, $request->user());

        $message = $submissionAction === 'draft'
            ? 'Fuel receiving berhasil disimpan sebagai DRAFT.'
            : 'Fuel receiving berhasil diposting dan stok bertambah.';

        return redirect()->route('receivings.show', $receiving)->with('status', $message);
    }

    public function show(FuelReceiving $receiving): View
    {
        return view('receivings.show', [
            'receiving' => $receiving->load(['site', 'storage', 'fuelSource', 'creator', 'poster', 'reverser']),
        ]);
    }

    public function post(FuelReceiving $receiving, FuelReceivingService $service): RedirectResponse
    {
        $service->post($receiving, request()->user());

        return back()->with('status', 'Fuel receiving berhasil diposting dan stock bertambah.');
    }

    public function reverse(Request $request, FuelReceiving $receiving, FuelReceivingService $service): RedirectResponse
    {
        $data = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->reverse($receiving, $request->user(), $data['reversal_reason']);

        return back()->with('status', 'Fuel receiving berhasil direverse.');
    }

    public function downloadAttachment(FuelReceiving $receiving): StreamedResponse
    {
        if (! $receiving->attachment_path || ! FileStorage::disk('private')->exists($receiving->attachment_path)) {
            throw ValidationException::withMessages([
                'attachment' => 'Attachment tidak ditemukan.',
            ]);
        }

        return FileStorage::disk('private')->download($receiving->attachment_path);
    }
}
