<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuelDispensingRequest;
use App\Models\FuelDispensing;
use App\Models\FuelOrder;
use App\Models\FuelOrderItem;
use App\Models\FuelTruck;
use App\Models\Storage;
use App\Models\Unit;
use App\Services\FuelDispensingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage as FileStorage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class FuelDispensingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'in:DRAFT,POSTED,REVERSED'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'storage_id' => ['nullable', 'exists:storages,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $dispensings = FuelDispensing::query()
            ->with(['fuelOrder', 'fuelOrderItem.unit', 'storage.terminal', 'unit', 'fuelTruck'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('dispensing_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('dispensing_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['unit_id'] ?? null, fn ($query, $unitId) => $query->where('unit_id', $unitId))
            ->when($filters['storage_id'] ?? null, fn ($query, $storageId) => $query->where('storage_id', $storageId))
            ->when($filters['q'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('dispensing_number', 'like', '%'.$search.'%')
                        ->orWhere('operator_name', 'like', '%'.$search.'%');
                });
            })
            ->latest('dispensing_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('dispensings.index', [
            'dispensings' => $dispensings,
            'units' => Unit::query()->orderBy('unit_code')->get(),
            'storages' => Storage::query()->with('terminal')->orderBy('storage_name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $orders = FuelOrder::query()
            ->with([
                'customer',
                'site',
                'items' => fn ($query) => $query
                    ->where('status', 'ACTIVE')
                    ->whereHas('unit', fn ($query) => $query->where('status', 'ACTIVE'))
                    ->with('unit')
                    ->withSum([
                        'dispensings as posted_quantity' => fn ($query) => $query->where('status', FuelDispensing::STATUS_POSTED),
                    ], 'quantity_liter'),
            ])
            ->activeForDispensing()
            ->latest('fo_date')
            ->latest('id')
            ->get();

        $unitIds = $orders->flatMap(fn (FuelOrder $order) => $order->items->pluck('unit_id'))->unique()->values();
        $lastMeterByUnit = Unit::query()
            ->whereKey($unitIds->all())
            ->addSelect([
                'last_meter_value' => FuelDispensing::query()
                    ->select('meter_value')
                    ->whereColumn('fuel_dispensings.unit_id', 'units.id')
                    ->where('status', FuelDispensing::STATUS_POSTED)
                    ->latest('dispensing_date')
                    ->latest('id')
                    ->limit(1),
            ])
            ->pluck('last_meter_value', 'id');
        $orderItems = $orders->mapWithKeys(function (FuelOrder $order) use ($lastMeterByUnit): array {
            $items = $order->items
                ->map(function (FuelOrderItem $item) use ($lastMeterByUnit): array {
                    $requestedQuantity = (float) $item->requested_quantity;
                    $dispensedQuantity = (float) ($item->posted_quantity ?? 0);

                    return [
                        'id' => $item->id,
                        'unit_code' => $item->unit?->unit_code,
                        'unit_name' => $item->unit?->unit_name,
                        'meter_type' => $item->unit?->meter_type,
                        'production_unit' => $item->unit?->production_unit,
                        'delivery_method' => $item->delivery_method,
                        'requested_quantity' => $requestedQuantity,
                        'dispensed_quantity' => $dispensedQuantity,
                        'remaining_quantity' => max(0, $requestedQuantity - $dispensedQuantity),
                        'last_meter_value' => $lastMeterByUnit->get($item->unit_id),
                    ];
                })
                ->filter(fn (array $item): bool => $item['remaining_quantity'] > 0)
                ->values();

            return [(string) $order->id => $items];
        });

        return view('dispensings.create', [
            'orders' => $orders,
            'orderItems' => $orderItems,
            'storages' => Storage::query()->with('terminal')->where('status', 'ACTIVE')->orderBy('storage_name')->get(),
            'fuelTrucks' => FuelTruck::query()->active()->orderBy('truck_code')->get(),
        ]);
    }

    public function store(StoreFuelDispensingRequest $request, FuelDispensingService $service): RedirectResponse
    {
        $data = $request->validated();
        $submissionAction = $data['submission_action'] ?? 'post';
        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments/dispensings', 'private');
            $data['attachment_path'] = $attachmentPath;
        }

        unset($data['attachment'], $data['submission_action']);

        try {
            if (isset($data['items'])) {
                $dispensings = $service->createBatch($data, $request->user(), $submissionAction !== 'draft');
                $count = $dispensings->count();
                $message = $submissionAction === 'draft'
                    ? "$count fuel dispensing berhasil disimpan sebagai DRAFT."
                    : "$count fuel dispensing berhasil diposting dan stok berkurang.";

                return redirect()->route('dispensings.index')->with('status', $message);
            }

            $dispensing = $submissionAction === 'draft'
                ? $service->createDraft($data, $request->user())
                : $service->createAndPost($data, $request->user());
        } catch (Throwable $exception) {
            if ($attachmentPath !== null) {
                FileStorage::disk('private')->delete($attachmentPath);
            }

            throw $exception;
        }

        $message = $submissionAction === 'draft'
            ? 'Fuel dispensing berhasil disimpan sebagai DRAFT.'
            : 'Fuel dispensing berhasil diposting dan stok berkurang.';

        return redirect()->route('dispensings.show', $dispensing)->with('status', $message);
    }

    public function show(FuelDispensing $dispensing): View
    {
        return view('dispensings.show', [
            'dispensing' => $dispensing->load(['fuelOrder', 'fuelOrderItem.unit', 'storage.terminal', 'unit', 'fuelTruck', 'creator', 'poster', 'approver', 'reverser', 'bastItem.bast']),
        ]);
    }

    public function post(FuelDispensing $dispensing, FuelDispensingService $service): RedirectResponse
    {
        $service->post($dispensing, request()->user());

        return back()->with('status', 'Fuel dispensing berhasil diposting dan stock berkurang.');
    }

    public function reverse(Request $request, FuelDispensing $dispensing, FuelDispensingService $service): RedirectResponse
    {
        $data = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->reverse($dispensing, $request->user(), $data['reversal_reason']);

        return back()->with('status', 'Fuel dispensing berhasil direverse.');
    }

    public function downloadAttachment(FuelDispensing $dispensing): StreamedResponse
    {
        if (! $dispensing->attachment_path || ! FileStorage::disk('private')->exists($dispensing->attachment_path)) {
            throw ValidationException::withMessages([
                'attachment' => 'Attachment tidak ditemukan.',
            ]);
        }

        return FileStorage::disk('private')->download($dispensing->attachment_path);
    }
}
