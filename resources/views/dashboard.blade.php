@extends('layouts.app', ['pageTitle' => 'Operational Dashboard'])

@section('content')
    <form method="GET" class="card mb-3">
        <div class="card-body filter-grid">
            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from', $overview['filters']['dateFrom']) }}" class="form-control">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to', $overview['filters']['dateTo']) }}" class="form-control">
            </div>
            <div>
                <label class="form-label">Site</label>
                <select name="site_id" class="form-select">
                    <option value="">All Site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected((string) request('site_id') === (string) $site->id)>{{ $site->site_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">VHS</label>
                <select name="storage_id" class="form-select">
                    <option value="">All VHS</option>
                    @foreach($storages as $storage)
                        <option value="{{ $storage->id }}" @selected((string) request('storage_id') === (string) $storage->id)>{{ $storage->terminal?->terminal_name ?: 'Tanpa Terminal' }} / {{ $storage->storage_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        @foreach([
            ['label' => 'Current Stock', 'value' => \App\Support\QuantityFormatter::format($overview['kpis']['current_stock']), 'icon' => 'bi-droplet-half', 'class' => 'text-bg-primary'],
            ['label' => 'Period Receiving', 'value' => \App\Support\QuantityFormatter::format($overview['kpis']['today_receiving']), 'icon' => 'bi-box-arrow-in-down', 'class' => 'text-bg-success'],
            ['label' => 'Period Consumption', 'value' => \App\Support\QuantityFormatter::format($overview['kpis']['today_consumption']), 'icon' => 'bi-fuel-pump', 'class' => 'text-bg-warning'],
            ['label' => 'Period Transactions', 'value' => number_format($overview['kpis']['today_transactions']), 'icon' => 'bi-list-check', 'class' => 'text-bg-info'],
            ['label' => 'Active Units', 'value' => number_format($overview['kpis']['active_units']), 'icon' => 'bi-truck', 'class' => 'text-bg-secondary'],
            ['label' => 'Open FO', 'value' => number_format($overview['kpis']['open_fo']), 'icon' => 'bi-file-earmark-text', 'class' => 'text-bg-dark'],
            ['label' => 'Stock Variance', 'value' => \App\Support\QuantityFormatter::format($overview['kpis']['stock_variance']), 'icon' => 'bi-activity', 'class' => 'text-bg-danger'],
        ] as $kpi)
            <div class="col-12 col-sm-6 col-lg-3 col-xxl">
                <div class="small-box {{ $kpi['class'] }}">
                    <div class="inner">
                        <h3>{{ $kpi['value'] }}</h3>
                        <p>{{ $kpi['label'] }}</p>
                    </div>
                    <i class="small-box-icon bi {{ $kpi['icon'] }}"></i>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h2 class="section-title mb-0">Stock Trend</h2></div>
                <div class="card-body"><div id="stockTrendChart" class="chart-box"></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h2 class="section-title mb-0">Alerts</h2></div>
                <div class="card-body">
                    @forelse($overview['alerts'] as $alert)
                        <div class="alert alert-{{ $alert['level'] }} py-2">
                            <div class="fw-semibold">{{ $alert['title'] }}</div>
                            <div>{{ $alert['message'] }}</div>
                        </div>
                    @empty
                        <div class="text-secondary">No active alerts.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title mb-0">Receiving vs Consumption</h2></div>
                <div class="card-body"><div id="receivingConsumptionChart" class="chart-box"></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title mb-0">Top Unit Consumption</h2></div>
                <div class="card-body"><div id="topUnitsChart" class="chart-box"></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title mb-0">Consumption by Unit Type</h2></div>
                <div class="card-body"><div id="unitTypeChart" class="chart-box"></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title mb-0">KM / HM Efficiency</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div id="kmEfficiencyChart" class="chart-box"></div></div>
                        <div class="col-md-6"><div id="hmEfficiencyChart" class="chart-box"></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h2 class="section-title mb-0">Latest Posted Dispensing</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th><th>No</th><th>FO</th><th>Unit</th><th>VHS</th><th class="text-end">Liter</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($overview['latestDispensings'] as $dispensing)
                                <tr>
                                    <td>{{ $dispensing->dispensing_date?->toDateString() }}</td>
                                    <td><a href="{{ route('dispensings.show', $dispensing) }}">{{ $dispensing->dispensing_number }}</a></td>
                                    <td>{{ $dispensing->fuelOrder?->fo_number }}</td>
                                    <td>{{ $dispensing->unit?->unit_code }}</td>
                                    <td>{{ $dispensing->storage?->storage_name }}</td>
                                    <td class="text-end">{{ \App\Support\QuantityFormatter::format($dispensing->quantity_liter) }}</td>
                                    <td>@include('partials.status', ['status' => $dispensing->status])</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-secondary py-4">No posted dispensing in the selected period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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

        const overview = @json($overview);
        const baseOptions = {
            chart: { toolbar: { show: false }, fontFamily: 'Inter, Segoe UI, Arial, sans-serif' },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            colors: ['#0f766e', '#2563eb', '#d97706', '#b42318'],
        };

        const hasValues = (...series) => series.flat().some((value) => Number(value) !== 0);
        const renderEmptyState = (selector, message) => {
            const target = document.querySelector(selector);
            const emptyState = document.createElement('div');
            const icon = document.createElement('i');
            const text = document.createElement('span');

            emptyState.className = 'chart-empty-state';
            icon.className = 'bi bi-bar-chart';
            text.textContent = message;
            emptyState.append(icon, text);
            target.replaceChildren(emptyState);
        };

        if (hasValues(overview.stockTrend.values)) {
            new window.ApexCharts(document.querySelector('#stockTrendChart'), {
                ...baseOptions,
                chart: { ...baseOptions.chart, type: 'area', height: 285 },
                series: [{ name: 'Liter', data: overview.stockTrend.values }],
                xaxis: { categories: overview.stockTrend.labels },
            }).render();
        } else {
            renderEmptyState('#stockTrendChart', 'No stock movement in the selected period.');
        }

        if (hasValues(overview.receivingVsConsumption.receiving, overview.receivingVsConsumption.consumption)) {
            new window.ApexCharts(document.querySelector('#receivingConsumptionChart'), {
                ...baseOptions,
                chart: { ...baseOptions.chart, type: 'bar', height: 285 },
                series: [
                    { name: 'Receiving', data: overview.receivingVsConsumption.receiving },
                    { name: 'Consumption', data: overview.receivingVsConsumption.consumption },
                ],
                xaxis: { categories: overview.receivingVsConsumption.labels },
            }).render();
        } else {
            renderEmptyState('#receivingConsumptionChart', 'No receiving or consumption in the selected period.');
        }

        if (hasValues(overview.topUnits.values)) {
            new window.ApexCharts(document.querySelector('#topUnitsChart'), {
                ...baseOptions,
                chart: { ...baseOptions.chart, type: 'bar', height: 285 },
                series: [{ name: 'Liter', data: overview.topUnits.values }],
                xaxis: { categories: overview.topUnits.labels },
            }).render();
        } else {
            renderEmptyState('#topUnitsChart', 'No posted unit consumption in the selected period.');
        }

        if (hasValues(overview.consumptionByUnitType.values)) {
            new window.ApexCharts(document.querySelector('#unitTypeChart'), {
                ...baseOptions,
                chart: { ...baseOptions.chart, type: 'donut', height: 285 },
                series: overview.consumptionByUnitType.values,
                labels: overview.consumptionByUnitType.labels,
            }).render();
        } else {
            renderEmptyState('#unitTypeChart', 'No unit category consumption in the selected period.');
        }

        if (hasValues(overview.efficiency.km.values)) {
            new window.ApexCharts(document.querySelector('#kmEfficiencyChart'), {
                ...baseOptions,
                chart: { ...baseOptions.chart, type: 'bar', height: 285 },
                title: { text: 'KM/L' },
                series: [{ name: 'KM/L', data: overview.efficiency.km.values }],
                xaxis: { categories: overview.efficiency.km.labels },
            }).render();
        } else {
            renderEmptyState('#kmEfficiencyChart', 'No KM/L efficiency data in the selected period.');
        }

        if (hasValues(overview.efficiency.hm.values)) {
            new window.ApexCharts(document.querySelector('#hmEfficiencyChart'), {
                ...baseOptions,
                chart: { ...baseOptions.chart, type: 'bar', height: 285 },
                title: { text: 'L/HM' },
                series: [{ name: 'L/HM', data: overview.efficiency.hm.values }],
                xaxis: { categories: overview.efficiency.hm.labels },
            }).render();
        } else {
            renderEmptyState('#hmEfficiencyChart', 'No L/HM efficiency data in the selected period.');
        }
    </script>
@endpush
