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
        Schema::table('units', function (Blueprint $table) {
            $table->string('production_unit', 30)->nullable()->after('meter_type');
        });

        Schema::table('fuel_dispensings', function (Blueprint $table) {
            $table->decimal('production_quantity', 15, 3)->nullable()->after('quantity_liter');
            $table->string('production_unit', 30)->nullable()->after('production_quantity');
            $table->index(
                ['status', 'dispensing_date', 'unit_id'],
                'fuel_dispensings_operational_report_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_dispensings', function (Blueprint $table) {
            $table->dropIndex('fuel_dispensings_operational_report_index');
            $table->dropColumn(['production_quantity', 'production_unit']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('production_unit');
        });
    }
};
