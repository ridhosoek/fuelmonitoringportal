@extends('layouts.app', ['pageTitle' => 'Current Stock'])

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body filter-grid">
            <select name="storage_id" class="form-select" aria-label="VHS">
                <option value="">All VHS</option>
                @foreach($storages as $storage)
                    <option value="{{ $storage->id }}" @selected((string) request('storage_id') === (string) $storage->id)>{{ $storage->terminal?->terminal_name ?: 'Tanpa Terminal' }} / {{ $storage->storage_name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
    </form>

    <div class="row g-3">
        @forelse($stockRows as $row)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2 mb-3">
                            <div>
                                <div class="metric-label">{{ $row['storage']->terminal?->terminal_name ?: $row['storage']->site?->site_name }}</div>
                                <div class="metric-value">{{ $row['storage']->storage_name }}</div>
                            </div>
                            <span @class([
                                'badge align-self-start',
                                'text-bg-danger' => $row['status'] === \App\Models\Storage::STOCK_STATUS_EMPTY,
                                'text-bg-warning' => $row['status'] === \App\Models\Storage::STOCK_STATUS_LOW,
                                'text-bg-success' => $row['status'] === \App\Models\Storage::STOCK_STATUS_OK,
                            ])>{{ $row['status'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Stock</span>
                            <strong>{{ \App\Support\QuantityFormatter::format($row['stock']) }} L</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Minimum</span>
                            <span>{{ \App\Support\QuantityFormatter::format($row['minimum']) }} L</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Capacity</span>
                            <span>{{ \App\Support\QuantityFormatter::format($row['capacity']) }} L</span>
                        </div>
                        <div class="progress" role="progressbar" aria-valuenow="{{ min(100, $row['fill_rate']) }}" aria-valuemin="0" aria-valuemax="100">
                            <div @class([
                                'progress-bar',
                                'bg-danger' => $row['status'] === \App\Models\Storage::STOCK_STATUS_EMPTY,
                                'bg-warning' => $row['status'] === \App\Models\Storage::STOCK_STATUS_LOW,
                                'bg-success' => $row['status'] === \App\Models\Storage::STOCK_STATUS_OK,
                            ]) style="width: {{ min(100, max(0, $row['fill_rate'])) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body text-center text-secondary">No VHS data.</div></div>
            </div>
        @endforelse
    </div>
@endsection
