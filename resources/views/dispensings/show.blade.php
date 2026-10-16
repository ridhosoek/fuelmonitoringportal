@extends('layouts.app', ['pageTitle' => $dispensing->dispensing_number])

@section('actions')
    <a href="{{ route('dispensings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="section-title mb-0">Dispensing Detail</h2>
                    @include('partials.status', ['status' => $dispensing->status])
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach([
                            'Date' => $dispensing->dispensing_date?->toDateString().' '.$dispensing->dispensing_time,
                            'FO' => $dispensing->fuelOrder?->fo_number,
                            'Unit' => $dispensing->unit?->unit_code.' - '.$dispensing->unit?->unit_name,
                            'VHS' => $dispensing->storage?->storage_name,
                            'Terminal' => $dispensing->storage?->terminal?->terminal_name,
                            'Mekanisme' => $dispensing->delivery_method === 'MOBILE_TANKER' ? 'Diantar Mobil Tangki' : 'Langsung di Terminal/Storage',
                            'Mobil Tangki' => $dispensing->fuelTruck ? $dispensing->fuelTruck->truck_code.' / '.$dispensing->fuelTruck->plate_number : '-',
                            'Meter Type' => $dispensing->meter_type,
                            'HM/KM Awal' => $dispensing->previous_meter_value === null ? '-' : \App\Support\QuantityFormatter::format($dispensing->previous_meter_value),
                            'HM/KM Akhir' => \App\Support\QuantityFormatter::format($dispensing->meter_value),
                            'Working Hours / Delta' => $dispensing->workingHours() === null ? '-' : \App\Support\QuantityFormatter::format($dispensing->workingHours()),
                            'Quantity Liter' => \App\Support\QuantityFormatter::format($dispensing->quantity_liter),
                            'Liter per Hour' => $dispensing->literPerHour() === null ? '-' : \App\Support\QuantityFormatter::format($dispensing->literPerHour()),
                            'Production' => $dispensing->production_quantity === null ? '-' : \App\Support\QuantityFormatter::format($dispensing->production_quantity).' '.($dispensing->production_unit ?? ''),
                            'Liter per Production' => $dispensing->literPerProductionUnit() === null ? '-' : \App\Support\QuantityFormatter::format($dispensing->literPerProductionUnit()),
                            'Operator' => $dispensing->operator_name,
                        ] as $label => $value)
                            <div class="col-md-4">
                                <div class="metric-label">{{ $label }}</div>
                                <div class="metric-value">{{ $value }}</div>
                            </div>
                        @endforeach
                        <div class="col-md-6">
                            <div class="metric-label">Override Reason</div>
                            <div>{{ $dispensing->override_reason ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="metric-label">BAST</div>
                            <div>
                                @if($dispensing->bastItem?->bast)
                                    <a href="{{ route('basts.show', $dispensing->bastItem->bast) }}">{{ $dispensing->bastItem->bast->bast_number }}</a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="metric-label">Notes</div>
                            <div>{{ $dispensing->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h2 class="section-title mb-0">Control</h2></div>
                <div class="card-body d-grid gap-2">
                    @if($dispensing->attachment_path)
                        <a href="{{ route('dispensings.attachment', $dispensing) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-paperclip me-1"></i>Attachment
                        </a>
                    @endif
                    @if(auth()->user()->canWriteTransactions() && $dispensing->status === 'DRAFT')
                        <form method="POST" action="{{ route('dispensings.post', $dispensing) }}">
                            @csrf
                            <button class="btn btn-success w-100" type="submit"><i class="bi bi-check2-circle me-1"></i>Post Dispensing</button>
                        </form>
                    @endif
                    @if(auth()->user()->canOverrideOperationalControl() && $dispensing->status === 'POSTED' && ! $dispensing->bastItem)
                        <form method="POST" action="{{ route('dispensings.reverse', $dispensing) }}">
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
                    <div class="mb-2">{{ $dispensing->creator?->name ?: '-' }}</div>
                    <div class="small text-secondary">Posted by</div>
                    <div class="mb-2">{{ $dispensing->poster?->name ?: '-' }} {{ $dispensing->posted_at?->format('Y-m-d H:i') }}</div>
                    <div class="small text-secondary">Approved by</div>
                    <div class="mb-2">{{ $dispensing->approver?->name ?: '-' }} {{ $dispensing->approved_at?->format('Y-m-d H:i') }}</div>
                    <div class="small text-secondary">Reversed by</div>
                    <div>{{ $dispensing->reverser?->name ?: '-' }} {{ $dispensing->reversed_at?->format('Y-m-d H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
