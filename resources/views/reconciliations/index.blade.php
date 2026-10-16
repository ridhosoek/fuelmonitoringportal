@extends('layouts.app', ['pageTitle' => 'Daily Reconciliation'])

@section('actions')
    @if(auth()->user()->canWriteTransactions())
        <a href="{{ route('reconciliations.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Reconciliation</a>
    @endif
@endsection

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
            <select name="status" class="form-select" aria-label="Status">
                <option value="">All Status</option>
                @foreach(['DRAFT', 'SUBMITTED', 'APPROVED'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th><th>Terminal / VHS</th><th class="text-end">System Closing</th><th class="text-end">Physical</th><th class="text-end">Variance</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations as $reconciliation)
                        <tr>
                            <td>{{ $reconciliation->reconciliation_date?->toDateString() }}</td>
                            <td>{{ $reconciliation->storage?->terminal?->terminal_name ?: '-' }} / {{ $reconciliation->storage?->storage_name }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($reconciliation->system_closing_stock) }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($reconciliation->physical_closing_stock) }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($reconciliation->variance) }}</td>
                            <td>@include('partials.status', ['status' => $reconciliation->status])</td>
                            <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reconciliations.show', $reconciliation) }}" title="Open">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No reconciliation data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $reconciliations->links() }}</div>
    </div>
@endsection
