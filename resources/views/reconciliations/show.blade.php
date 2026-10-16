@extends('layouts.app', ['pageTitle' => 'Reconciliation '.$reconciliation->reconciliation_date?->toDateString()])

@section('actions')
    <a href="{{ route('reconciliations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="section-title mb-0">Daily Formula</h2>
                    @include('partials.status', ['status' => $reconciliation->status])
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach([
                            'Opening Stock' => $reconciliation->opening_stock,
                            'Receiving' => $reconciliation->total_receiving,
                            'Dispensing' => $reconciliation->total_dispensing,
                            'Adjustment' => $reconciliation->total_adjustment,
                            'System Closing' => $reconciliation->system_closing_stock,
                            'Physical Closing' => $reconciliation->physical_closing_stock,
                            'Variance' => $reconciliation->variance,
                            'Variance %' => $reconciliation->variance_percentage,
                        ] as $label => $value)
                            <div class="col-md-3">
                                <div class="metric-label">{{ $label }}</div>
                                <div class="metric-value">{{ \App\Support\QuantityFormatter::format($value) }}</div>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <div class="metric-label">Notes</div>
                            <div>{{ $reconciliation->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h2 class="section-title mb-0">Control</h2></div>
                <div class="card-body d-grid gap-2">
                    @if(auth()->user()->canWriteTransactions() && $reconciliation->status === 'DRAFT')
                        <form method="POST" action="{{ route('reconciliations.submit', $reconciliation) }}">
                            @csrf
                            <button class="btn btn-info w-100" type="submit"><i class="bi bi-send me-1"></i>Submit</button>
                        </form>
                    @endif
                    @if(auth()->user()->canOverrideOperationalControl() && $reconciliation->status === 'SUBMITTED')
                        <form method="POST" action="{{ route('reconciliations.approve', $reconciliation) }}">
                            @csrf
                            <button class="btn btn-success w-100" type="submit"><i class="bi bi-check2-circle me-1"></i>Approve</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="section-title mb-0">VHS / Tangki Timbun</h2></div>
                <div class="card-body">
                    <div class="metric-label">VHS</div>
                    <div class="metric-label mb-1">{{ $reconciliation->storage?->terminal?->terminal_name ?: '-' }}</div>
                    <div class="mb-2">{{ $reconciliation->storage?->storage_name }}</div>
                    <div class="metric-label">Site</div>
                    <div class="mb-2">{{ $reconciliation->storage?->site?->site_name }}</div>
                    <div class="metric-label">Prepared By</div>
                    <div class="mb-2">{{ $reconciliation->preparer?->name ?: '-' }}</div>
                    <div class="metric-label">Approved By</div>
                    <div>{{ $reconciliation->approver?->name ?: '-' }} {{ $reconciliation->approved_at?->format('Y-m-d H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
