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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('customer_name');
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('ACTIVE')->index();
            $table->timestamps();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('site_code');
            $table->string('site_name');
            $table->text('address')->nullable();
            $table->string('status')->default('ACTIVE')->index();
            $table->timestamps();

            $table->unique(['customer_id', 'site_code']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('storage_code');
            $table->string('storage_name');
            $table->decimal('capacity_liter', 15, 3);
            $table->decimal('minimum_stock_liter', 15, 3)->default(0);
            $table->string('status')->default('ACTIVE')->index();
            $table->timestamps();

            $table->unique(['site_id', 'storage_code']);
            $table->index(['site_id', 'status']);
        });

        Schema::create('fuel_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('source_type');
            $table->text('address')->nullable();
            $table->string('status')->default('ACTIVE')->index();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('unit_code');
            $table->string('unit_name');
            $table->string('plate_number')->nullable();
            $table->string('unit_type');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('meter_type');
            $table->string('status')->default('ACTIVE')->index();
            $table->timestamps();

            $table->unique(['site_id', 'unit_code']);
            $table->index(['customer_id', 'site_id', 'unit_type']);
            $table->index(['site_id', 'status']);
        });

        Schema::create('fuel_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number')->unique();
            $table->date('receiving_date')->index();
            $table->time('receiving_time')->nullable();
            $table->foreignId('site_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('storage_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('fuel_source_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('delivery_document_number')->nullable();
            $table->string('truck_plate_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->decimal('quantity_liter', 15, 3);
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('DRAFT')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'storage_id', 'status']);
            $table->index(['storage_id', 'receiving_date']);
        });

        Schema::create('fuel_orders', function (Blueprint $table) {
            $table->id();
            $table->string('fo_number')->unique();
            $table->date('fo_date')->index();
            $table->foreignId('customer_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('requested_by');
            $table->text('notes')->nullable();
            $table->string('status')->default('DRAFT')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'site_id', 'status']);
        });

        Schema::create('fuel_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_order_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('requested_quantity', 15, 3);
            $table->text('notes')->nullable();
            $table->string('status')->default('ACTIVE')->index();
            $table->timestamps();

            $table->index(['fuel_order_id', 'unit_id']);
        });

        Schema::create('fuel_dispensings', function (Blueprint $table) {
            $table->id();
            $table->string('dispensing_number')->unique();
            $table->date('dispensing_date')->index();
            $table->time('dispensing_time')->nullable();
            $table->foreignId('fuel_order_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('fuel_order_item_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('storage_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('meter_type');
            $table->decimal('previous_meter_value', 15, 3)->nullable();
            $table->decimal('meter_value', 15, 3);
            $table->decimal('quantity_liter', 15, 3);
            $table->string('operator_name');
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('DRAFT')->index();
            $table->text('override_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->index(['fuel_order_id', 'status']);
            $table->index(['fuel_order_item_id', 'status']);
            $table->index(['storage_id', 'dispensing_date']);
            $table->index(['unit_id', 'dispensing_date']);
        });

        Schema::create('basts', function (Blueprint $table) {
            $table->id();
            $table->string('bast_number')->unique();
            $table->date('bast_date')->index();
            $table->foreignId('fuel_order_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('received_by');
            $table->string('operator_name')->nullable();
            $table->decimal('quantity_liter', 15, 3)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('DRAFT')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['fuel_order_id', 'status']);
            $table->index(['customer_id', 'site_id', 'status']);
        });

        Schema::create('bast_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bast_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('fuel_dispensing_id')->unique()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('quantity_liter', 15, 3);
            $table->string('meter_type');
            $table->decimal('meter_value', 15, 3);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->date('movement_date')->index();
            $table->time('movement_time')->nullable();
            $table->foreignId('storage_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('movement_type')->index();
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_number');
            $table->decimal('quantity_liter', 15, 3);
            $table->decimal('balance_after', 15, 3);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['storage_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('daily_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->date('reconciliation_date')->index();
            $table->foreignId('site_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('storage_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('opening_stock', 15, 3);
            $table->decimal('total_receiving', 15, 3);
            $table->decimal('total_dispensing', 15, 3);
            $table->decimal('total_adjustment', 15, 3)->default(0);
            $table->decimal('system_closing_stock', 15, 3);
            $table->decimal('physical_closing_stock', 15, 3);
            $table->decimal('variance', 15, 3);
            $table->decimal('variance_percentage', 8, 3)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('DRAFT')->index();
            $table->foreignId('prepared_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['reconciliation_date', 'storage_id']);
            $table->index(['site_id', 'storage_id', 'status']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('attachable');
            $table->string('module');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['module', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('action')->index();
            $table->string('module')->index();
            $table->nullableMorphs('auditable');
            $table->string('reference')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['module', 'action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('daily_reconciliations');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('bast_items');
        Schema::dropIfExists('basts');
        Schema::dropIfExists('fuel_dispensings');
        Schema::dropIfExists('fuel_order_items');
        Schema::dropIfExists('fuel_orders');
        Schema::dropIfExists('fuel_receivings');
        Schema::dropIfExists('units');
        Schema::dropIfExists('fuel_sources');
        Schema::dropIfExists('storages');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('customers');
    }
};
