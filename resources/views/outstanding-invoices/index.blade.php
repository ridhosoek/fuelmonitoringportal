@extends('layouts.app', ['pageTitle' => 'Outstanding Invoice'])

@section('actions')
    <button type="submit" form="invoice-filter" name="download" value="pdf" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
    </button>
@endsection

@section('content')
    <form id="invoice-filter" method="GET" class="card mb-3">
        <div class="card-body filter-grid">
            <select name="customer_code" class="form-select" aria-label="Customer">
                <option value="">Semua Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->customer_code }}" @selected(($filters['customer_code'] ?? '') === $customer->customer_code)>{{ $customer->customer_code }} - {{ $customer->customer_name }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select" aria-label="Status invoice">
                <option value="">Semua Status</option>
                @foreach(['OPEN', 'PARTIAL', 'OVERDUE', 'PAID', 'DISPUTED'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <input type="date" name="due_from" value="{{ $filters['due_from'] ?? '' }}" class="form-control" aria-label="Due date from">
            <input type="date" name="due_to" value="{{ $filters['due_to'] ?? '' }}" class="form-control" aria-label="Due date to">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Invoice, customer, SAP document">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
    </form>

    <div class="row g-3 mb-3">
        @foreach([
            ['Jumlah Invoice', number_format($summary['invoice_count']), 'bi-receipt'],
            ['Total Outstanding', 'IDR '.number_format($summary['outstanding_total'], 0, ',', '.'), 'bi-cash-stack'],
            ['Overdue Invoice', number_format($summary['overdue_count']), 'bi-exclamation-triangle'],
            ['Nilai Overdue', 'IDR '.number_format($summary['overdue_total'], 0, ',', '.'), 'bi-calendar-x'],
        ] as $metric)
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="entry-pane-icon"><i class="bi {{ $metric[2] }}"></i></span>
                        <div class="min-w-0"><div class="metric-label">{{ $metric[0] }}</div><div class="metric-value invoice-metric">{{ $metric[1] }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-3">
            <h2 class="section-title mb-0">Invoice dari SAP</h2>
            <span class="text-secondary small">Sinkronisasi terakhir: {{ $summary['last_synced_at']?->format('d-m-Y H:i') ?: '-' }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Invoice</th><th>Customer</th><th>Document</th><th>Due Date</th><th class="text-end">Aging</th><th class="text-end">Original</th><th class="text-end">Outstanding</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td><div class="fw-semibold">{{ $invoice->invoice_number }}</div><div class="small text-secondary">SAP {{ $invoice->sap_document_id }}</div></td>
                            <td><div>{{ $invoice->customer_name }}</div><div class="small text-secondary">{{ $invoice->customer_code }}</div></td>
                            <td>{{ $invoice->document_date->format('d-m-Y') }}</td>
                            <td>{{ $invoice->due_date->format('d-m-Y') }}</td>
                            <td class="text-end">{{ $invoice->agingDays() }} hari</td>
                            <td class="text-end">{{ $invoice->currency }} {{ number_format((float) $invoice->original_amount, 2, ',', '.') }}</td>
                            <td class="text-end fw-semibold">{{ $invoice->currency }} {{ number_format((float) $invoice->outstanding_amount, 2, ',', '.') }}</td>
                            <td>@include('partials.status', ['status' => $invoice->operationalStatus()])</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-5">Belum ada data outstanding invoice dari SAP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $invoices->links() }}</div>
    </div>
@endsection
