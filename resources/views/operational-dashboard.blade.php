@extends('layouts.app', ['pageTitle' => 'Fuel Monitoring Dashboard'])

@php
    $quantity = fn (int|float|string $value, int $decimals = 3): string => \App\Support\QuantityFormatter::formatIndonesian($value, $decimals);
    $percent = fn (int|float|string $value, int $decimals = 1): string => \App\Support\QuantityFormatter::percentIndonesian($value, $decimals);
    $summary = $dashboard['summary'];
    $reconciliation = $dashboard['reconciliation'];
@endphp

@section('content')
    <div class="operational-control-dashboard">
        <section class="control-header mb-3" aria-labelledby="control-dashboard-title">
            <div class="control-heading">
                <div>
                    <div class="control-eyebrow">Operational Fuel Control</div>
                    <h2 id="control-dashboard-title">Fuel Monitoring Dashboard</h2>
                    <p>Posisi stok real-time dan aktivitas periode terpilih.</p>
                </div>
                <div class="control-updated">
                    <span>Last updated</span>
                    <strong>{{ $dashboard['last_updated'] }}</strong>
                </div>
            </div>

            <form method="GET" id="operational-dashboard-filter" class="control-filter-grid">
                <div>
                    <label for="control-site" class="form-label">Site</label>
                    <select id="control-site" name="site_id" class="form-select">
                        <option value="">All Site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected((string) $dashboard['filters']['site_id'] === (string) $site->id)>{{ $site->site_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="control-storage" class="form-label">VHS</label>
                    <select id="control-storage" name="storage_id" class="form-select">
                        <option value="">All VHS</option>
                        @foreach($storages as $storage)
                            <option value="{{ $storage->id }}" data-site-id="{{ $storage->site_id }}" @selected((string) $dashboard['filters']['storage_id'] === (string) $storage->id)>
                                {{ $storage->terminal?->terminal_name ?: 'Tanpa Terminal' }} / {{ $storage->storage_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="control-date-from" class="form-label">Date From</label>
                    <input id="control-date-from" type="date" name="date_from" value="{{ $dashboard['filters']['date_from'] }}" class="form-control">
                </div>
                <div>
                    <label for="control-date-to" class="form-label">Date To</label>
                    <input id="control-date-to" type="date" name="date_to" value="{{ $dashboard['filters']['date_to'] }}" class="form-control">
                </div>
                <div class="control-filter-action">
                    <button type="submit" class="btn btn-primary" id="control-apply-filter">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span>Apply</span>
                    </button>
                </div>
            </form>
        </section>

        @if($dashboard['critical_alert_count'] > 0)
            <div class="control-critical-banner mb-3" role="alert">
                <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
                <div>
                    <strong>{{ $dashboard['critical_alert_count'] }} VHS membutuhkan perhatian segera</strong>
                    <span>Stok VHS kosong dan memerlukan tindak lanjut segera.</span>
                </div>
                <a href="#attention-required">Review alerts <i class="bi bi-arrow-down"></i></a>
            </div>
        @endif

        <section aria-labelledby="health-status-title" class="mb-3">
            <div class="control-section-heading">
                <div>
                    <span class="control-section-index">01</span>
                    <div>
                        <h3 id="health-status-title">Health Status</h3>
                        <p>Apakah kondisi fuel saat ini aman?</p>
                    </div>
                </div>
            </div>

            <div class="primary-kpi-grid">
                <article class="control-kpi control-kpi-{{ strtolower($summary['current_stock']['status']) }}">
                    <div class="control-kpi-topline">
                        <span>Current Stock</span>
                        <span class="control-status control-status-{{ strtolower($summary['current_stock']['status']) }}">{{ $summary['current_stock']['status'] }}</span>
                    </div>
                    <div class="control-kpi-value">{{ $quantity($summary['current_stock']['value']) }} <small>L</small></div>
                    <p>Posisi ledger saat ini, real-time.</p>
                </article>

                <article class="control-kpi">
                    <div class="control-kpi-topline">
                        <span>Days of Stock</span>
                        <i class="bi bi-info-circle" title="Estimated days the current stock can support based on average consumption."></i>
                    </div>
                    @if($summary['days_remaining']['value'] !== null)
                        <div class="control-kpi-value">{{ $quantity($summary['days_remaining']['value'], 1) }} <small>Days</small></div>
                        <p>Rata-rata {{ $quantity($summary['days_remaining']['average_daily_consumption'], 2) }} L/hari.</p>
                    @else
                        <div class="control-kpi-value">N/A</div>
                        <p>Average daily consumption unavailable.</p>
                    @endif
                </article>

                <article class="control-kpi">
                    <div class="control-kpi-topline"><span>Period Consumption</span></div>
                    <div class="control-kpi-value">{{ $quantity($summary['consumption']['value']) }} <small>L</small></div>
                    @if($summary['consumption']['comparison_percentage'] !== null)
                        <p class="control-comparison {{ $summary['consumption']['comparison_percentage'] > 0 ? 'is-up' : 'is-down' }}">
                            <i class="bi {{ $summary['consumption']['comparison_percentage'] > 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                            {{ $percent(abs($summary['consumption']['comparison_percentage'])) }} vs previous period
                        </p>
                    @else
                        <p>Previous period comparison unavailable.</p>
                    @endif
                </article>

                <article class="control-kpi {{ $summary['stock_variance']['available'] ? '' : 'control-kpi-muted' }}">
                    <div class="control-kpi-topline">
                        <span>Stock Variance</span>
                        <i class="bi bi-info-circle" title="Difference between approved physical stock and system stock."></i>
                    </div>
                    @if($summary['stock_variance']['available'])
                        <div class="control-kpi-value">{{ $quantity($summary['stock_variance']['value']) }} <small>L</small></div>
                        <p>{{ $summary['stock_variance']['percentage'] === null ? 'Percentage N/A' : $percent($summary['stock_variance']['percentage'], 2) }} · {{ $summary['stock_variance']['coverage'] }}</p>
                    @else
                        <div class="control-kpi-value">N/A</div>
                        <p>Physical stock coverage {{ $summary['stock_variance']['coverage'] }}.</p>
                    @endif
                </article>
            </div>

            <div class="secondary-kpi-grid mt-3">
                @foreach([
                    ['label' => 'Period Receiving', 'value' => $quantity($summary['receiving']).' L', 'icon' => 'bi-box-arrow-in-down'],
                    ['label' => 'Active Units', 'value' => number_format($summary['active_units'], 0, ',', '.'), 'icon' => 'bi-truck'],
                    ['label' => 'Open FO', 'value' => number_format($summary['open_fo'], 0, ',', '.'), 'icon' => 'bi-file-earmark-text'],
                    ['label' => 'Period Transactions', 'value' => number_format($summary['transactions'], 0, ',', '.'), 'icon' => 'bi-list-check'],
                ] as $metric)
                    <article class="secondary-kpi">
                        <i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i>
                        <div><span>{{ $metric['label'] }}</span><strong>{{ $metric['value'] }}</strong></div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="control-panel reconciliation-panel mb-3" aria-labelledby="reconciliation-title">
            <div class="control-panel-header">
                <div>
                    <h3 id="reconciliation-title">Stock Reconciliation</h3>
                    <p>Pergerakan ledger selama periode terpilih.</p>
                </div>
                @if($reconciliation['matches_current'] === false)
                    <span class="control-status control-status-warning">PERIOD CLOSING</span>
                @elseif($reconciliation['matches_current'] === true)
                    <span class="control-status control-status-safe">MATCHED</span>
                @endif
            </div>
            <div class="reconciliation-flow">
                <div><span>Opening Stock</span><strong>{{ $quantity($reconciliation['opening_stock']) }} L</strong></div>
                <b aria-hidden="true">+</b>
                <div><span>Receiving</span><strong>{{ $quantity($reconciliation['receiving']) }} L</strong></div>
                <b aria-hidden="true">−</b>
                <div><span>Consumption</span><strong>{{ $quantity($reconciliation['consumption']) }} L</strong></div>
                <b aria-hidden="true">±</b>
                <div><span>Adjustment / Reversal</span><strong>{{ $quantity($reconciliation['adjustment']) }} L</strong></div>
                <b aria-hidden="true">=</b>
                <div class="reconciliation-result"><span>Period Closing</span><strong>{{ $quantity($reconciliation['closing_stock']) }} L</strong></div>
            </div>
            @if($reconciliation['matches_current'] === false)
                <p class="reconciliation-note mb-0"><i class="bi bi-info-circle"></i> Current stock berbeda karena terdapat movement setelah Date To.</p>
            @endif
        </section>

        <section class="control-analytics-grid mb-3">
            <article class="control-panel control-panel-wide">
                <div class="control-panel-header">
                    <div><h3>Stock Position / Trend</h3><p>Saldo akhir per interval dan total minimum stock VHS terpilih.</p></div>
                    <div class="control-legend"><span><i class="legend-stock"></i> Stock</span><span><i class="legend-minimum"></i> Minimum</span></div>
                </div>
                <div id="operational-stock-trend" class="control-chart"></div>
            </article>

            <article class="control-panel control-attention-panel" id="attention-required">
                <div class="control-panel-header">
                    <div><h3>Attention Required</h3><p>Prioritas tindakan berbasis data.</p></div>
                    <span class="attention-count">{{ count($dashboard['alerts']) }}</span>
                </div>
                <div class="attention-list">
                    @forelse($dashboard['alerts'] as $alert)
                        <div class="attention-item attention-{{ strtolower($alert['severity']) }}">
                            <div class="attention-heading">
                                <span class="control-status control-status-{{ strtolower($alert['severity']) }}">{{ $alert['severity'] }}</span>
                                <strong>{{ $alert['subject'] }}</strong>
                            </div>
                            <div class="attention-title">{{ $alert['title'] }}</div>
                            <p>{{ $alert['message'] }}</p>
                            @if(isset($alert['current_stock']))
                                <dl class="attention-facts">
                                    <div><dt>Current</dt><dd>{{ $quantity($alert['current_stock']) }} L</dd></div>
                                    <div><dt>Minimum</dt><dd>{{ $quantity($alert['minimum_stock']) }} L</dd></div>
                                    <div><dt>Average</dt><dd>{{ $alert['average_daily_consumption'] === null ? 'N/A' : $quantity($alert['average_daily_consumption'], 2).' L/day' }}</dd></div>
                                    <div><dt>Remaining</dt><dd>{{ $alert['days_remaining'] === null ? 'N/A' : $quantity($alert['days_remaining'], 1).' days' }}</dd></div>
                                </dl>
                            @endif
                            <div class="attention-action"><i class="bi bi-arrow-right"></i> {{ $alert['action'] }}</div>
                        </div>
                    @empty
                        <div class="control-empty compact"><i class="bi bi-check-circle"></i><span>No active operational alerts.</span></div>
                    @endforelse
                </div>
            </article>

            <article class="control-panel">
                <div class="control-panel-header"><div><h3>Receiving vs Consumption</h3><p>Net movement tersedia pada tooltip chart.</p></div></div>
                <div id="operational-movement-chart" class="control-chart"></div>
            </article>

            <article class="control-panel">
                <div class="control-panel-header"><div><h3>Stock by VHS</h3><p>Urut dari lokasi kritis dan persentase terendah.</p></div></div>
                <div class="stock-position-list">
                    @forelse($dashboard['stock_by_vhs'] as $position)
                        <div class="stock-position-item">
                            <div class="stock-position-heading">
                                <div><strong>{{ $position['name'] }}</strong><span>{{ $position['terminal'] }} · {{ $position['site'] }}</span></div>
                                <span class="control-status control-status-{{ strtolower($position['status']) }}">{{ $position['status'] }}</span>
                            </div>
                            <div class="stock-position-value"><strong>{{ $quantity($position['stock']) }} L</strong><span>{{ $position['percentage'] === null ? 'Capacity N/A' : $percent($position['percentage']) }}</span></div>
                            @if($position['percentage'] !== null)
                                <div class="progress stock-progress" role="progressbar" aria-label="{{ $position['name'] }} stock" aria-valuenow="{{ min(100, max(0, $position['percentage'])) }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar {{ $position['status'] === 'CRITICAL' ? 'bg-danger' : 'bg-success' }}" style="width: {{ min(100, max(0, $position['percentage'])) }}%"></div>
                                </div>
                            @endif
                            <div class="stock-position-meta"><span>Minimum {{ $quantity($position['minimum']) }} L</span><span>{{ $position['days_remaining'] === null ? 'Days remaining N/A' : $quantity($position['days_remaining'], 1).' days remaining' }}</span></div>
                        </div>
                    @empty
                        <div class="control-empty"><i class="bi bi-database-x"></i><span>No VHS available for the selected filter.</span></div>
                    @endforelse
                </div>
            </article>

            <article class="control-panel">
                <div class="control-panel-header"><div><h3>Top Fuel Consumers</h3><p>Top 10 unit berdasarkan dispensing posted.</p></div></div>
                <div id="operational-top-consumers" class="control-chart"></div>
            </article>

            <article class="control-panel">
                <div class="control-panel-header"><div><h3>Vehicle & Equipment Efficiency</h3><p>KM/L dan L/HM dipisahkan sesuai interpretasinya.</p></div></div>
                <div class="efficiency-grid">
                    <div>
                        <h4>Vehicle Efficiency <span title="Distance travelled per liter of fuel.">KM/L</span></h4>
                        <div id="vehicle-efficiency-chart" class="control-chart control-chart-small"></div>
                    </div>
                    <div>
                        <h4>Equipment Consumption <span title="Fuel consumed per operating hour.">L/HM</span></h4>
                        <div id="equipment-efficiency-chart" class="control-chart control-chart-small"></div>
                    </div>
                </div>
            </article>

            <article class="control-panel">
                <div class="control-panel-header"><div><h3>Consumption by Unit Type</h3><p>Distribusi konsumsi berdasarkan kategori unit.</p></div></div>
                <div id="operational-unit-type" class="control-chart"></div>
            </article>

            <article class="control-panel">
                <div class="control-panel-header">
                    <div><h3>Operational Exceptions</h3><p>Transaksi posted dengan meter tidak lengkap atau tidak bergerak.</p></div>
                    <span class="attention-count">{{ $dashboard['exceptions']['count'] }}</span>
                </div>
                <div class="exception-list">
                    @forelse($dashboard['exceptions']['items'] as $exception)
                        <div class="exception-item">
                            <i class="bi bi-exclamation-triangle"></i>
                            <div><strong>{{ $exception['unit'] }} · {{ $exception['number'] }}</strong><span>{{ $exception['date'] }} · {{ $exception['storage'] }}</span><p>{{ $exception['reason'] }}</p></div>
                        </div>
                    @empty
                        <div class="control-empty"><i class="bi bi-check-circle"></i><span>No meter exceptions in the selected period.</span></div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="control-panel mb-3" aria-labelledby="transactions-title">
            <div class="control-panel-header">
                <div><h3 id="transactions-title">Recent Posted Transactions</h3><p>Maksimal 10 dispensing terbaru pada periode terpilih.</p></div>
                <a href="{{ route('dispensings.index', ['date_from' => $dashboard['filters']['date_from'], 'date_to' => $dashboard['filters']['date_to'], 'storage_id' => $dashboard['filters']['storage_id']]) }}" class="btn btn-outline-secondary btn-sm">View All Transactions <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover control-transaction-table mb-0">
                    <thead><tr><th>Time</th><th>Unit</th><th>Unit Type</th><th>Site</th><th>VHS</th><th class="text-end">Quantity</th><th>KM / HM</th><th>Efficiency</th><th>Type</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($dashboard['transactions'] as $transaction)
                            <tr>
                                <td><strong>{{ $transaction['date'] }}</strong><small>{{ $transaction['time'] }}</small></td>
                                <td><a href="{{ route('dispensings.show', $transaction['id']) }}">{{ $transaction['unit'] }}</a></td>
                                <td>{{ $transaction['unit_type'] }}</td>
                                <td>{{ $transaction['site'] }}</td>
                                <td>{{ $transaction['storage'] }}</td>
                                <td class="text-end fw-semibold">{{ $quantity($transaction['quantity']) }} L</td>
                                <td>{{ $transaction['meter_start'] === null ? 'N/A' : $quantity($transaction['meter_start']).' → '.$quantity($transaction['meter_end']).' '.$transaction['meter_type'] }}</td>
                                <td>{{ $transaction['efficiency'] === null ? 'N/A' : $quantity($transaction['efficiency'], 2).' '.($transaction['meter_type'] === 'KM' ? 'KM/L' : 'L/HM') }}</td>
                                <td>Dispensing</td>
                                <td>@include('partials.status', ['status' => $transaction['status']])</td>
                            </tr>
                        @empty
                            <tr><td colspan="10"><div class="control-empty compact"><i class="bi bi-inbox"></i><span>No posted transactions in the selected period.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script type="module">
        await new Promise((resolve) => {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', resolve, { once: true });
                return;
            }
            resolve();
        });

        const dashboard = @json($dashboard);
        const siteSelect = document.querySelector('#control-site');
        const storageSelect = document.querySelector('#control-storage');
        const storageOptions = Array.from(storageSelect.options).slice(1).map((option) => ({
            value: option.value,
            label: option.textContent.trim(),
            siteId: option.dataset.siteId,
        }));

        const refreshStorageOptions = () => {
            const selectedSite = siteSelect.value;
            const selectedStorage = storageSelect.value;
            storageSelect.replaceChildren(new Option('All VHS', ''));

            storageOptions
                .filter((option) => !selectedSite || option.siteId === selectedSite)
                .forEach((option) => storageSelect.add(new Option(option.label, option.value, false, option.value === selectedStorage)));
        };

        siteSelect.addEventListener('change', refreshStorageOptions);
        document.querySelector('#operational-dashboard-filter').addEventListener('submit', () => {
            const button = document.querySelector('#control-apply-filter');
            button.disabled = true;
            button.querySelector('i').className = 'spinner-border spinner-border-sm';
            button.querySelector('span').textContent = 'Loading';
        });

        const formatNumber = (value, decimals = 0) => Number(value).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: decimals,
        });
        const hasValues = (values) => values.some((value) => Number(value) !== 0);
        const emptyState = (selector, message) => {
            const target = document.querySelector(selector);
            target.innerHTML = '';
            const wrapper = document.createElement('div');
            wrapper.className = 'control-empty';
            const icon = document.createElement('i');
            icon.className = 'bi bi-bar-chart';
            const text = document.createElement('span');
            text.textContent = message;
            wrapper.append(icon, text);
            target.append(wrapper);
        };
        const renderChart = (selector, options, hasData, emptyMessage) => {
            if (!hasData) {
                emptyState(selector, emptyMessage);
                return;
            }

            try {
                new window.ApexCharts(document.querySelector(selector), options).render();
            } catch (error) {
                console.error(`Unable to render ${selector}`, error);
                emptyState(selector, 'Unable to load dashboard data. Please retry.');
            }
        };
        const chartBase = {
            chart: { toolbar: { show: false }, fontFamily: 'Inter, Segoe UI, Arial, sans-serif' },
            colors: ['#0f766e', '#2563eb', '#d97706'],
            dataLabels: { enabled: false },
            grid: { borderColor: '#e3eaed', strokeDashArray: 3 },
            tooltip: { theme: 'light' },
        };
        const literAxis = { labels: { formatter: (value) => `${formatNumber(value)} L` } };

        renderChart('#operational-stock-trend', {
            ...chartBase,
            colors: ['#0f766e', '#b45309'],
            chart: { ...chartBase.chart, type: 'line', height: 310 },
            series: [
                { name: 'Stock', type: 'area', data: dashboard.stock_trend.stock },
                { name: 'Minimum Stock', type: 'line', data: dashboard.stock_trend.minimum },
            ],
            stroke: { curve: 'straight', width: [3, 2], dashArray: [0, 6] },
            fill: { type: ['gradient', 'solid'], opacity: [0.28, 1] },
            xaxis: { categories: dashboard.stock_trend.labels, labels: { rotate: -35 } },
            yaxis: literAxis,
            tooltip: { shared: true, y: { formatter: (value) => `${formatNumber(value, 3)} L` } },
            legend: { show: false },
        }, dashboard.stock_trend.has_data, 'No stock position data for the selected filter.');

        renderChart('#operational-movement-chart', {
            ...chartBase,
            chart: { ...chartBase.chart, type: 'bar', height: 300 },
            series: [
                { name: 'Receiving', data: dashboard.fuel_movements.receiving },
                { name: 'Consumption', data: dashboard.fuel_movements.consumption },
            ],
            plotOptions: { bar: { columnWidth: '58%', borderRadius: 2 } },
            xaxis: { categories: dashboard.fuel_movements.labels, labels: { rotate: -35 } },
            yaxis: literAxis,
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: (value) => `${formatNumber(value, 3)} L` },
                custom: ({ dataPointIndex, w }) => {
                    const label = w.globals.labels[dataPointIndex];
                    const received = dashboard.fuel_movements.receiving[dataPointIndex] || 0;
                    const consumed = dashboard.fuel_movements.consumption[dataPointIndex] || 0;
                    const net = dashboard.fuel_movements.net[dataPointIndex] || 0;
                    return `<div class="control-chart-tooltip"><strong>${label}</strong><span>Received: ${formatNumber(received, 3)} L</span><span>Consumed: ${formatNumber(consumed, 3)} L</span><span>Net: ${formatNumber(net, 3)} L</span></div>`;
                },
            },
        }, hasValues([...dashboard.fuel_movements.receiving, ...dashboard.fuel_movements.consumption]), 'No receiving or consumption in the selected period.');

        const topConsumers = dashboard.top_consumers;
        renderChart('#operational-top-consumers', {
            ...chartBase,
            chart: { ...chartBase.chart, type: 'bar', height: 300 },
            series: [{ name: 'Consumption', data: topConsumers.map((item) => item.consumption) }],
            plotOptions: { bar: { horizontal: true, borderRadius: 2, distributed: false } },
            xaxis: { categories: topConsumers.map((item) => item.unit), labels: { formatter: (value) => formatNumber(value) } },
            tooltip: { y: { formatter: (value, context) => `${formatNumber(value, 3)} L · ${formatNumber(topConsumers[context.dataPointIndex].percentage, 1)}%` } },
        }, topConsumers.length > 0, 'No posted unit consumption in the selected period.');

        const vehicle = dashboard.efficiency.vehicle;
        renderChart('#vehicle-efficiency-chart', {
            ...chartBase,
            chart: { ...chartBase.chart, type: 'bar', height: 235 },
            series: [{ name: 'KM/L', data: vehicle.map((item) => item.value) }],
            plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
            xaxis: { categories: vehicle.map((item) => item.unit) },
            tooltip: { y: { formatter: (value) => `${formatNumber(value, 2)} KM/L` } },
        }, vehicle.length > 0, 'No KM/L data available for the selected period.');

        const equipment = dashboard.efficiency.equipment;
        renderChart('#equipment-efficiency-chart', {
            ...chartBase,
            chart: { ...chartBase.chart, type: 'bar', height: 235 },
            series: [{ name: 'L/HM', data: equipment.map((item) => item.value) }],
            plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
            xaxis: { categories: equipment.map((item) => item.unit) },
            tooltip: { y: { formatter: (value) => `${formatNumber(value, 2)} L/HM` } },
        }, equipment.length > 0, 'No L/HM data available for the selected period.');

        const unitTypes = dashboard.consumption_by_unit_type;
        const useDonut = unitTypes.length <= 6;
        renderChart('#operational-unit-type', useDonut ? {
            ...chartBase,
            chart: { ...chartBase.chart, type: 'donut', height: 300 },
            series: unitTypes.map((item) => item.value),
            labels: unitTypes.map((item) => item.label),
            legend: { position: 'bottom' },
            tooltip: { y: { formatter: (value, context) => `${formatNumber(value, 3)} L · ${formatNumber(unitTypes[context.seriesIndex].percentage, 1)}%` } },
        } : {
            ...chartBase,
            chart: { ...chartBase.chart, type: 'bar', height: 300 },
            series: [{ name: 'Consumption', data: unitTypes.map((item) => item.value) }],
            plotOptions: { bar: { horizontal: true } },
            xaxis: { categories: unitTypes.map((item) => item.label) },
            tooltip: { y: { formatter: (value) => `${formatNumber(value, 3)} L` } },
        }, unitTypes.length > 0, 'No unit category consumption in the selected period.');
    </script>
@endpush
