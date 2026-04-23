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
        Schema::create('estimation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('estimation_type'); // sale, rent, mortgage, remortgage
            $table->string('postcode')->nullable();
            $table->text('address')->nullable();
            $table->json('input');
            $table->json('output');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimation_requests');
    }
};
