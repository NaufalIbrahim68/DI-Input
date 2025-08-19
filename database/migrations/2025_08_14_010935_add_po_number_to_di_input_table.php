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
        if (!Schema::hasColumn('di_input', 'po_number')) {
            $table->string('po_number')->nullable();
        }
    });
}};
