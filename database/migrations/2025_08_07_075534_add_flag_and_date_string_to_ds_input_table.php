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
        $table->tinyInteger('flag')->default(0)->after('updated_at');
        $table->string('di_received_date_string', 30)->nullable()->after('flag');
    });
}

public function down(): void
{
    Schema::table('ds_input', function (Blueprint $table) {
        $table->dropColumn(['flag', 'di_received_date_string']);
    });
}
};
