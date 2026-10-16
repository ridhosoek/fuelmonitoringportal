@extends('layouts.app', ['pageTitle' => $fuelOrder->fo_number])

@section('actions')
    <a href="{{ route('fuel-orders.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="section-title mb-0">FO Header</h2>
                    @include('partials.status', ['status' => $fuelOrder->status])
                </div>
                <div class="card-body">
                    <div class="metric-label">FO Date</div>
                    <div class="metric-value mb-2">{{ $fuelOrder->fo_date?->toDateString() }}</div>
                    <div class="metric-label">Jam Permintaan</div>
                    <div class="mb-2">{{ $fuelOrder->requested_time ?: '-' }}</div>
                    <div class="metric-label">Rencana Pengisian</div>
                    <div class="metric-value mb-2">{{ $fuelOrder->planned_delivery_date?->toDateString() ?: '-' }}</div>
                    <div class="metric-label">Customer</div>
                    <div class="mb-2">{{ $fuelOrder->customer?->customer_name }}</div>
                    <div class="metric-label">Site</div>
                    <div class="mb-2">{{ $fuelOrder->site?->site_name }}</div>
                    <div class="metric-label">Requested By</div>
                    <div class="mb-2">{{ $fuelOrder->requested_by }}</div>
                    <div class="metric-label">Notes</div>
                    <div>{{ $fuelOrder->notes ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h2 class="section-title mb-0">FO Items</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Unit</th><th>Mobilitas</th><th>Mekanisme</th><th>Meter</th><th class="text-end">Requested</th><th class="text-end">Dispensed</th><th class="text-end">Remaining</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fuelOrder->items as $item)
                                <tr>
                                    <td>{{ $item->unit?->unit_code }} - {{ $item->unit?->unit_name }}</td>
                                    <td>{{ $item->unit?->mobility_type === 'STATIONARY' ? 'Tidak Bergerak' : 'Bergerak' }}</td>
                                    <td>{{ $item->delivery_method === 'MOBILE_TANKER' ? 'Mobil Tangki' : 'Langsung Terminal' }}</td>
                                    <td>{{ $item->unit?->meter_type }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($item->requested_quantity) }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($item->dispensedQuantity()) }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($service->remainingForItem($item)) }}</td>
                                    <td>@include('partials.status', ['status' => $item->status])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h2 class="section-title mb-0">Dispensing</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>No</th><th>Unit</th><th class="text-end">Liter</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($fuelOrder->dispensings as $dispensing)
                                <tr>
                                    <td>{{ $dispensing->dispensing_date?->toDateString() }}</td>
                                    <td><a href="{{ route('dispensings.show', $dispensing) }}">{{ $dispensing->dispensing_number }}</a></td>
                                    <td>{{ $dispensing->unit?->unit_code }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($dispensing->quantity_liter) }}</td>
                                    <td>@include('partials.status', ['status' => $dispensing->status])</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-4">No dispensing data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="section-title mb-0">BAST</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>No</th><th class="text-end">Liter</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($fuelOrder->basts as $bast)
                                <tr>
                                    <td>{{ $bast->bast_date?->toDateString() }}</td>
                                    <td><a href="{{ route('basts.show', $bast) }}">{{ $bast->bast_number }}</a></td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($bast->quantity_liter) }}</td>
                                    <td>@include('partials.status', ['status' => $bast->status])</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary py-4">No BAST data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
