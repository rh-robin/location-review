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
        Schema::create('rental_statistics', function (Blueprint $table) {
            $table->id();

            $table->string('area_code')->index();
            $table->string('area_name')->index();
            $table->string('region')->nullable();

            $table->date('period_date')->index();
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedTinyInteger('month')->index();

            // Bedroom rents
            $table->unsignedInteger('rent_1_bed')->nullable();
            $table->unsignedInteger('rent_2_bed')->nullable();
            $table->unsignedInteger('rent_3_bed')->nullable();
            $table->unsignedInteger('rent_4plus_bed')->nullable();

            // Property type rents (future use)
            $table->unsignedInteger('rent_detached')->nullable();
            $table->unsignedInteger('rent_semidetached')->nullable();
            $table->unsignedInteger('rent_terraced')->nullable();
            $table->unsignedInteger('rent_flat')->nullable();

            $table->timestamps();

            $table->index(['area_name', 'period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_statistics');
    }
};
