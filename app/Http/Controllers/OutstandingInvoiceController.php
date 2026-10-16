<?php

namespace App\Http\Controllers;

use App\Models\OutstandingInvoice;
use App\Services\TabularPdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OutstandingInvoiceController extends Controller
{
    public function index(Request $request, TabularPdfService $pdf): View|Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['OPEN', 'PARTIAL', 'OVERDUE', 'PAID', 'DISPUTED'])],
            'customer_code' => ['nullable', 'string', 'max:50'],
            'due_from' => ['nullable', 'date'],
            'due_to' => ['nullable', 'date', 'after_or_equal:due_from'],
            'q' => ['nullable', 'string', 'max:100'],
            'download' => ['nullable', Rule::in(['pdf'])],
        ]);

        $query = OutstandingInvoice::query()
            ->when($filters['customer_code'] ?? null, fn (Builder $query, string $code) => $query->where('customer_code', $code))
            ->when($filters['due_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('due_date', '>=', $date))
            ->when($filters['due_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('due_date', '<=', $date))
            ->when($filters['q'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $searchQuery) => $searchQuery
                    ->where('invoice_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('sap_document_id', 'like', '%'.$search.'%'),
            ));

        if (($filters['status'] ?? null) === OutstandingInvoice::STATUS_OVERDUE) {
            $query->where('outstanding_amount', '>', 0)->whereDate('due_date', '<', today());
        } elseif (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        $allRows = (clone $query)->oldest('due_date')->get();
        $overdueRows = $allRows->filter(fn (OutstandingInvoice $invoice): bool => $invoice->operationalStatus() === OutstandingInvoice::STATUS_OVERDUE);
        $summary = [
            'invoice_count' => $allRows->count(),
            'outstanding_total' => (float) $allRows->sum('outstanding_amount'),
            'overdue_count' => $overdueRows->count(),
            'overdue_total' => (float) $overdueRows->sum('outstanding_amount'),
            'last_synced_at' => $allRows->max('last_synced_at'),
        ];

        if (($filters['download'] ?? null) === 'pdf') {
            $binary = $pdf->render(
                'OUTSTANDING INVOICE',
                'Sumber data SAP per '.now()->format('d-m-Y H:i'),
                ['Invoice', 'Customer', 'Doc Date', 'Due Date', 'Aging', 'Currency', 'Outstanding', 'Status'],
                $allRows->map(fn (OutstandingInvoice $invoice): array => [
                    $invoice->invoice_number,
                    $invoice->customer_code.' - '.$invoice->customer_name,
                    $invoice->document_date->toDateString(),
                    $invoice->due_date->toDateString(),
                    $invoice->agingDays().' hari',
                    $invoice->currency,
                    (float) $invoice->outstanding_amount,
                    $invoice->operationalStatus(),
                ])->all(),
                [
                    'Jumlah Invoice' => $summary['invoice_count'],
                    'Outstanding' => number_format($summary['outstanding_total'], 2, '.', ','),
                    'Overdue' => number_format($summary['overdue_total'], 2, '.', ','),
                ],
            );

            return response($binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="outstanding-invoice-'.now()->format('Ymd-His').'.pdf"',
                'Cache-Control' => 'no-store, no-cache',
            ]);
        }

        return view('outstanding-invoices.index', [
            'invoices' => $query->oldest('due_date')->paginate(20)->withQueryString(),
            'customers' => OutstandingInvoice::query()->select(['customer_code', 'customer_name'])->distinct()->orderBy('customer_name')->get(),
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }
}
