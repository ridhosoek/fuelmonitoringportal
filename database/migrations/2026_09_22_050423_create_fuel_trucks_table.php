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
        Schema::create('fuel_trucks', function (Blueprint $table) {
            $table->id();
            $table->string('truck_code')->unique();
            $table->string('plate_number')->unique();
            $table->decimal('capacity_liter', 15, 3);
            $table->string('driver_name')->nullable();
            $table->string('status')->default('ACTIVE')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_trucks');
    }
};
