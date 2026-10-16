@extends('layouts.app', ['pageTitle' => $bast->bast_number])

@section('actions')
    <div class="d-flex align-items-center gap-2 no-print">
        @if(auth()->user()->canWriteTransactions() && $bast->status === \App\Models\Bast::STATUS_DRAFT)
            <form method="POST" action="{{ route('basts.finalize', $bast) }}">
                @csrf
                <button class="btn btn-success btn-sm" type="submit">
                    <i class="bi bi-check2-circle me-1"></i>Finalize
                </button>
            </form>
        @endif
        <button class="btn btn-primary btn-sm" id="print-bast" type="button" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Print
        </button>
        <a href="{{ route('basts.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
@endsection

@section('content')
    @php
        $documentDate = $bast->bast_date?->copy()->locale('id')->translatedFormat('d F Y') ?? '-';
        $isDraft = $bast->status === \App\Models\Bast::STATUS_DRAFT;
    @endphp

    <div class="bast-screen-note no-print">
        <i class="bi bi-info-circle"></i>
        <span>Gunakan tombol <strong>Print</strong> untuk mencetak BAST dalam format A4.</span>
    </div>

    <article class="bast-document" aria-label="Berita Acara Serah Terima {{ $bast->bast_number }}">
        @if($isDraft)
            <div class="bast-watermark" aria-hidden="true">DRAFT</div>
        @endif

        <header class="bast-letterhead">
            <div class="bast-company">
                <img src="{{ asset('images/logo-indra-angkola.png') }}" alt="Logo PT Indra Angkola Energy" class="bast-company-logo">
                <div>
                    <div class="bast-company-name">PT INDRA ANGKOLA ENERGY</div>
                    <div class="bast-company-subtitle">Fuel Distribution &amp; Operational Services</div>
                </div>
            </div>
            <div class="bast-document-control">
                <span>Nomor Dokumen</span>
                <strong>{{ $bast->bast_number }}</strong>
                <span class="bast-status {{ strtolower($bast->status) }}">{{ $bast->status }}</span>
            </div>
        </header>

        <div class="bast-brand-rule"><span></span></div>

        <section class="bast-title-block">
            <h1>BERITA ACARA SERAH TERIMA</h1>
            <p>BAHAN BAKAR MINYAK</p>
        </section>

        <p class="bast-introduction">
            Pada tanggal <strong>{{ $documentDate }}</strong>, telah dilaksanakan serah terima bahan bakar minyak
            berdasarkan Fuel Order <strong>{{ $bast->fuelOrder?->fo_number ?? '-' }}</strong> antara
            <strong>PT Indra Angkola Energy</strong> sebagai pihak yang menyerahkan dan
            <strong>{{ $bast->customer?->customer_name ?? '-' }}</strong> sebagai pihak yang menerima,
            dengan rincian sebagai berikut:
        </p>

        <section class="bast-summary" aria-label="Informasi BAST">
            <dl>
                <div><dt>Nomor BAST</dt><dd>{{ $bast->bast_number }}</dd></div>
                <div><dt>Tanggal</dt><dd>{{ $documentDate }}</dd></div>
                <div><dt>Fuel Order</dt><dd>{{ $bast->fuelOrder?->fo_number ?? '-' }}</dd></div>
            </dl>
            <dl>
                <div><dt>Pelanggan</dt><dd>{{ $bast->customer?->customer_name ?? '-' }}</dd></div>
                <div><dt>Lokasi / Site</dt><dd>{{ $bast->site?->site_name ?? '-' }}</dd></div>
                <div><dt>Total Diserahkan</dt><dd><strong>{{ \App\Support\QuantityFormatter::formatIndonesian($bast->quantity_liter) }} Liter</strong></dd></div>
            </dl>
        </section>

        <section class="bast-section">
            <h2>Rincian Penyerahan</h2>
            <div class="table-responsive bast-table-wrap">
                <table class="bast-items-table">
                    <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th>Tanggal / Waktu</th>
                            <th>Dispensing</th>
                            <th>Unit</th>
                            <th>Sumber / Metode</th>
                            <th>Meter Awal &ndash; Akhir</th>
                            <th class="text-end">Liter</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bast->items as $item)
                            @php
                                $dispensing = $item->fuelDispensing;
                                $storage = $dispensing?->storage;
                                $deliveryMethod = match ($dispensing?->delivery_method) {
                                    \App\Models\FuelOrderItem::DELIVERY_DIRECT_STORAGE => 'Langsung di storage',
                                    \App\Models\FuelOrderItem::DELIVERY_MOBILE_TANKER => 'Mobil tangki',
                                    default => $dispensing?->delivery_method ?? '-',
                                };
                                $sourceName = $storage
                                    ? trim(($storage->terminal?->terminal_name ? $storage->terminal->terminal_name.' / ' : '').$storage->storage_name)
                                    : ($dispensing?->fuelTruck?->truck_code ?? '-');
                            @endphp
                            <tr data-bast-item>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    {{ $dispensing?->dispensing_date?->format('d-m-Y') ?? '-' }}
                                    <small>{{ $dispensing?->dispensing_time ? substr((string) $dispensing->dispensing_time, 0, 5) : '-' }}</small>
                                </td>
                                <td>
                                    @if($dispensing)
                                        <a href="{{ route('dispensings.show', $dispensing) }}">{{ $dispensing->dispensing_number }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $item->unit?->unit_code ?? '-' }}</strong>
                                    <small>{{ $item->unit?->unit_type ?? $item->unit?->unit_name ?? '-' }}</small>
                                </td>
                                <td>
                                    {{ $sourceName }}
                                    <small>{{ $deliveryMethod }}</small>
                                </td>
                                <td>
                                    @if($item->meter_type && $item->meter_type !== 'NONE')
                                        {{ $item->meter_type }}:
                                        {{ $dispensing?->previous_meter_value !== null ? \App\Support\QuantityFormatter::formatIndonesian($dispensing->previous_meter_value) : '-' }}
                                        &ndash;
                                        {{ $item->meter_value !== null ? \App\Support\QuantityFormatter::formatIndonesian($item->meter_value) : '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ \App\Support\QuantityFormatter::formatIndonesian($item->quantity_liter) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-end">TOTAL PENYERAHAN</th>
                            <th class="text-end">{{ \App\Support\QuantityFormatter::formatIndonesian($bast->quantity_liter) }} L</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section class="bast-notes">
            <h2>Catatan</h2>
            <p>{{ filled($bast->notes) ? $bast->notes : 'Tidak ada catatan tambahan.' }}</p>
        </section>

        <section class="bast-acceptance">
            <p>
                Dengan ditandatanganinya dokumen ini, bahan bakar tersebut dinyatakan telah diserahkan dan
                diterima sesuai rincian di atas dalam kondisi dan jumlah yang telah diperiksa oleh kedua belah pihak.
            </p>
        </section>

        <section class="bast-signatures" aria-label="Tanda tangan serah terima">
            <div class="bast-signature">
                <p>Pihak yang Menyerahkan,</p>
                <strong>PT Indra Angkola Energy</strong>
                <div class="bast-signature-space"></div>
                <div class="bast-signature-line">{{ $bast->operator_name ?: '-' }}</div>
                <small>Petugas / Operator</small>
            </div>
            <div class="bast-signature">
                <p>Pihak yang Menerima,</p>
                <strong>{{ $bast->customer?->customer_name ?? '-' }}</strong>
                <div class="bast-signature-space"></div>
                <div class="bast-signature-line">{{ $bast->received_by ?: '-' }}</div>
                <small>Penerima</small>
            </div>
        </section>

        <footer class="bast-document-footer">
            <span>{{ $bast->bast_number }}</span>
            <span>Dokumen dihasilkan oleh VHS Fuel Monitoring Portal</span>
            @if($bast->status === \App\Models\Bast::STATUS_FINAL && $bast->finalized_at)
                <span>Final: {{ $bast->finalized_at->format('d-m-Y H:i') }}</span>
            @else
                <span>Belum difinalisasi</span>
            @endif
        </footer>
    </article>
@endsection
