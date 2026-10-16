<div class="row g-3 mb-3">
    @foreach([
        ['Total BBM', \App\Support\QuantityFormatter::format($reportData['totals']['fuel']).' L', 'bi-fuel-pump'],
        ['Transaksi', number_format($reportData['totals']['refueling_count']), 'bi-receipt'],
        ['Working Hours', $reportData['totals']['working_hours'] === null ? '-' : \App\Support\QuantityFormatter::format($reportData['totals']['working_hours']), 'bi-clock-history'],
        ['Mobil Tangki', \App\Support\QuantityFormatter::format($reportData['totals']['mobile_tanker_fuel']).' L', 'bi-truck'],
    ] as [$label, $value, $icon])
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="entry-pane-icon"><i class="bi {{ $icon }}"></i></span>
                    <div><div class="metric-label">{{ $label }}</div><div class="metric-value">{{ $value }}</div></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="section-title mb-1">Pemakaian BBM per Unit</h2>
            <div class="small text-secondary">{{ $reportData['date_from'] }} s/d {{ $reportData['date_to'] }} | {{ ucfirst($reportData['period']) }}</div>
        </div>
        <span class="badge text-bg-light border">Hanya transaksi POSTED</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Periode</th><th>Unit</th><th>Jenis</th><th class="text-end">BBM</th><th class="text-end">Trx</th><th class="text-end">Working</th><th class="text-end">Produksi</th><th class="text-end">L/Jam</th><th class="text-end">L/Produksi</th><th class="text-end">Langsung</th><th class="text-end">Mobil Tangki</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['rows'] as $row)
                    <tr>
                        <td>{{ $row['period_label'] }}</td>
                        <td><a href="{{ route('units.show', $row['unit']) }}">{{ $row['unit']->unit_code }} - {{ $row['unit']->unit_name }}</a></td>
                        <td>{{ $row['unit']->unit_type }}</td>
                        <td class="text-end">{{ \App\Support\QuantityFormatter::format($row['fuel']) }}</td>
                        <td class="text-end">{{ $row['refueling_count'] }}</td>
                        <td class="text-end">{{ $row['working_hours'] === null ? '-' : \App\Support\QuantityFormatter::format($row['working_hours']) }}</td>
                        <td class="text-end">{{ $row['production'] === null ? '-' : \App\Support\QuantityFormatter::format($row['production']) }}</td>
                        <td class="text-end">{{ $row['liter_per_hour'] === null ? '-' : \App\Support\QuantityFormatter::format($row['liter_per_hour']) }}</td>
                        <td class="text-end">{{ $row['liter_per_production'] === null ? '-' : \App\Support\QuantityFormatter::format($row['liter_per_production']) }}</td>
                        <td class="text-end">{{ \App\Support\QuantityFormatter::format($row['direct_fuel']) }}</td>
                        <td class="text-end">{{ \App\Support\QuantityFormatter::format($row['mobile_tanker_fuel']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-secondary py-4">Belum ada pemakaian BBM pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
