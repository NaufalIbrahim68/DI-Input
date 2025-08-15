<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('ds_input', function (Blueprint $table) {
        if (!Schema::hasColumn('ds_input', 'flag')) {
            $table->tinyInteger('flag')->default(0);
        }
        if (!Schema::hasColumn('ds_input', 'di_received_date_string')) {
            $table->string('di_received_date_string', 30)->nullable();
        }
    });
}

public function down(): void
{
    Schema::table('ds_input', function (Blueprint $table) {
        $table->dropColumn(['flag', 'di_received_date_string']);
    });
}
};
