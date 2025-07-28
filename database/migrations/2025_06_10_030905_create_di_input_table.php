<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiInputTable extends Migration
{
    public function up()
    {
        Schema::create('di_input', function (Blueprint $table) {
            $table->id();
              $table->string('di_no')->nullable();
            $table->string('gate')->nullable();
            $table->string('po_number')->nullable();
            $table->string('po_item')->nullable();
            $table->string('supplier_id')->nullable();
            $table->string('supplier_desc')->nullable();
            $table->string('supplier_part_number')->nullable();
            $table->string('baan_pn')->nullable();
            $table->string('visteon_pn')->nullable();
            $table->string('supplier_part_number_desc')->nullable();
            $table->integer('qty')->nullable();
            $table->string('uom')->nullable();
            $table->string('critical_part')->nullable();
            $table->string('flag_subcontracting')->nullable();
            $table->string('po_status')->nullable();
            $table->date('latest_gr_date_po')->nullable();
            $table->string('di_type')->nullable();
            $table->string('di_status')->nullable();
            $table->date('di_received_date')->nullable();
            $table->string('di_received_time')->nullable();
            $table->date('di_created_date')->nullable();
            $table->string('di_created_time')->nullable();
            $table->string('di_no_original')->nullable();
            $table->string('di_no_split')->nullable();
            $table->string('dn_no')->nullable();
            $table->string('plant_id_dn')->nullable();
            $table->string('plant_desc_dn')->nullable();
            $table->string('supplier_id_dn')->nullable();
            $table->string('supplier_desc_dn')->nullable();
            $table->string('plant_supplier_dn')->nullable();
             $table->timestamps(); // created_at & updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('di_input');
    }
}
