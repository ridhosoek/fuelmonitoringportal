@extends('layouts.app', ['pageTitle' => 'Unit Monitoring'])

@section('actions')
    <a href="{{ route('units.export-pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
    </a>
@endsection

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body filter-grid">
            <select name="meter_type" class="form-select" aria-label="Meter type">
                <option value="">All Meter</option>
                @foreach(['KM', 'HM'] as $type)
                    <option value="{{ $type }}" @selected(request('meter_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <select name="unit_type" class="form-select" aria-label="Unit type">
                <option value="">All Unit Type</option>
                @foreach($unitTypes as $type)
                    <option value="{{ $type }}" @selected(request('unit_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select" aria-label="Status">
                <option value="">All Status</option>
                @foreach(['ACTIVE', 'INACTIVE'] as $status)
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
                        <th>Unit</th><th>Type</th><th>Meter</th><th class="text-end">Fuel</th><th class="text-end">Count</th><th class="text-end">Working KM/HM</th><th class="text-end">Efficiency</th><th>Last</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaries as $summary)
                        <tr>
                            <td>{{ $summary['unit']->unit_code }} - {{ $summary['unit']->unit_name }}</td>
                            <td>{{ $summary['unit']->unit_type }}</td>
                            <td>{{ $summary['unit']->meter_type }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($summary['fuel']) }}</td>
                            <td class="text-end">{{ $summary['refueling_count'] }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($summary['distance_or_hours']) }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($summary['efficiency']) }} {{ $summary['efficiency_label'] }}</td>
                            <td>{{ $summary['last_refueling']?->toDateString() ?: '-' }}</td>
                            <td>@include('partials.status', ['status' => $summary['unit']->status])</td>
                            <td class="text-end">
                                <a href="{{ route('units.show', $summary['unit']) }}" class="btn btn-outline-secondary btn-sm" title="Open"><i class="bi bi-arrow-right"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-secondary py-4">No unit data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
