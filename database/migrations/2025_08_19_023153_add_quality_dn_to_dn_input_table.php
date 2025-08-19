<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dn_input', function (Blueprint $table) {
            $table->integer('quality_dn')->nullable()->after('dn_number');
            // kalau string: $table->string('quality_dn', 100)->nullable()->after('dn_number');
        });
    }

    public function down()
    {
        Schema::table('dn_input', function (Blueprint $table) {
            $table->dropColumn('quality_dn');
        });
    }
};
