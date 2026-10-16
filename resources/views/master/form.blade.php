@php
    $isEdit = $record !== null;
    $formAction = $isEdit
        ? route('master.update', [$resource, $record->id])
        : route('master.store', $resource);
@endphp

@extends('layouts.app', ['pageTitle' => ($isEdit ? 'Edit ' : 'Tambah ').$module['singular']])

@section('actions')
    <a href="{{ route('master.index', ['resource' => $resource]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ $formAction }}" class="card entry-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="card-body">
            @switch($resource)
                @case('customers')
                    <div class="entry-grid">
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-building" title="Identitas Customer" />
                            <div class="entry-fields">
                                <x-master-input name="customer_code" label="Kode Customer" :value="$record?->customer_code" required />
                                <x-master-input name="customer_name" label="Nama Customer" :value="$record?->customer_name" required />
                                <x-master-input name="contact_person" label="Contact Person" :value="$record?->contact_person" />
                                <x-master-input name="phone" label="Telepon" :value="$record?->phone" />
                            </div>
                        </section>
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-card-text" title="Kontak dan Status" />
                            <div class="entry-fields">
                                <x-master-input name="email" label="Email" type="email" :value="$record?->email" wide />
                                <x-master-input name="address" label="Alamat" :value="$record?->address" wide />
                                <x-master-status :value="$record?->status" />
                            </div>
                        </section>
                    </div>
                    @break

                @case('sites')
                    <div class="entry-grid">
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-buildings" title="Kepemilikan" />
                            <div class="entry-fields">
                                <div class="entry-field-wide">
                                    <label class="form-label" for="customer_id">Customer</label>
                                    <select id="customer_id" name="customer_id" class="form-select" required>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected((int) old('customer_id', $record?->customer_id) === $customer->id)>{{ $customer->customer_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-master-input name="site_code" label="Kode Site" :value="$record?->site_code" wide required />
                            </div>
                        </section>
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-geo-alt" title="Informasi Site" />
                            <div class="entry-fields">
                                <x-master-input name="site_name" label="Nama Site" :value="$record?->site_name" wide required />
                                <x-master-input name="address" label="Alamat" :value="$record?->address" wide />
                                <x-master-status :value="$record?->status" />
                            </div>
                        </section>
                    </div>
                    @break

                @case('terminals')
                    <div class="entry-grid">
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-grid-1x2" title="Lokasi dan Identitas" />
                            <div class="entry-fields">
                                <div class="entry-field-wide">
                                    <label class="form-label" for="site_id">Site</label>
                                    <select id="site_id" name="site_id" class="form-select" required>
                                        @foreach($sites as $site)
                                            <option value="{{ $site->id }}" @selected((int) old('site_id', $record?->site_id) === $site->id)>{{ $site->site_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-master-input name="terminal_code" label="Kode Terminal" :value="$record?->terminal_code" required />
                                <x-master-input name="terminal_name" label="Nama Terminal" :value="$record?->terminal_name" required />
                            </div>
                        </section>
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-sliders" title="Informasi Terminal" />
                            <div class="entry-fields">
                                <div class="entry-field-wide">
                                    <label class="form-label" for="description">Keterangan</label>
                                    <textarea id="description" name="description" rows="4" class="form-control">{{ old('description', $record?->description) }}</textarea>
                                </div>
                                <x-master-status :value="$record?->status" />
                            </div>
                        </section>
                    </div>
                    @break

                @case('storages')
                    <div class="entry-grid">
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-database" title="Lokasi VHS" />
                            <div class="entry-fields">
                                <div>
                                    <label class="form-label" for="site_id">Site</label>
                                    <select id="site_id" name="site_id" class="form-select" required>
                                        @foreach($sites as $site)
                                            <option value="{{ $site->id }}" @selected((int) old('site_id', $record?->site_id) === $site->id)>{{ $site->site_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="terminal_id">Terminal</label>
                                    <select id="terminal_id" name="terminal_id" class="form-select" required>
                                        @foreach($terminals as $terminal)
                                            <option value="{{ $terminal->id }}" @selected((int) old('terminal_id', $record?->terminal_id) === $terminal->id)>{{ $terminal->terminal_name }} - {{ $terminal->site?->site_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-master-input name="storage_code" label="Kode VHS" :value="$record?->storage_code" required />
                                <x-master-input name="storage_name" label="Nama VHS" :value="$record?->storage_name" required />
                            </div>
                        </section>
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-speedometer" title="Kapasitas dan Kontrol" />
                            <div class="entry-fields">
                                <x-master-input name="capacity_liter" label="Kapasitas Liter" type="number" step="0.001" min="0.001" :value="$record?->capacity_liter" required />
                                <x-master-input name="minimum_stock_liter" label="Minimum Stok Liter" type="number" step="0.001" min="0" :value="$record?->minimum_stock_liter" required />
                                <x-master-status :value="$record?->status" />
                            </div>
                        </section>
                    </div>
                    @break

                @case('fuel-trucks')
                    <div class="entry-grid">
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-truck" title="Identitas Mobil Tangki" />
                            <div class="entry-fields">
                                <x-master-input name="truck_code" label="Kode Mobil Tangki" :value="$record?->truck_code" required />
                                <x-master-input name="plate_number" label="Nomor Polisi" :value="$record?->plate_number" required />
                            </div>
                        </section>
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-person-badge" title="Kapasitas dan Pengemudi" />
                            <div class="entry-fields">
                                <x-master-input name="capacity_liter" label="Kapasitas Liter" type="number" step="0.001" min="0.001" :value="$record?->capacity_liter" required />
                                <x-master-input name="driver_name" label="Nama Driver" :value="$record?->driver_name" />
                                <x-master-status :value="$record?->status" />
                            </div>
                        </section>
                    </div>
                    @break

                @case('fuel-sources')
                    <div class="entry-grid">
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-fuel-pump" title="Identitas Sumber" />
                            <div class="entry-fields">
                                <x-master-input name="code" label="Kode Sumber" :value="$record?->code" required />
                                <x-master-input name="name" label="Nama Sumber" :value="$record?->name" required />
                            </div>
                        </section>
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-geo-alt" title="Tipe dan Lokasi" />
                            <div class="entry-fields">
                                <x-master-input name="source_type" label="Tipe Sumber" :value="$record?->source_type" required />
                                <x-master-input name="address" label="Alamat" :value="$record?->address" />
                                <x-master-status :value="$record?->status" />
                            </div>
                        </section>
                    </div>
                    @break

                @case('units')
                    <div class="entry-grid">
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-box-seam" title="Identitas Unit" />
                            <div class="entry-fields">
                                <div>
                                    <label class="form-label" for="customer_id">Customer</label>
                                    <select id="customer_id" name="customer_id" class="form-select" required>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected((int) old('customer_id', $record?->customer_id) === $customer->id)>{{ $customer->customer_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="site_id">Site</label>
                                    <select id="site_id" name="site_id" class="form-select" required>
                                        @foreach($sites as $site)
                                            <option value="{{ $site->id }}" @selected((int) old('site_id', $record?->site_id) === $site->id)>{{ $site->site_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-master-input name="unit_code" label="Kode Unit" :value="$record?->unit_code" required />
                                <x-master-input name="unit_name" label="Nama Unit" :value="$record?->unit_name" required />
                                <x-master-input name="plate_number" label="Nomor Polisi" :value="$record?->plate_number" />
                                <x-master-input name="unit_type" label="Kategori Unit" :value="$record?->unit_type" required />
                            </div>
                        </section>
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-sliders" title="Klasifikasi Operasional" />
                            <div class="entry-fields">
                                <div>
                                    <label class="form-label" for="mobility_type">Mobilitas</label>
                                    <select id="mobility_type" name="mobility_type" class="form-select" required>
                                        <option value="MOVABLE" @selected(old('mobility_type', $record?->mobility_type) === 'MOVABLE')>Bergerak</option>
                                        <option value="STATIONARY" @selected(old('mobility_type', $record?->mobility_type) === 'STATIONARY')>Tidak Bergerak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="meter_type">Meter</label>
                                    <select id="meter_type" name="meter_type" class="form-select" required>
                                        @foreach(['HM', 'KM'] as $meter)
                                            <option value="{{ $meter }}" @selected(old('meter_type', $record?->meter_type ?? 'HM') === $meter)>{{ $meter }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-master-input name="brand" label="Merek" :value="$record?->brand" />
                                <x-master-input name="model" label="Model" :value="$record?->model" />
                                <x-master-input name="production_unit" label="Satuan Produksi" :value="$record?->production_unit" />
                                <x-master-status :value="$record?->status" />
                            </div>
                        </section>
                    </div>
                    @break

                @case('users')
                    <div class="entry-grid">
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-person" title="Identitas User" />
                            <div class="entry-fields">
                                <x-master-input name="name" label="Nama" :value="$record?->name" wide required />
                                <x-master-input name="username" label="Username" :value="$record?->username" />
                                <x-master-input name="email" label="Email" type="email" :value="$record?->email" required />
                            </div>
                        </section>
                        <section class="entry-pane">
                            <x-master-section-title icon="bi-shield-check" title="Akses User" />
                            <div class="entry-fields">
                                <div>
                                    <label class="form-label" for="role">Role</label>
                                    <select id="role" name="role" class="form-select" required>
                                        @foreach($roles as $key => $label)
                                            <option value="{{ $key }}" @selected(old('role', $record?->role) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="is_active">Status</label>
                                    <select id="is_active" name="is_active" class="form-select" required>
                                        <option value="1" @selected((bool) old('is_active', $record?->is_active ?? true))>ACTIVE</option>
                                        <option value="0" @selected(! (bool) old('is_active', $record?->is_active ?? true))>INACTIVE</option>
                                    </select>
                                </div>
                                <x-master-input name="password" label="Password" type="password" wide :required="! $isEdit" />
                            </div>
                        </section>
                    </div>
                    @break
            @endswitch
        </div>

        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('master.index', ['resource' => $resource]) }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Data' }}</button>
        </div>
    </form>
@endsection
