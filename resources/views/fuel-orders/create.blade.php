@extends('layouts.app', ['pageTitle' => 'New Fuel Order'])

@section('actions')
    <a href="{{ route('fuel-orders.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('fuel-orders.store') }}" class="card entry-form">
        @csrf
        <div class="card-body">
            <div class="entry-grid">
                <section class="entry-pane" aria-labelledby="fo-scope-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <h2 class="entry-pane-title" id="fo-scope-title">Order Scope</h2>
                    </div>
                    <div class="entry-fields">
                        <div class="entry-field-wide">
                            <label class="form-label" for="fo_date">Tanggal Permintaan</label>
                            <input id="fo_date" type="date" name="fo_date" value="{{ old('fo_date', today()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label" for="requested_time">Jam Permintaan</label>
                            <input id="requested_time" type="time" name="requested_time" value="{{ old('requested_time', now()->format('H:i')) }}" class="form-control">
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="planned_delivery_date">Rencana Tanggal Pengisian</label>
                            <input id="planned_delivery_date" type="date" name="planned_delivery_date" value="{{ old('planned_delivery_date', today()->addDay()->toDateString()) }}" min="{{ today()->addDay()->toDateString() }}" class="form-control" required>
                            <div class="form-hint">Permintaan dibuat sebelum hari pelaksanaan pengisian.</div>
                        </div>
                        <div>
                            <label class="form-label" for="customer_id">Customer</label>
                            <select id="customer_id" name="customer_id" class="form-select" required>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->customer_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="site_id">Site</label>
                            <select id="site_id" name="site_id" class="form-select" required>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section class="entry-pane" aria-labelledby="fo-control-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-person-check"></i></span>
                        <h2 class="entry-pane-title" id="fo-control-title">Request &amp; Control</h2>
                    </div>
                    <div class="entry-fields">
                        <div>
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="OPEN" @selected(old('status', 'OPEN') === 'OPEN')>OPEN</option>
                                <option value="DRAFT" @selected(old('status') === 'DRAFT')>DRAFT</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="requested_by">Requested By</label>
                            <input id="requested_by" type="text" name="requested_by" value="{{ old('requested_by') }}" class="form-control" required>
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
            <section class="entry-section-wide" aria-labelledby="fo-items-title">
                <div class="entry-pane-header d-flex justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="entry-pane-icon"><i class="bi bi-list-check"></i></span>
                        <h2 class="entry-pane-title" id="fo-items-title">FO Items</h2>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addFoItem"><i class="bi bi-plus-lg me-1"></i>Add Item</button>
                </div>
                <div id="foItems" class="d-grid gap-3">
                    <div class="row g-3 fo-item align-items-end">
                        <div class="col-lg-4">
                            <label class="form-label">Unit</label>
                            <select name="items[0][unit_id]" class="form-select" required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" data-mobility-type="{{ $unit->mobility_type }}">{{ $unit->unit_code }} - {{ $unit->unit_name }} ({{ $unit->meter_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-5">
                            <label class="form-label">Mekanisme Pengisian</label>
                            <select name="items[0][delivery_method]" class="form-select delivery-method" required>
                                <option value="DIRECT_STORAGE">Unit datang ke Terminal/Storage</option>
                                <option value="MOBILE_TANKER">Diantar Mobil Tangki</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Requested Liter</label>
                            <input type="number" name="items[0][requested_quantity]" step="0.001" min="0.001" class="form-control text-end" required>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Item Notes</label>
                            <input type="text" name="items[0][notes]" class="form-control">
                        </div>
                        <div class="col-lg-1 col-md-1 d-grid">
                            <button type="button" class="btn btn-outline-danger remove-item" title="Remove"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save FO</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('#foItems');
            const addButton = document.querySelector('#addFoItem');

            addButton.addEventListener('click', () => {
                const index = container.querySelectorAll('.fo-item').length;
                const clone = container.querySelector('.fo-item').cloneNode(true);
                clone.querySelectorAll('select, input').forEach((field) => {
                    field.name = field.name.replace(/\[\d+]/, `[${index}]`);
                    field.value = '';
                });
                container.appendChild(clone);
            });

            container.addEventListener('click', (event) => {
                if (! event.target.closest('.remove-item')) {
                    return;
                }

                if (container.querySelectorAll('.fo-item').length > 1) {
                    event.target.closest('.fo-item').remove();
                }
            });

            container.addEventListener('change', (event) => {
                if (! event.target.matches('select[name$="[unit_id]"]')) {
                    return;
                }

                const option = event.target.options[event.target.selectedIndex];
                const method = event.target.closest('.fo-item').querySelector('.delivery-method');
                method.value = option?.dataset.mobilityType === 'STATIONARY' ? 'MOBILE_TANKER' : 'DIRECT_STORAGE';
            });
        });
    </script>
@endpush
