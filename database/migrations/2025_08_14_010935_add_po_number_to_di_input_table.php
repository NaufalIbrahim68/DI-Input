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
    Schema::table('di_input', function (Blueprint $table) {
        $table->string('po_number')->nullable()->after('supplier_part_number');
    });
}

public function down()
{
    Schema::table('di_input', function (Blueprint $table) {
        $table->dropColumn('po_number');
    });
}
};
