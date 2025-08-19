<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dn_input', function (Blueprint $table) {
            $table->dropColumn('quality_dn');
        });
    }

    public function down(): void
    {
        Schema::table('dn_input', function (Blueprint $table) {
            $table->integer('quality_dn')->nullable(); // kalau rollback, balikin lagi
        });
    }
};
