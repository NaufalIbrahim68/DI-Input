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
    Schema::table('ds_input', function (Blueprint $table) {
        if (!Schema::hasColumn('ds_input', 'di_id')) {
            $table->unsignedBigInteger('di_id')->nullable()->after('di_received_time');
        }

        // Pakai NO ACTION (default), bukan cascade
        $table->foreign('di_id')
              ->references('id')
              ->on('di_input')
              ->onDelete('no action')   // ⬅ penting
              ->onUpdate('no action');
    });
}

public function down(): void
{
    Schema::table('ds_input', function (Blueprint $table) {
        $table->dropForeign(['di_id']);
        $table->dropColumn('di_id');
    });
}

};
