<?php

namespace App\Exports;

use App\Models\DsInput;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class DsInputExport implements FromCollection, WithHeadings
{
    protected $tanggal;

    public function __construct($tanggal = null)
    {
        $this->tanggal = $tanggal;
    }

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
            'di_received_date_string',
            'di_received_time',
            'qty',
            'qty_delivery',
            'qty_prep',
            'dn_number'
        ]);

        return $data->map(function ($item) {
            return [
                'ds_number'               => $item->ds_number,
                'gate'                    => $item->gate,
                'supplier_part_number'    => $item->supplier_part_number,
                'di_type'                 => $item->di_type,
                'di_received_date_string' => $item->di_received_date_string
                                                ? Carbon::parse($item->di_received_date_string)->format('d-m-Y')
                                                : '-',
                'di_received_time'        => $item->di_received_time ?? '-',
                'qty'                     => $item->qty,
                'qty_delivery'            => ($item->qty_delivery ?? 0) > 0 ? $item->qty_delivery : '',
                'qty_prep'                => ($item->qty_prep ?? 0) > 0 ? $item->qty_prep : '',
                'dn_number'               => ($item->dn_number ?? 0) > 0 ? $item->dn_number : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'DS Number',
            'Gate',
            'Supplier Part Number',
            'DI Type',
            'Received Date',
            'Received Time',
            'Qty',
            'Qty Delivery',
            'Qty Prep',
            'DN Number',
        ];
    }
}
