@extends('layouts.app', ['pageTitle' => 'Stock Ledger'])

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body filter-grid">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="Date from">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="Date to">
            <select name="storage_id" class="form-select" aria-label="VHS">
                <option value="">All VHS</option>
                @foreach($storages as $storage)
                    <option value="{{ $storage->id }}" @selected((string) request('storage_id') === (string) $storage->id)>{{ $storage->terminal?->terminal_name ?: 'Tanpa Terminal' }} / {{ $storage->storage_name }}</option>
                @endforeach
            </select>
            <select name="movement_type" class="form-select" aria-label="Movement type">
                <option value="">All Type</option>
                @foreach(['RECEIVING', 'DISPENSING', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'REVERSAL'] as $type)
                    <option value="{{ $type }}" @selected(request('movement_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Reference">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th><th>Time</th><th>Terminal / VHS</th><th>Type</th><th>Reference</th><th class="text-end">Quantity</th><th class="text-end">Balance</th><th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td>{{ $movement->movement_date?->toDateString() }}</td>
                            <td>{{ $movement->movement_time }}</td>
                            <td>{{ $movement->storage?->terminal?->terminal_name ?: '-' }} / {{ $movement->storage?->storage_name }}</td>
                            <td>{{ $movement->movement_type }}</td>
                            <td>{{ $movement->reference_number }}</td>
                            <td class="text-end @if((float) $movement->quantity_liter < 0) text-danger @else text-success @endif">{{ \App\Support\QuantityFormatter::format($movement->quantity_liter) }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($movement->balance_after) }}</td>
                            <td>{{ $movement->creator?->name ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4">No stock movement data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $movements->links() }}</div>
    </div>
@endsection
