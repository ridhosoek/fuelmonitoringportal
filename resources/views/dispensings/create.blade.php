@extends('layouts.app', ['pageTitle' => 'New Fuel Dispensing'])

@section('actions')
    <a href="{{ route('dispensings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('dispensings.store') }}" enctype="multipart/form-data" class="card entry-form">
        @csrf
        <div class="card-body">
            <div class="entry-grid">
                <section class="entry-pane" aria-labelledby="dispensing-context-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-file-earmark-check"></i></span>
                        <h2 class="entry-pane-title" id="dispensing-context-title">Order &amp; Stock Reference</h2>
                    </div>
                    <div class="entry-fields">
                        <div>
                            <label class="form-label" for="dispensing_date">Date</label>
                            <input id="dispensing_date" type="date" name="dispensing_date" value="{{ old('dispensing_date', today()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label" for="dispensing_time">Time</label>
                            <input id="dispensing_time" type="time" name="dispensing_time" value="{{ old('dispensing_time', now()->format('H:i')) }}" class="form-control">
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="fuel_order_id">Fuel Order</label>
                            <select id="fuel_order_id" name="fuel_order_id" class="form-select" required>
                                <option value="">Select FO</option>
                                @foreach($orders as $order)
                                    <option value="{{ $order->id }}" @selected(old('fuel_order_id') == $order->id)>
                                        {{ $order->fo_number }} - {{ $order->site?->site_name }} ({{ $orderItems->get((string) $order->id, collect())->count() }} item tersisa)
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint">Daftar unit di bawah otomatis mengikuti Fuel Order yang dipilih.</div>
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="storage_id">VHS / Tangki Timbun</label>
                            <select id="storage_id" name="storage_id" class="form-select" required>
                                <option value="">Select VHS</option>
                                @foreach($storages as $storage)
                                    <option value="{{ $storage->id }}" @selected(old('storage_id') == $storage->id)>{{ $storage->terminal?->terminal_name }} / {{ $storage->storage_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section class="entry-pane" aria-labelledby="dispensing-control-title">
                    <div class="entry-pane-header">
                        <span class="entry-pane-icon"><i class="bi bi-person-check"></i></span>
                        <h2 class="entry-pane-title" id="dispensing-control-title">Operator &amp; Evidence</h2>
                    </div>
                    <div class="entry-fields">
                        <div>
                            <label class="form-label" for="operator_name">Operator</label>
                            <input id="operator_name" type="text" name="operator_name" value="{{ old('operator_name', auth()->user()->name) }}" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label" for="attachment">Attachment</label>
                            <input id="attachment" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="override_reason">Override Reason</label>
                            <input id="override_reason" type="text" name="override_reason" value="{{ old('override_reason') }}" class="form-control">
                            <div class="form-hint">Khusus Supervisor/Super Admin untuk penyimpangan kontrol operasional.</div>
                        </div>
                        <div class="entry-field-wide">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>
            </div>

            <section class="entry-section-wide dispensing-items-section" aria-labelledby="dispensing-items-title">
                <div class="entry-pane-header">
                    <span class="entry-pane-icon"><i class="bi bi-list-check"></i></span>
                    <div class="min-w-0">
                        <h2 class="entry-pane-title" id="dispensing-items-title">Fuel Order Items</h2>
                        <div class="form-hint">Isi Qty Real dan meter hanya untuk unit yang benar-benar dilayani.</div>
                    </div>
                </div>

                <div id="dispensingSummary" class="dispensing-summary-grid" hidden>
                    <div class="dispensing-summary-item">
                        <span>Unit Tersisa</span>
                        <strong data-summary-items>0</strong>
                    </div>
                    <div class="dispensing-summary-item">
                        <span>Total Diminta</span>
                        <strong><span data-summary-requested>0</span> L</strong>
                    </div>
                    <div class="dispensing-summary-item">
                        <span>Sisa FO</span>
                        <strong><span data-summary-remaining>0</span> L</strong>
                    </div>
                    <div class="dispensing-summary-item dispensing-summary-real">
                        <span>Qty Real Diisi</span>
                        <strong><span data-summary-real>0</span> L</strong>
                    </div>
                </div>

                <div id="dispensingItemsEmpty" class="dispensing-items-empty">
                    <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                    <strong>Pilih Fuel Order terlebih dahulu</strong>
                    <span>Item dan unit permintaan akan ditampilkan otomatis di sini.</span>
                </div>

                <div id="dispensingItemsGrid" class="table-responsive dispensing-items-grid" hidden>
                    <table class="table table-sm align-middle mb-0 dispensing-items-table">
                        <thead>
                            <tr>
                                <th scope="col">Unit</th>
                                <th scope="col">Mekanisme</th>
                                <th scope="col" class="text-end">Permintaan</th>
                                <th scope="col">Meter Awal</th>
                                <th scope="col">Meter Akhir</th>
                                <th scope="col">Qty Real (L)</th>
                                <th scope="col">Produksi</th>
                                <th scope="col">Mobil Tangki</th>
                            </tr>
                        </thead>
                        <tbody id="dispensingItems"></tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="submit" name="submission_action" value="draft" class="btn btn-outline-secondary"><i class="bi bi-save me-1"></i>Simpan Draft</button>
            <button type="submit" name="submission_action" value="post" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Simpan &amp; Posting</button>
        </div>
    </form>

    <template id="dispensingItemTemplate">
        <tr class="dispensing-item-row">
            <td class="dispensing-unit-cell">
                <input type="hidden" data-field="fuel_order_item_id">
                <strong data-unit-code></strong>
                <span data-unit-name></span>
                <small><span data-meter-type></span><span data-production-unit></span></small>
            </td>
            <td><span class="badge text-bg-light border" data-delivery-method></span></td>
            <td class="text-end dispensing-request-cell">
                <strong><span data-requested></span> L</strong>
                <small>Sudah <span data-dispensed></span> L</small>
                <span>Sisa <strong><span data-remaining></span> L</strong></span>
            </td>
            <td>
                <label class="visually-hidden" data-meter-start-label>Meter awal</label>
                <input type="number" step="0.001" min="0" class="form-control form-control-sm text-end" data-field="previous_meter_value" data-meter-start>
            </td>
            <td>
                <label class="visually-hidden" data-meter-end-label>Meter akhir</label>
                <input type="number" step="0.001" min="0" class="form-control form-control-sm text-end" data-field="meter_value" data-meter-end>
                <small class="dispensing-delta">Delta: <span data-meter-delta>-</span></small>
            </td>
            <td>
                <label class="visually-hidden" data-quantity-label>Qty real</label>
                <input type="number" step="0.001" min="0.001" class="form-control form-control-sm text-end dispensing-real-input" data-field="quantity_liter" data-quantity>
            </td>
            <td>
                <label class="visually-hidden" data-production-label>Produksi</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.001" min="0" class="form-control text-end" data-field="production_quantity" data-production>
                    <span class="input-group-text" data-production-suffix>-</span>
                </div>
            </td>
            <td>
                <select class="form-select form-select-sm" data-field="fuel_truck_id" data-fuel-truck>
                    <option value="">Pilih mobil tangki</option>
                    @foreach($fuelTrucks as $truck)
                        <option value="{{ $truck->id }}">{{ $truck->truck_code }} / {{ $truck->plate_number }} / {{ \App\Support\QuantityFormatter::format($truck->capacity_liter) }} L</option>
                    @endforeach
                </select>
                <span class="text-secondary" data-no-truck>-</span>
            </td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const orderItems = {{ Illuminate\Support\Js::from($orderItems) }};
            const oldItems = {{ Illuminate\Support\Js::from(old('items', [])) }};
            const oldItemsById = new Map(oldItems.map((item) => [String(item.fuel_order_item_id), item]));
            const orderSelect = document.getElementById('fuel_order_id');
            const itemsContainer = document.getElementById('dispensingItems');
            const grid = document.getElementById('dispensingItemsGrid');
            const emptyState = document.getElementById('dispensingItemsEmpty');
            const summary = document.getElementById('dispensingSummary');
            const template = document.getElementById('dispensingItemTemplate');
            const numberFormatter = new Intl.NumberFormat('en-US', { maximumFractionDigits: 3 });

            const formatQuantity = (value) => numberFormatter.format(Number(value || 0));

            const setField = (row, index, field, value = '') => {
                const input = row.querySelector(`[data-field="${field}"]`);
                input.name = `items[${index}][${field}]`;
                input.value = value ?? '';

                return input;
            };

            const updateDelta = (row) => {
                const start = Number.parseFloat(row.querySelector('[data-meter-start]').value);
                const end = Number.parseFloat(row.querySelector('[data-meter-end]').value);
                row.querySelector('[data-meter-delta]').textContent = Number.isFinite(start) && Number.isFinite(end) && end >= start
                    ? formatQuantity(Number((end - start).toFixed(3)))
                    : '-';
            };

            const updateRealTotal = () => {
                const total = Array.from(itemsContainer.querySelectorAll('[data-quantity]'))
                    .reduce((sum, input) => sum + (Number.parseFloat(input.value) || 0), 0);
                summary.querySelector('[data-summary-real]').textContent = formatQuantity(total);
            };

            const renderItems = () => {
                const items = orderItems[String(orderSelect.value)] || [];
                itemsContainer.replaceChildren();

                if (items.length === 0) {
                    grid.hidden = true;
                    summary.hidden = true;
                    emptyState.hidden = false;
                    emptyState.querySelector('strong').textContent = orderSelect.value
                        ? 'Tidak ada item yang masih harus dipenuhi'
                        : 'Pilih Fuel Order terlebih dahulu';
                    emptyState.querySelector('span').textContent = orderSelect.value
                        ? 'Seluruh permintaan pada Fuel Order ini sudah terpenuhi.'
                        : 'Item dan unit permintaan akan ditampilkan otomatis di sini.';

                    return;
                }

                items.forEach((item, index) => {
                    const row = template.content.firstElementChild.cloneNode(true);
                    const previousValue = oldItemsById.get(String(item.id)) || {};
                    const meterType = item.meter_type || 'HM/KM';
                    const productionUnit = item.production_unit || '-';

                    setField(row, index, 'fuel_order_item_id', item.id);
                    const meterStart = setField(row, index, 'previous_meter_value', previousValue.previous_meter_value ?? item.last_meter_value ?? '');
                    const meterEnd = setField(row, index, 'meter_value', previousValue.meter_value ?? '');
                    setField(row, index, 'quantity_liter', previousValue.quantity_liter ?? '');
                    setField(row, index, 'production_quantity', previousValue.production_quantity ?? '');
                    const fuelTruck = setField(row, index, 'fuel_truck_id', previousValue.fuel_truck_id ?? '');

                    row.querySelector('[data-unit-code]').textContent = item.unit_code || '-';
                    row.querySelector('[data-unit-name]').textContent = item.unit_name || '-';
                    row.querySelector('[data-meter-type]').textContent = meterType;
                    row.querySelector('[data-production-unit]').textContent = item.production_unit ? ` / Produksi: ${item.production_unit}` : '';
                    row.querySelector('[data-requested]').textContent = formatQuantity(item.requested_quantity);
                    row.querySelector('[data-dispensed]').textContent = formatQuantity(item.dispensed_quantity);
                    row.querySelector('[data-remaining]').textContent = formatQuantity(item.remaining_quantity);
                    row.querySelector('[data-production-suffix]').textContent = productionUnit;
                    row.querySelector('[data-delivery-method]').textContent = item.delivery_method === 'MOBILE_TANKER'
                        ? 'Diantar Mobil Tangki'
                        : 'Datang ke Terminal';
                    row.querySelector('[data-meter-start-label]').textContent = `${meterType} awal ${item.unit_code}`;
                    row.querySelector('[data-meter-end-label]').textContent = `${meterType} akhir ${item.unit_code}`;
                    row.querySelector('[data-quantity-label]').textContent = `Qty real ${item.unit_code}`;
                    row.querySelector('[data-production-label]').textContent = `Produksi ${item.unit_code}`;

                    const usesTruck = item.delivery_method === 'MOBILE_TANKER';
                    fuelTruck.hidden = ! usesTruck;
                    fuelTruck.disabled = ! usesTruck;
                    row.querySelector('[data-no-truck]').hidden = usesTruck;

                    meterStart.addEventListener('input', () => updateDelta(row));
                    meterEnd.addEventListener('input', () => updateDelta(row));
                    row.querySelector('[data-quantity]').addEventListener('input', updateRealTotal);
                    updateDelta(row);
                    itemsContainer.appendChild(row);
                });

                summary.querySelector('[data-summary-items]').textContent = String(items.length);
                summary.querySelector('[data-summary-requested]').textContent = formatQuantity(items.reduce((sum, item) => sum + Number(item.requested_quantity), 0));
                summary.querySelector('[data-summary-remaining]').textContent = formatQuantity(items.reduce((sum, item) => sum + Number(item.remaining_quantity), 0));
                updateRealTotal();
                emptyState.hidden = true;
                grid.hidden = false;
                summary.hidden = false;
            };

            orderSelect.addEventListener('change', renderItems);
            renderItems();
        });
    </script>
@endpush
