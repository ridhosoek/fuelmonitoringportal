<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outstanding_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('source_system')->default('SAP');
            $table->string('sap_document_id')->unique();
            $table->string('company_code')->nullable()->index();
            $table->string('customer_code')->index();
            $table->string('customer_name');
            $table->string('invoice_number')->index();
            $table->date('document_date')->index();
            $table->date('due_date')->index();
            $table->string('currency', 3)->default('IDR');
            $table->decimal('original_amount', 18, 2);
            $table->decimal('outstanding_amount', 18, 2);
            $table->string('payment_terms')->nullable();
            $table->string('status')->default('OPEN')->index();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->index(['customer_code', 'status', 'due_date'], 'outstanding_invoice_customer_status_due_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outstanding_invoices');
    }
};
