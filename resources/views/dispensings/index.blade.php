@extends('layouts.app', ['pageTitle' => 'Fuel Dispensing'])

@section('actions')
    @if(auth()->user()->canWriteTransactions())
        <a href="{{ route('dispensings.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Dispensing</a>
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
            <select name="unit_id" class="form-select" aria-label="Unit">
                <option value="">All Unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" @selected((string) request('unit_id') === (string) $unit->id)>{{ $unit->unit_code }}</option>
                @endforeach
            </select>
            <select name="storage_id" class="form-select" aria-label="VHS">
                <option value="">All VHS</option>
                @foreach($storages as $storage)
                    <option value="{{ $storage->id }}" @selected((string) request('storage_id') === (string) $storage->id)>{{ $storage->storage_name }}</option>
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
                        <th>Date</th><th>No</th><th>FO</th><th>Unit</th><th>VHS</th><th>Mekanisme</th><th>Meter Awal-Akhir</th><th class="text-end">Working Hours</th><th class="text-end">Liter</th><th class="text-end">L/Jam</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dispensings as $dispensing)
                        <tr>
                            <td>{{ $dispensing->dispensing_date?->toDateString() }}</td>
                            <td>{{ $dispensing->dispensing_number }}</td>
                            <td>{{ $dispensing->fuelOrder?->fo_number }}</td>
                            <td>{{ $dispensing->unit?->unit_code }}</td>
                            <td>{{ $dispensing->storage?->storage_name }}</td>
                            <td>{{ $dispensing->delivery_method === 'MOBILE_TANKER' ? 'Mobil Tangki' : 'Langsung Terminal' }}</td>
                            <td>{{ $dispensing->meter_type }} {{ \App\Support\QuantityFormatter::format($dispensing->previous_meter_value) }} - {{ \App\Support\QuantityFormatter::format($dispensing->meter_value) }}</td>
                            <td class="text-end">{{ $dispensing->workingHours() === null ? '-' : \App\Support\QuantityFormatter::format($dispensing->workingHours()) }}</td>
                            <td class="text-end">{{ \App\Support\QuantityFormatter::format($dispensing->quantity_liter) }}</td>
                            <td class="text-end">{{ $dispensing->literPerHour() === null ? '-' : \App\Support\QuantityFormatter::format($dispensing->literPerHour()) }}</td>
                            <td>@include('partials.status', ['status' => $dispensing->status])</td>
                            <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('dispensings.show', $dispensing) }}" title="Open">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-secondary py-4">No dispensing data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $dispensings->links() }}</div>
    </div>
@endsection
