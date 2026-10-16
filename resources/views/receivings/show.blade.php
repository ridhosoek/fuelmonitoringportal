@extends('layouts.app', ['pageTitle' => $receiving->receiving_number])

@section('actions')
    <a href="{{ route('receivings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="section-title mb-0">Receiving Detail</h2>
                    @include('partials.status', ['status' => $receiving->status])
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach([
                            'Date' => $receiving->receiving_date?->toDateString().' '.$receiving->receiving_time,
                            'Site' => $receiving->site?->site_name,
                            'Terminal' => $receiving->storage?->terminal?->terminal_name,
                            'VHS' => $receiving->storage?->storage_name,
                            'Fuel Source' => $receiving->fuelSource?->name,
                            'Delivery Document' => $receiving->delivery_document_number ?: '-',
                            'Truck Plate' => $receiving->truck_plate_number ?: '-',
                            'Driver' => $receiving->driver_name ?: '-',
                            'Quantity Liter' => \App\Support\QuantityFormatter::format($receiving->quantity_liter),
                        ] as $label => $value)
                            <div class="col-md-6">
                                <div class="metric-label">{{ $label }}</div>
                                <div class="metric-value">{{ $value }}</div>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <div class="metric-label">Notes</div>
                            <div>{{ $receiving->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h2 class="section-title mb-0">Control</h2></div>
                <div class="card-body d-grid gap-2">
                    @if($receiving->attachment_path)
                        <a href="{{ route('receivings.attachment', $receiving) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-paperclip me-1"></i>Attachment
                        </a>
                    @endif
                    @if(auth()->user()->hasRole(['super_admin', 'vhs_admin']) && $receiving->status === 'DRAFT')
                        <form method="POST" action="{{ route('receivings.post', $receiving) }}">
                            @csrf
                            <button class="btn btn-success w-100" type="submit"><i class="bi bi-check2-circle me-1"></i>Post Receiving</button>
                        </form>
                    @endif
                    @if(auth()->user()->canOverrideOperationalControl() && $receiving->status === 'POSTED')
                        <form method="POST" action="{{ route('receivings.reverse', $receiving) }}">
                            @csrf
                            <textarea name="reversal_reason" rows="3" class="form-control mb-2" placeholder="Reversal reason" required></textarea>
                            <button class="btn btn-danger w-100" type="submit"><i class="bi bi-arrow-counterclockwise me-1"></i>Reverse</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="section-title mb-0">Audit Marks</h2></div>
                <div class="card-body">
                    <div class="small text-secondary">Created by</div>
                    <div class="mb-2">{{ $receiving->creator?->name ?: '-' }}</div>
                    <div class="small text-secondary">Posted by</div>
                    <div class="mb-2">{{ $receiving->poster?->name ?: '-' }} {{ $receiving->posted_at?->format('Y-m-d H:i') }}</div>
                    <div class="small text-secondary">Reversed by</div>
                    <div>{{ $receiving->reverser?->name ?: '-' }} {{ $receiving->reversed_at?->format('Y-m-d H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
