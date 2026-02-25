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
        Schema::create('property_sales', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_id')->unique();
            $table->unsignedBigInteger('price');

            $table->date('transfer_date');
            $table->unsignedSmallInteger('year');

            $table->string('postcode');
            $table->string('postcode_district');
            $table->string('postcode_sector');

            $table->char('property_type', 1);     // D,S,T,F
            $table->char('new_build', 1)->nullable();
            $table->char('duration', 1);          // F,L

            $table->string('town')->nullable();
            $table->string('district')->nullable();
            $table->string('county')->nullable();

            $table->char('ppd_category', 1)->nullable();
            $table->char('record_status', 1)->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | PERFORMANCE INDEXES (CRITICAL)
            |--------------------------------------------------------------------------
            */

            // For Sector-level estimator
            $table->index(
                ['postcode_sector', 'property_type', 'duration', 'transfer_date'],
                'idx_sector_estimator'
            );

            // For District-level fallback
            $table->index(
                ['district', 'property_type', 'duration', 'transfer_date'],
                'idx_district_estimator'
            );

            // For County-level fallback
            $table->index(
                ['county', 'property_type', 'duration', 'transfer_date'],
                'idx_county_estimator'
            );

            // Optional: quick year filtering
            $table->index(['year'], 'idx_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_sales');
    }
};
