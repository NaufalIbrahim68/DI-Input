@extends('layouts.app')

@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.min.css">

<div class="container-fluid">
    <div class="bg-white p-4 shadow rounded">
        {{-- Flash message --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div> 
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Form Pilih Tanggal --}}
        <div class="d-flex justify-content-center mb-4">
            <form method="GET" action="{{ route('dn.index') }}" class="d-flex align-items-center gap-3 p-3 border rounded bg-light">
                <label class="mb-0 fw-semibold text-black">Pilih Tanggal:</label>
                <input type="date" name="selected_date" class="form-control" style="width: 200px;" 
                       value="{{ $selectedDate ?? '' }}">
                <button type="submit" class="btn btn-primary px-4">
                    Tampilkan Data DN
                </button>
                <a href="{{ route('dn.index') }}" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        {{-- Info Messages --}}
        @if(!$selectedDate)
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                Pilih tanggal untuk menampilkan data DN.
            </div>
        @elseif($dnData->isEmpty())
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Tidak ada data DN untuk tanggal 
                <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>.
            </div>
        @else
            <div class="alert alert-success text-center">
                <i class="fas fa-check-circle me-2"></i>
                Menampilkan data DN untuk tanggal 
                <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>
                - Ditemukan <strong>{{ $dnData->count() }}</strong> data
            </div>
        @endif

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('dn.export_excel', request()->query()) }}" class="btn btn-success btn-sm">
        <i class="fas fa-file-excel"></i> Export Excel
    </a>
    <a href="{{ route('dn.export_pdf', request()->query()) }}" class="btn btn-danger btn-sm ms-2">
        <i class="fas fa-file-pdf"></i> Cetak PDF
    </a>
</div>


        <div class="table-responsive">
            <table id="example" class="display w-full text-xs leading-tight">
                <thead class="bg-white">
                    <tr>
                        <th class="text-black">DS Number</th>
                        <th class="text-black text-center">Qty DS</th>
                        <th class="text-black">DN Number</th>
                        <th class="text-black text-center">Qty DN</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$selectedDate)
                        <tr>
                            <td colspan="4" class="text-center">Silakan pilih tanggal terlebih dahulu.</td>
                        </tr>
                    @elseif($dnData->isEmpty())
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada data DN untuk tanggal tersebut.</td>
                        </tr>
                    @else
                        @foreach($dnData as $row)
                            <tr>
                                <td class="text-black">{{ $row->ds_number }}</td>
                                <td class="text-black text-center">{{ $row->qty_ds ?? '-' }}</td>
                                <td class="text-black">{{ $row->dn_number ?? '-' }}</td>
                                <td class="text-black text-center">{{ $row->qty_dn ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- jQuery & DataTables -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

        <script>
            $(document).ready(function () {
                $('#example').DataTable({
                    "columnDefs": [
                        { "defaultContent": "-", "targets": "_all" }
                    ],
                    "responsive": true,
                    "scrollX": true,
                    "searching": false, 
                    "paging": false      
                });
            });
        </script>

    </div>
</div>
@endsection
