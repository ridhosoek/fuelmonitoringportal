@extends('layouts.app', ['pageTitle' => 'Reports'])

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body filter-grid report-filter-grid">
            <select name="report" class="form-select report-type-control" aria-label="Report">
                @foreach([
                    'operational-usage' => 'Rekap Operasional Tahunan',
                    'unit-usage' => 'Pemakaian BBM per Unit',
                    'receiving' => 'Receiving',
                    'dispensing' => 'Dispensing',
                    'stock-movement' => 'Stock Movement',
                    'unit-consumption' => 'Unit Consumption',
                    'fo-reconciliation' => 'FO Reconciliation',
                    'daily-reconciliation' => 'Daily Reconciliation',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected($report === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if($report === 'operational-usage')
                <input type="number" name="year" value="{{ request('year', now()->year) }}" min="2020" max="2100" class="form-control" aria-label="Year">
                <select name="site_id" class="form-select" aria-label="Site">
                    <option value="">All Site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected((string) request('site_id') === (string) $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
                <select name="storage_id" class="form-select" aria-label="VHS">
                    <option value="">All VHS</option>
                    @foreach($storages as $storage)
                        <option value="{{ $storage->id }}" @selected((string) request('storage_id') === (string) $storage->id)>{{ $storage->terminal?->terminal_name ?: 'Tanpa Terminal' }} / {{ $storage->storage_name }}</option>
                    @endforeach
                </select>
            @elseif($report === 'unit-usage')
                <input type="date" name="date_from" value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}" class="form-control" aria-label="Date from">
                <input type="date" name="date_to" value="{{ request('date_to', now()->toDateString()) }}" class="form-control" aria-label="Date to">
                <select name="period" class="form-select" aria-label="Period grouping">
                    @foreach(['daily' => 'Per Hari', 'weekly' => 'Per Minggu', 'monthly' => 'Per Bulan'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('period', 'daily') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="unit_id" class="form-select" aria-label="Unit">
                    <option value="">Semua Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" @selected((string) request('unit_id') === (string) $unit->id)>{{ $unit->unit_code }} - {{ $unit->unit_name }}</option>
                    @endforeach
                </select>
                <select name="site_id" class="form-select" aria-label="Site">
                    <option value="">Semua Site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected((string) request('site_id') === (string) $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
                <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="form-control" aria-label="PDF month">
            @else
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="Date from">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="Date to">
            @endif
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>View</button>
            <button type="submit" name="export" value="1" class="btn btn-success"><i class="bi bi-download me-1"></i>Export XLSX</button>
            @if($report === 'unit-usage')
                <button type="submit" name="download" value="pdf" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Report PDF Bulanan</button>
            @endif
        </div>
    </form>

    @if($report === 'operational-usage')
        @include('reports.operational-usage', ['reportData' => $operationalReport])
    @elseif($report === 'unit-usage')
        @include('reports.unit-usage', ['reportData' => $unitUsageReport])
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        @foreach($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td @class(['text-end' => is_numeric($cell)])>
                                    {{ is_numeric($cell) ? \App\Support\QuantityFormatter::format($cell) : ($cell ?: '-') }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($headers) }}" class="text-center text-secondary py-4">No report data.</td></tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
