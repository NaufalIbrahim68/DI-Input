<?php

namespace App\Exports;

use App\Models\DsInput;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class DsInputExport implements FromCollection, WithHeadings
{
    protected $tanggal;

    /**
     * Terima tanggal saat membuat instance export
     *
     * @param string|null $tanggal
     */
    public function __construct($tanggal = null)
    {
        $this->tanggal = $tanggal;
    }

    /**
     * Ambil data untuk diexport
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = DsInput::query();

        // Filter berdasarkan tanggal jika diberikan
        if ($this->tanggal) {
            $query->whereDate('di_received_date_string', $this->tanggal);
        }

        $data = $query->get([
            'ds_number',
            'gate',
            'supplier_part_number',
              'di_type',
            'di_received_time',
            'di_received_date_string',
            'qty'
          
        ]);

        // Ubah setiap row menjadi array & format tanggal
        return $data->map(function ($item) {
            return [
                'ds_number'               => $item->ds_number,
                'gate'                    => $item->gate,
                'supplier_part_number'    => $item->supplier_part_number,
                 'di_type'                 => $item->di_type,
                'di_received_time'        => $item->di_received_time,
                'di_received_date_string' => $item->di_received_date_string
                                                ? Carbon::parse($item->di_received_date_string)->format('d-m-Y')
                                                : null,
                'qty'                     => $item->qty,
            ];
        });
    }

    /**
     * Header Excel
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'DS Number',
            'Gate',
            'Supplier Part Number',
             'DI Type',
            'DI Received Time',
            'DI Received Date',
            'Qty',
           
        ];
    }
}
