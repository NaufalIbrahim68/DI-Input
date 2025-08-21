<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DnExport implements FromCollection, WithHeadings
{
    protected $selectedDate;

    public function __construct($selectedDate)
    {
        $this->selectedDate = $selectedDate;
    }

    public function collection()
    {
        $query = DB::table('dn_input as d')
            ->leftJoin('ds_input as ds', 'd.ds_number', '=', 'ds.ds_number')
            ->select(
                'd.ds_number',
                'd.dn_number',
                'd.qty_dn',
                'ds.qty as qty_ds',
                'ds.di_received_date_string as received_date'
            );

        // filter tanggal wajib
        if (!empty($this->selectedDate)) {
            $query->whereDate('ds.di_received_date_string', $this->selectedDate);
        } else {
            // optional: kembalikan collection kosong jika tanggal tidak ada
            return collect([]);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'DS Number',
            'DN Number',
            'Qty DN',
            'Qty DS',
            'Received_Date',
        ];
    }
}
