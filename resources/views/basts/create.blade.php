@extends('layouts.app', ['pageTitle' => 'New BAST'])

@section('actions')
    <a href="{{ route('basts.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('basts.store') }}" class="card entry-form">
        @csrf
        <div class="card-body">
            <div class="entry-grid">
                <section class="entry-pane" aria-labelledby="bast-document-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-clipboard2-check"></i></span>
                        <h2 class="entry-pane-title" id="bast-document-title">Document Information</h2>
                    </div>
                    <div class="entry-fields">
                        <div class="entry-field-wide">
                            <label class="form-label" for="bast_date">BAST Date</label>
                            <input id="bast_date" type="date" name="bast_date" value="{{ old('bast_date', today()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="operator_name">Operator</label>
                            <input id="operator_name" type="text" name="operator_name" value="{{ old('operator_name', auth()->user()->name) }}" class="form-control">
                        </div>
                    </div>
                </section>

                <section class="entry-pane" aria-labelledby="bast-handover-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-person-check"></i></span>
                        <h2 class="entry-pane-title" id="bast-handover-title">Handover Information</h2>
                    </div>
                    <div class="entry-fields">
                        <div class="entry-field-wide">
                            <label class="form-label" for="received_by">Received By</label>
                            <input id="received_by" type="text" name="received_by" value="{{ old('received_by') }}" class="form-control" required>
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
            <section class="entry-section-wide" aria-labelledby="bast-dispensings-title">
                <div class="entry-pane-header">
                    <span class="entry-pane-icon"><i class="bi bi-fuel-pump"></i></span>
                    <h2 class="entry-pane-title" id="bast-dispensings-title">Posted Dispensing Selection</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th></th><th>Date</th><th>Dispensing No</th><th>FO</th><th>Unit</th><th class="text-end">Liter</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dispensings as $dispensing)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="fuel_dispensing_ids[]" value="{{ $dispensing->id }}" class="form-check-input" @checked(in_array($dispensing->id, old('fuel_dispensing_ids', [])))>
                                    </td>
                                    <td>{{ $dispensing->dispensing_date?->toDateString() }}</td>
                                    <td>{{ $dispensing->dispensing_number }}</td>
                                    <td>{{ $dispensing->fuelOrder?->fo_number }}</td>
                                    <td>{{ $dispensing->unit?->unit_code }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($dispensing->quantity_liter) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-secondary py-4">No posted dispensing ready for BAST.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Draft</button>
        </div>
    </form>
@endsection
