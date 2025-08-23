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
            <form method="GET" action="{{ route('ds_input.index') }}" 
                  class="d-flex align-items-center gap-3 p-3 border rounded bg-light">
                <label class="mb-0 fw-semibold text-black">Pilih Tanggal:</label>
                <input type="date" name="selected_date" class="form-control" style="width: 200px;" 
                       value="{{ $selectedDate ?? '' }}">
                <button type="submit" class="btn btn-primary px-4">
                    Generate Data DS
                </button>
            </form>
        </div>

        {{-- Info Messages --}}
        @if(!$selectedDate)
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                Pilih tanggal untuk menampilkan data DS.
            </div>
        @elseif($dsData->isEmpty())
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Tidak ada data DS untuk tanggal 
                <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>.
            </div>
        @else
            <div class="alert alert-success text-center">
                <i class="fas fa-check-circle me-2"></i>
                Menampilkan data DS untuk tanggal 
                <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>
                - Ditemukan <strong>{{ $dsData->count() }}</strong> data
            </div>
        @endif

        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('ds_input.export_excel', request()->query()) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a href="{{ route('ds_input.export_pdf', request()->query()) }}" class="btn btn-danger btn-sm ms-2">
                <i class="fas fa-file-pdf"></i> Cetak PDF
            </a>
        </div>

        <div class="table-responsive">
            <table id="example" class="display w-full text-xs leading-tight">
                <thead class="bg-white">
                    <tr>
                        <th class="text-black">DS Number</th>
                        <th class="text-black">Gate</th>
                        <th class="text-black">Supplier Part Number</th>
                        <th class="text-black text-center">Qty</th>
                        <th class="text-black">DI Type</th>
                        <th class="text-black">DI Status</th>
                        <th class="text-black">Received Date</th>
                        <th class="text-black">Received Time</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$selectedDate)
                        <tr>
                            <td colspan="8" class="text-center">Silakan pilih tanggal terlebih dahulu.</td>
                        </tr>
                    @elseif($dsData->isEmpty())
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data DS untuk tanggal tersebut.</td>
                        </tr>
                    @else
                        @foreach($dsData as $row)
                            <tr>
                                <td class="text-black">{{ $row->ds_number }}</td>
                                <td class="text-black">{{ $row->gate ?? '-' }}</td>
                                <td class="text-black">{{ $row->supplier_part_number ?? '-' }}</td>
                                <td class="text-black text-center">{{ $row->qty ?? '-' }}</td>
                                <td class="text-black">{{ $row->di_type ?? '-' }}</td>
                                <td class="text-black">{{ $row->di_status ?? '-' }}</td>
                                <td class="text-black">
                                    {{ $row->di_received_date ? \Carbon\Carbon::parse($row->di_received_date)->format('d-m-Y') : '-' }}
                                </td>
                                <td class="text-black">{{ $row->di_received_time ?? '-' }}</td>
                               {{-- Tombol Edit (ke halaman edit) --}}
<a href="{{ route('ds_input.edit', $ds->ds_number) }}" class="btn btn-sm bg-white">✏️</a>

{{-- Tombol Delete --}}
<form action="{{ route('ds_input.destroy', $ds->ds_number) }}" method="POST" style="display: inline-block;" 
      onsubmit="return confirm('Yakin ingin menghapus data ini?');">
    @csrf
    @method('DELETE')
    {{-- untuk menjaga filter & pagination saat kembali --}}
    <input type="hidden" name="tanggal" value="{{ request('tanggal') }}">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <input type="hidden" name="page" value="{{ request('page') }}">
    <button type="submit" class="btn btn-sm">🗑️</button>
</form>
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
