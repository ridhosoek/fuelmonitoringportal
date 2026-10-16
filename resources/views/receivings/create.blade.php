@extends('layouts.app', ['pageTitle' => 'New Fuel Receiving'])

@section('actions')
    <a href="{{ route('receivings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('receivings.store') }}" enctype="multipart/form-data" class="card entry-form">
        @csrf
        <div class="card-body">
            <div class="entry-grid">
                <section class="entry-pane" aria-labelledby="receiving-transaction-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-calendar-check"></i></span>
                        <h2 class="entry-pane-title" id="receiving-transaction-title">Transaction Information</h2>
                    </div>
                    <div class="entry-fields">
                        <div>
                            <label class="form-label" for="receiving_date">Date</label>
                            <input id="receiving_date" type="date" name="receiving_date" value="{{ old('receiving_date', today()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label" for="receiving_time">Time</label>
                            <input id="receiving_time" type="time" name="receiving_time" value="{{ old('receiving_time', now()->format('H:i')) }}" class="form-control">
                        </div>
                        <div>
                            <label class="form-label" for="site_id">Site</label>
                            <select id="site_id" name="site_id" class="form-select" required>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->site_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="storage_id">VHS / Tangki Timbun</label>
                            <select id="storage_id" name="storage_id" class="form-select" required>
                                @foreach($storages as $storage)
                                    <option value="{{ $storage->id }}" @selected(old('storage_id') == $storage->id)>{{ $storage->terminal?->terminal_name ?: 'Tanpa Terminal' }} / {{ $storage->storage_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="quantity_liter">Quantity Liter</label>
                            <input id="quantity_liter" type="number" name="quantity_liter" value="{{ old('quantity_liter') }}" step="0.001" min="0.001" class="form-control text-end" required>
                        </div>
                    </div>
                </section>

                <section class="entry-pane" aria-labelledby="receiving-delivery-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-truck"></i></span>
                        <h2 class="entry-pane-title" id="receiving-delivery-title">Source &amp; Delivery</h2>
                    </div>
                    <div class="entry-fields">
                        <div>
                            <label class="form-label" for="fuel_source_id">Fuel Source</label>
                            <select id="fuel_source_id" name="fuel_source_id" class="form-select" required>
                                @foreach($fuelSources as $source)
                                    <option value="{{ $source->id }}" @selected(old('fuel_source_id') == $source->id)>{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="delivery_document_number">Delivery Document</label>
                            <input id="delivery_document_number" type="text" name="delivery_document_number" value="{{ old('delivery_document_number') }}" class="form-control">
                        </div>
                        <div>
                            <label class="form-label" for="truck_plate_number">Truck Plate</label>
                            <input id="truck_plate_number" type="text" name="truck_plate_number" value="{{ old('truck_plate_number') }}" class="form-control">
                        </div>
                        <div>
                            <label class="form-label" for="driver_name">Driver</label>
                            <input id="driver_name" type="text" name="driver_name" value="{{ old('driver_name') }}" class="form-control">
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="attachment">Attachment</label>
                            <input id="attachment" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="submit" name="submission_action" value="draft" class="btn btn-outline-secondary"><i class="bi bi-save me-1"></i>Simpan Draft</button>
            <button type="submit" name="submission_action" value="post" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Simpan &amp; Posting</button>
        </div>
    </form>
@endsection
