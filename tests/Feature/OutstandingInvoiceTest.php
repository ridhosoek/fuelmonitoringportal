<?php

namespace Tests\Feature;

use App\Models\OutstandingInvoice;
use App\Models\User;
use App\Services\SapOutstandingInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutstandingInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_and_export_sap_outstanding_invoices(): void
    {
        $this->withoutVite();
        $this->seed();
        OutstandingInvoice::factory()->count(2)->create();
        $admin = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('outstanding-invoices.index', absolute: false))
            ->assertOk()
            ->assertSee('Outstanding Invoice')
            ->assertSee('Invoice dari SAP');

        $pdf = $this->actingAs($admin)->get(route('outstanding-invoices.index', ['download' => 'pdf'], false));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('OUTSTANDING INVOICE', $pdf->getContent());
    }

    public function test_sap_sync_is_idempotent_by_document_id(): void
    {
        $service = app(SapOutstandingInvoiceService::class);
        $document = [
            'sap_document_id' => '1900000001',
            'customer_code' => 'BNCT',
            'customer_name' => 'PT BNCT',
            'invoice_number' => 'INV-001',
            'document_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'currency' => 'IDR',
            'original_amount' => 1000000,
            'outstanding_amount' => 1000000,
            'status' => OutstandingInvoice::STATUS_OPEN,
        ];

        $service->synchronize([$document]);
        $service->synchronize([[...$document, 'outstanding_amount' => 500000]]);

        $this->assertSame(1, OutstandingInvoice::query()->count());
        $this->assertSame('500000.00', OutstandingInvoice::query()->sole()->outstanding_amount);
    }
}
