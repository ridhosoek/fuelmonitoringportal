@php($annualMetrics = $reportData['totals']['total'])

<div class="row g-3 mb-3">
    @foreach([
        ['Total BBM', $annualMetrics['fuel'], 'L'],
        ['Working Hours', $annualMetrics['working_hours'], 'jam'],
        ['Konsumsi', $annualMetrics['liter_per_hour'], 'L/jam'],
        ['Frekuensi Dispensing', $annualMetrics['refueling_count'], 'transaksi'],
    ] as [$label, $value, $unit])
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="metric-label">{{ $label }}</div>
                    <div class="metric-value">
                        {{ $value === null ? '-' : \App\Support\QuantityFormatter::format($value) }}
                        <small class="text-secondary fs-6">{{ $unit }}</small>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card operational-report-card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="section-title mb-1">Rekap Pemakaian BBM, Produksi, dan Working Hours {{ $reportData['year'] }}</h2>
            <div class="small text-secondary">{{ $reportData['site_name'] }} | {{ $reportData['storage_name'] }}</div>
        </div>
        <span class="badge text-bg-light border">Rasio dihitung dari total periode</span>
    </div>
    <div class="table-responsive operational-report-scroll">
        <table class="table table-bordered table-sm mb-0 operational-report-table">
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Kelompok Alat</th>
                    <th rowspan="2">Unit</th>
                    <th rowspan="2">VHS</th>
                    <th rowspan="2">Satuan Produksi</th>
                    @foreach($reportData['months'] as $monthName)
                        <th colspan="5" class="text-center operational-period-heading">{{ $monthName }}</th>
                    @endforeach
                    <th colspan="5" class="text-center operational-total-heading">TOTAL SETAHUN</th>
                </tr>
                <tr>
                    @foreach(range(1, 13) as $period)
                        <th class="text-end">Liter</th>
                        <th class="text-end">Produksi</th>
                        <th class="text-end">L/Produksi</th>
                        <th class="text-end">Working Hours</th>
                        <th class="text-end">L/Jam</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php($sequence = 1)
                @forelse($reportData['groups'] as $group)
                    <tr class="operational-group-row">
                        <th colspan="70">{{ $group['label'] }}</th>
                    </tr>
                    @foreach($group['rows'] as $row)
                        <tr>
                            <td class="text-center">{{ $sequence++ }}</td>
                            <td>{{ $row['unit_type'] }}</td>
                            <td class="fw-semibold">{{ $row['unit']->unit_code }} - {{ $row['unit']->unit_name }}</td>
                            <td>{{ $row['storage']?->storage_name ?? '-' }}</td>
                            <td>{{ $row['production_unit'] ?? '-' }}</td>
                            @foreach([...array_values($row['months']), $row['total']] as $metrics)
                                @foreach([
                                    $metrics['refueling_count'] > 0 ? $metrics['fuel'] : null,
                                    $metrics['production'],
                                    $metrics['liter_per_production'],
                                    $metrics['working_hours'],
                                    $metrics['liter_per_hour'],
                                ] as $value)
                                    <td class="text-end">{{ $value === null ? '-' : \App\Support\QuantityFormatter::format($value) }}</td>
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                    <tr class="operational-subtotal-row">
                        <th colspan="5">Jumlah {{ $group['label'] }}</th>
                        @foreach([...array_values($group['totals']['months']), $group['totals']['total']] as $metrics)
                            @foreach([
                                $metrics['refueling_count'] > 0 ? $metrics['fuel'] : null,
                                $metrics['production'],
                                $metrics['liter_per_production'],
                                $metrics['working_hours'],
                                $metrics['liter_per_hour'],
                            ] as $value)
                                <th class="text-end">{{ $value === null ? '-' : \App\Support\QuantityFormatter::format($value) }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="70" class="text-center text-secondary py-4">Belum ada data operasional untuk filter ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5">TOTAL KESELURUHAN</th>
                    @foreach([...array_values($reportData['totals']['months']), $reportData['totals']['total']] as $metrics)
                        @foreach([
                            $metrics['refueling_count'] > 0 ? $metrics['fuel'] : null,
                            $metrics['production'],
                            $metrics['liter_per_production'],
                            $metrics['working_hours'],
                            $metrics['liter_per_hour'],
                        ] as $value)
                            <th class="text-end">{{ $value === null ? '-' : \App\Support\QuantityFormatter::format($value) }}</th>
                        @endforeach
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
</div>
