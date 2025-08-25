<?php

namespace App\Exports;

use App\Models\DsInput;
use Maatwebsite\Excel\Concerns\FromCollection;

class DsInputExport implements FromCollection
{
    protected $tanggal;

    // Terima tanggal saat membuat instance export
    public function __construct($tanggal = null)
    {
        $this->tanggal = $tanggal;
    }

    public function collection()
    {
        $query = DsInput::query();

        // Jika tanggal dipilih, filter berdasarkan tanggal
        if ($this->tanggal) {
            $query->whereDate('di_received_date', $this->tanggal); // ganti kolom sesuai nama field tanggal
        }

        return $query->get();
    }
}
