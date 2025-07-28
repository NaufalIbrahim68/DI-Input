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
       Schema::create('di_partnumber', function (Blueprint $table) {
        $table->id();
        $table->string('supplier_pn');
        $table->string('baan_pn')->nullable();
        $table->string('visteon_pn')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('di_partnumber');
    }
};
