<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan kolom po_number di di_input jika belum ada
        if (!Schema::hasColumn('di_input', 'po_number')) {
            Schema::table('di_input', function (Blueprint $table) {
                $table->string('po_number', 50)->nullable()->after('supplier_part_number');
            });
        }

        // Perbaiki bentrok di ds_input (hanya tambahkan kolom jika belum ada)
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
        // Hapus kolom saat rollback
        Schema::table('di_input', function (Blueprint $table) {
            if (Schema::hasColumn('di_input', 'po_number')) {
                $table->dropColumn('po_number');
            }
        });

        Schema::table('ds_input', function (Blueprint $table) {
            if (Schema::hasColumn('ds_input', 'flag')) {
                $table->dropColumn('flag');
            }
            if (Schema::hasColumn('ds_input', 'di_received_date_string')) {
                $table->dropColumn('di_received_date_string');
            }
        });
    }
};
