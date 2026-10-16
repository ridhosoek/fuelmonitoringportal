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
        Schema::table('storages', function (Blueprint $table) {
            $table->foreignId('terminal_id')->nullable()->after('site_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->string('mobility_type')->default('MOVABLE')->after('unit_type')->index();
        });

        Schema::table('fuel_orders', function (Blueprint $table) {
            $table->time('requested_time')->nullable()->after('fo_date');
            $table->date('planned_delivery_date')->nullable()->after('requested_time')->index();
        });

        Schema::table('fuel_order_items', function (Blueprint $table) {
            $table->string('delivery_method')->default('DIRECT_STORAGE')->after('unit_id')->index();
        });

        Schema::table('fuel_dispensings', function (Blueprint $table) {
            $table->string('delivery_method')->default('DIRECT_STORAGE')->after('unit_id')->index();
            $table->foreignId('fuel_truck_id')->nullable()->after('delivery_method')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->index(['delivery_method', 'dispensing_date'], 'fuel_dispensings_delivery_report_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_dispensings', function (Blueprint $table) {
            $table->dropIndex('fuel_dispensings_delivery_report_index');
            $table->dropConstrainedForeignId('fuel_truck_id');
            $table->dropColumn('delivery_method');
        });

        Schema::table('fuel_order_items', function (Blueprint $table) {
            $table->dropColumn('delivery_method');
        });

        Schema::table('fuel_orders', function (Blueprint $table) {
            $table->dropColumn(['requested_time', 'planned_delivery_date']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('mobility_type');
        });

        Schema::table('storages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('terminal_id');
        });
    }
};
