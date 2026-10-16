@extends('layouts.app', ['pageTitle' => 'New Daily Reconciliation'])

@section('actions')
    <a href="{{ route('reconciliations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('reconciliations.store') }}" class="card entry-form">
        @csrf
        <div class="card-body">
            <div class="entry-grid">
                <section class="entry-pane" aria-labelledby="reconciliation-scope-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-calendar3"></i></span>
                        <h2 class="entry-pane-title" id="reconciliation-scope-title">Reconciliation Scope</h2>
                    </div>
                    <div class="entry-fields">
                        <div class="entry-field-wide">
                            <label class="form-label" for="reconciliation_date">Date</label>
                            <input id="reconciliation_date" type="date" name="reconciliation_date" value="{{ old('reconciliation_date', today()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="storage_id">VHS / Tangki Timbun</label>
                            <select id="storage_id" name="storage_id" class="form-select" required>
                                <option value="">Select VHS</option>
                                @foreach($storages as $storage)
                                    <option value="{{ $storage->id }}" @selected(old('storage_id') == $storage->id)>{{ $storage->terminal?->terminal_name ?: 'Tanpa Terminal' }} / {{ $storage->storage_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section class="entry-pane" aria-labelledby="reconciliation-physical-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-clipboard-data"></i></span>
                        <h2 class="entry-pane-title" id="reconciliation-physical-title">Physical Verification</h2>
                    </div>
                    <div class="entry-fields">
                        <div class="entry-field-wide">
                            <label class="form-label" for="physical_closing_stock">Physical Closing Stock</label>
                            <input id="physical_closing_stock" type="number" name="physical_closing_stock" value="{{ old('physical_closing_stock') }}" step="0.001" min="0" class="form-control text-end" required>
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-calculator me-1"></i>Calculate Draft</button>
        </div>
    </form>
@endsection
