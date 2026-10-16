@extends('layouts.app', ['pageTitle' => $summary['unit']->unit_code])

@section('actions')
    <div class="d-flex gap-2">
        <a href="{{ route('units.export-unit-pdf', array_merge(['unit' => $summary['unit']], request()->only(['date_from', 'date_to', 'period']))) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Export PDF</a>
        <a href="{{ route('units.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="section-title mb-0">Unit Profile</h2>
                    @include('partials.status', ['status' => $summary['unit']->status])
                </div>
                <div class="card-body">
                    <div class="metric-label">Name</div>
                    <div class="metric-value mb-2">{{ $summary['unit']->unit_name }}</div>
                    <div class="metric-label">Customer</div>
                    <div class="mb-2">{{ $summary['unit']->customer?->customer_name }}</div>
                    <div class="metric-label">Site</div>
                    <div class="mb-2">{{ $summary['unit']->site?->site_name }}</div>
                    <div class="metric-label">Type</div>
                    <div class="mb-2">{{ $summary['unit']->unit_type }}</div>
                    <div class="metric-label">Meter</div>
                    <div>{{ $summary['unit']->meter_type }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <form method="GET" class="card mb-3">
                <div class="card-body filter-grid">
                    <input type="date" name="date_from" value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}" class="form-control" aria-label="Date from">
                    <input type="date" name="date_to" value="{{ request('date_to', now()->toDateString()) }}" class="form-control" aria-label="Date to">
                    <select name="period" class="form-select" aria-label="Period grouping">
                        @foreach(['daily' => 'Per Hari', 'weekly' => 'Per Minggu', 'monthly' => 'Per Bulan'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('period', 'daily') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Tampilkan</button>
                </div>
            </form>
            <div class="row g-3 mb-3">
                @foreach([
                    ['Fuel', \App\Support\QuantityFormatter::format($summary['fuel']).' L'],
                    ['Refueling', $summary['refueling_count']],
                    ['Working KM/HM', \App\Support\QuantityFormatter::format($summary['distance_or_hours'])],
                    ['Efficiency', \App\Support\QuantityFormatter::format($summary['efficiency']).' '.$summary['efficiency_label']],
                ] as $metric)
                    <div class="col-sm-6 col-xl-3">
                        <div class="card"><div class="card-body"><div class="metric-label">{{ $metric[0] }}</div><div class="metric-value">{{ $metric[1] }}</div></div></div>
                    </div>
                @endforeach
            </div>
            <div class="card mb-3">
                <div class="card-header"><h2 class="section-title mb-0">Pemakaian per {{ ucfirst($usageReport['period']) }}</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Periode</th><th class="text-end">BBM</th><th class="text-end">Transaksi</th><th class="text-end">Working</th><th class="text-end">Produksi</th><th class="text-end">L/Jam</th><th class="text-end">Langsung</th><th class="text-end">Mobil Tangki</th></tr></thead>
                        <tbody>
                            @forelse($usageReport['rows'] as $row)
                                <tr>
                                    <td>{{ $row['period_label'] }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($row['fuel']) }}</td>
                                    <td class="text-end">{{ $row['refueling_count'] }}</td>
                                    <td class="text-end">{{ $row['working_hours'] === null ? '-' : \App\Support\QuantityFormatter::format($row['working_hours']) }}</td>
                                    <td class="text-end">{{ $row['production'] === null ? '-' : \App\Support\QuantityFormatter::format($row['production']) }}</td>
                                    <td class="text-end">{{ $row['liter_per_hour'] === null ? '-' : \App\Support\QuantityFormatter::format($row['liter_per_hour']) }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($row['direct_fuel']) }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($row['mobile_tanker_fuel']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-secondary py-4">Belum ada pemakaian pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="section-title mb-0">Dispensing History</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>No</th><th>FO</th><th>Terminal/VHS</th><th>Mekanisme</th><th class="text-end">Meter Awal</th><th class="text-end">Meter Akhir</th><th class="text-end">Working</th><th class="text-end">Liter</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($dispensings as $dispensing)
                                <tr>
                                    <td>{{ $dispensing->dispensing_date?->toDateString() }}</td>
                                    <td><a href="{{ route('dispensings.show', $dispensing) }}">{{ $dispensing->dispensing_number }}</a></td>
                                    <td>{{ $dispensing->fuelOrder?->fo_number }}</td>
                                    <td>{{ $dispensing->storage?->terminal?->terminal_name }} / {{ $dispensing->storage?->storage_name }}</td>
                                    <td>{{ $dispensing->delivery_method === 'MOBILE_TANKER' ? 'Mobil Tangki' : 'Langsung Terminal' }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($dispensing->previous_meter_value) }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($dispensing->meter_value) }}</td>
                                    <td class="text-end">{{ $dispensing->workingHours() === null ? '-' : \App\Support\QuantityFormatter::format($dispensing->workingHours()) }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($dispensing->quantity_liter) }}</td>
                                    <td>@include('partials.status', ['status' => $dispensing->status])</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-secondary py-4">No dispensing data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $dispensings->links() }}</div>
            </div>
        </div>
    </div>
@endsection
