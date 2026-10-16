@extends('layouts.app', ['pageTitle' => 'Fuel Order'])

@section('actions')
    @if(auth()->user()->canCreateFuelOrders())
        <a href="{{ route('fuel-orders.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New FO</a>
    @endif
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body filter-grid">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="Date from">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="Date to">
            <input type="date" name="planned_date" value="{{ request('planned_date') }}" class="form-control" aria-label="Planned delivery date">
            <select name="status" class="form-select" aria-label="Status">
                <option value="">All Status</option>
                @foreach(['DRAFT', 'OPEN', 'PARTIAL', 'COMPLETED', 'CANCELLED'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="site_id" class="form-select" aria-label="Site">
                <option value="">All Site</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" @selected((string) request('site_id') === (string) $site->id)>{{ $site->site_name }}</option>
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
                        <th>Request</th><th>Rencana Isi</th><th>FO No</th><th>Customer</th><th>Site</th><th>Requested By</th><th class="text-end">Requested</th><th class="text-end">Dispensed</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fuelOrders as $fuelOrder)
                        <tr>
                            <td>{{ $fuelOrder->fo_date?->toDateString() }}</td>
                            <td>{{ $fuelOrder->planned_delivery_date?->toDateString() ?: '-' }}</td>
                            <td>{{ $fuelOrder->fo_number }}</td>
                            <td>{{ $fuelOrder->customer?->customer_name }}</td>
                            <td>{{ $fuelOrder->site?->site_name }}</td>
                            <td>{{ $fuelOrder->requested_by }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($fuelOrder->totalRequested()) }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($fuelOrder->totalDispensed()) }}</td>
                            <td>@include('partials.status', ['status' => $fuelOrder->status])</td>
                            <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('fuel-orders.show', $fuelOrder) }}" title="Open">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-secondary py-4">No fuel order data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $fuelOrders->links() }}</div>
    </div>
@endsection
