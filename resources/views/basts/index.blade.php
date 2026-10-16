@extends('layouts.app', ['pageTitle' => 'BAST'])

@section('actions')
    @if(auth()->user()->canWriteTransactions())
        <a href="{{ route('basts.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New BAST</a>
    @endif
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body filter-grid">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="Date from">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="Date to">
            <select name="status" class="form-select" aria-label="Status">
                <option value="">All Status</option>
                @foreach(['DRAFT', 'FINAL', 'CANCELLED'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
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
                        <th>Date</th><th>BAST No</th><th>FO</th><th>Customer</th><th>Received By</th><th class="text-end">Liter</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($basts as $bast)
                        <tr>
                            <td>{{ $bast->bast_date?->toDateString() }}</td>
                            <td>{{ $bast->bast_number }}</td>
                            <td>{{ $bast->fuelOrder?->fo_number }}</td>
                            <td>{{ $bast->customer?->customer_name }}</td>
                            <td>{{ $bast->received_by }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($bast->quantity_liter) }}</td>
                            <td>@include('partials.status', ['status' => $bast->status])</td>
                            <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('basts.show', $bast) }}" title="Open">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4">No BAST data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $basts->links() }}</div>
    </div>
@endsection
