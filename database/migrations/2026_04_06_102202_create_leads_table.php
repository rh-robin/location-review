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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // plan, consultation
            $table->string('source'); // sales, rent, mortgage, remortgage
            $table->string('email')->nullable();
            $table->string('planning_time')->nullable(); // asap, 1-3 months, 3-6 months
            $table->text('note')->nullable();
            $table->integer('number_of_experts')->nullable();
            $table->string('name')->nullable();
            $table->string('preferred_contact_method')->nullable(); // call, email
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time')->nullable();
            $table->json('calculation_input')->nullable();
            $table->json('calculation_output')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
