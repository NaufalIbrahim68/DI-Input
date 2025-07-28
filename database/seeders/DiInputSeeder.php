<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiInputModel;

class DiInputSeeder extends Seeder
{
    public function run()
    {
        DiInputModel::create([
            'Gate' => 'CEA1',
            'PO_Number' => 'PO123456',
            'Supplier_PN' => 'SUPPN001',
            'BAAN_PN' => 'BAAN001',
            'VISTEON_PN' => 'VIST001',
            'Supplier_PartN' => 'SPN001',
            'qty' => 100,
            'DI_Type' => 'Type A',
            'Deliv_Date' => '2025-06-10',
        ]);

        DiInputModel::create([
            'Gate' => 'B2',
            'PO_Number' => 'PO654321',
            'Supplier_PN' => 'SUPPN002',
            'BAAN_PN' => 'BAAN002',
            'VISTEON_PN' => 'VIST002',
            'Supplier_PartN' => 'SPN002',
            'qty' => 250,
            'DI_Type' => 'Type B',
            'Deliv_Date' => '2025-06-15',
        ]);

        // Tambahan berdasarkan data Excel dengan Qty terbanyak
        DiInputModel::create([
            'Gate' => 'C1',
            'PO_Number' => 'PO001',
            'Supplier_PN' => 'SUPP003',
            'BAAN_PN' => '37100-K2S -N011-M1',
            'VISTEON_PN' => 'VIST003',
            'Supplier_PartN' => 'SPN003',
            'qty' => 900,
            'DI_Type' => 'Type C',
            'Deliv_Date' => '2025-06-01',
        ]);

        DiInputModel::create([
            'Gate' => 'C2',
            'PO_Number' => 'PO002',
            'Supplier_PN' => 'SUPP004',
            'BAAN_PN' => '37100-K3V -N111-M1',
            'VISTEON_PN' => 'VIST004',
            'Supplier_PartN' => 'SPN004',
            'qty' => 900,
            'DI_Type' => 'Type C',
            'Deliv_Date' => '2025-06-01',
        ]);

        DiInputModel::create([
            'Gate' => 'C3',
            'PO_Number' => 'PO003',
            'Supplier_PN' => 'SUPP005',
            'BAAN_PN' => '37100-K0J -N710-M1',
            'VISTEON_PN' => 'VIST005',
            'Supplier_PartN' => 'SPN005',
            'qty' => 200,
            'DI_Type' => 'Type C',
            'Deliv_Date' => '2025-06-01',
        ]);

        DiInputModel::create([
            'Gate' => 'C4',
            'PO_Number' => 'PO004',
            'Supplier_PN' => 'SUPP006',
            'BAAN_PN' => '37100-K2S -N111-M1',
            'VISTEON_PN' => 'VIST006',
            'Supplier_PartN' => 'SPN006',
            'qty' => 300,
            'DI_Type' => 'Type C',
            'Deliv_Date' => '2025-06-01',
        ]);

        DiInputModel::create([
            'Gate' => 'C5',
            'PO_Number' => 'PO005',
            'Supplier_PN' => 'SUPP007',
            'BAAN_PN' => '37100-K0J -N610-M1',
            'VISTEON_PN' => 'VIST007',
            'Supplier_PartN' => 'SPN007',
            'qty' => 100,
            'DI_Type' => 'Type C',
            'Deliv_Date' => '2025-06-01',
        ]);

        DiInputModel::create([
            'Gate' => 'C6',
            'PO_Number' => 'PO006',
            'Supplier_PN' => 'SUPP008',
            'BAAN_PN' => '37100-K3V -N011-M1',
            'VISTEON_PN' => 'VIST008',
            'Supplier_PartN' => 'SPN008',
            'qty' => 100,
            'DI_Type' => 'Type C',
            'Deliv_Date' => '2025-06-01',
        ]);
    }
}
