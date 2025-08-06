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
        Schema::create('ds_input', function (Blueprint $table) {
            $table->string('ds_number')->primary();
            $table->string('gate')->nullable();
            $table->string('supplier_part_number')->nullable();
            $table->integer('qty')->nullable();
            $table->string('di_type')->nullable();
            $table->string('di_status')->nullable();
            $table->date('di_received_date')->nullable();
            $table->time('di_received_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ds_input');
    }
};
