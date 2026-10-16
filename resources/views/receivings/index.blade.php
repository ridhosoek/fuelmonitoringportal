@extends('layouts.app', ['pageTitle' => 'Fuel Receiving'])

@section('actions')
    @if(auth()->user()->hasRole(['super_admin', 'vhs_admin']))
        <a href="{{ route('receivings.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Receiving</a>
    @endif
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body filter-grid">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="Date from">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="Date to">
            <select name="status" class="form-select" aria-label="Status">
                <option value="">All Status</option>
                @foreach(['DRAFT', 'POSTED', 'REVERSED'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="storage_id" class="form-select" aria-label="VHS">
                <option value="">All VHS</option>
                @foreach($storages as $storage)
                    <option value="{{ $storage->id }}" @selected((string) request('storage_id') === (string) $storage->id)>{{ $storage->terminal?->terminal_name ?: 'Tanpa Terminal' }} / {{ $storage->storage_name }}</option>
                @endforeach
            </select>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th><th>No</th><th>Terminal / VHS</th><th>Source</th><th>Delivery Doc</th><th class="text-end">Liter</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receivings as $receiving)
                        <tr>
                            <td>{{ $receiving->receiving_date?->toDateString() }}</td>
                            <td>{{ $receiving->receiving_number }}</td>
                            <td>{{ $receiving->storage?->terminal?->terminal_name ?: '-' }} / {{ $receiving->storage?->storage_name }}</td>
                            <td>{{ $receiving->fuelSource?->name }}</td>
                            <td>{{ $receiving->delivery_document_number ?: '-' }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($receiving->quantity_liter) }}</td>
                            <td>@include('partials.status', ['status' => $receiving->status])</td>
                            <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('receivings.show', $receiving) }}" title="Open">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4">No receiving data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $receivings->links() }}</div>
    </div>
@endsection
