@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.min.css">


@php
    // ambil dari query ?tanggal=YYYY-MM-DD
    $selectedDate = request('tanggal');
@endphp

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show shadow-sm rounded-3">
        <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="mb-3">
    {{-- pakai GET agar pagination ikut --}}
    <form method="GET" action="{{ route('ds_input.index') }}">
        <div class="d-flex align-items-center gap-2">
            <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control" style="width:200px;" required>
            <button type="submit" class="btn btn-success">Filter Tanggal</button>
        </div>
    </form>
</div>

{{-- Info tanggal & jumlah DS --}}
@if(!empty($selectedDate))
    <div class="alert alert-info text-center">
        Menampilkan data DS untuk tanggal
        <strong>{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</strong>
        - Ditemukan <strong>{{ $dsInputs->count() }}</strong> data
    </div>
@endif

{{-- Tombol Export (bawa query ?tanggal=) --}}
        <div class="d-flex gap-2 mb-3">
           <a href="{{ route('ds_input.export.pdf', ['tanggal' => request('tanggal')]) }}" class="btn btn-danger btn-sm">
               📄 Export PDF
            </a>
          <a href="{{ route('ds_input.export.excel', ['tanggal' => request('tanggal')]) }}" class="btn btn-success btn-sm">
               📊 Export Excel
            </a>
        </div>


<div class="table-responsive" style="overflow-x:auto; width:100%;">
    <table id="example" class="table table-bordered table-sm bg-white small">
        <thead class="bg-black text-white">
            <tr>
                <th>No</th>
                <th>DS Number</th>
                <th>Gate</th>
                <th>DI Type</th>
                <th>Supplier Part Number</th>
                <th>Received Date</th>
                <th>Received Time</th>
                <th>Status Preparation</th>
                <th>Status Delivery</th>
                <th>Qty</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @if($dsInputs && $dsInputs->count() > 0)
            @foreach ($dsInputs as $index => $ds)
                <tr>
                   <td class="text-black">{{ $index + 1 }}</td>
                    <td class="text-black">{{ $ds->ds_number ?? '-' }}</td>
                    <td class="text-black">{{ $ds->gate ?? '-' }}</td>
                    <td class="text-black">{{ $ds->di_type ?? '-' }}</td>
                    <td class="text-black">{{ $ds->supplier_part_number ?? '-' }}</td>
                    <td class="text-black">
                        {{ !empty($ds->di_received_date_string)
                            ? \Carbon\Carbon::parse($ds->di_received_date_string)->format('d-m-Y')
                            : '-' }}
                    </td>
                    <td class="text-black">{{ $ds->di_received_time ?? '-' }}</td>
                    <td class="text-black">
                        @if($ds->flag_prep == 1)
                            <span class="badge bg-success text-white">Completed</span>
                        @else
                            <span class="badge bg-primary text-white">Non Completed</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $totalDn = (int) \App\Models\Dn_Input::where('ds_number', $ds->ds_number)->sum('qty_dn');
                            $qtyDs = (int) $ds->qty;
                            if ($totalDn == 0) $status = 'not completed';
                            elseif ($totalDn < $qtyDs) $status = 'partial';
                            else $status = 'completed';
                        @endphp
                        @switch($status)
                            @case('completed') <span class="badge bg-success text-white">Completed</span> @break
                            @case('partial')   <span class="badge bg-warning text-white">Partial</span>   @break
                            @default            <span class="badge bg-primary text-white">Non Completed</span>
                        @endswitch
                    </td>
                    <td class="text-black">{{ $ds->qty ?? '-' }}</td>
                     <td class="d-flex gap-2">
                        {{-- Edit: arahkan ke halaman edit --}}
                        <a href="{{ route('ds_input.edit', $ds->ds_number) }}"
                           class="btn btn-sm bg-white" title="Edit">✏️</a>

                        {{-- Delete --}}
                        <form action="{{ route('ds_input.destroy', $ds->ds_number) }}" method="POST" style="display:inline-block"
                              onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                            @csrf
                            @method('DELETE')
                            {{-- jaga filter & halaman saat kembali --}}
                            <input type="hidden" name="tanggal" value="{{ request('tanggal') }}">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <input type="hidden" name="page" value="{{ request('page') }}">
                            <button type="submit" class="btn btn-sm">🗑️</button>
                        </form>
                    </td>
                </tr>
                
            @endforeach
        @else
            <tr>
                <td colspan="11" class="text-center text-muted">Pilih tanggal untuk menampilkan DS.</td>
            </tr>
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
        "pageLength": 10,   
        "responsive": true, 
        "scrollX": true,    
        "searching": false, 
        "lengthChange": true,
            "columnDefs": [
        { "width": "20px", "targets": 0 }, 
        { "width": "150px", "targets": 1 }, 
        { "width": "100px", "targets": 2 }, 
        { "width": "120px", "targets": 3 }, 
        { "width": "180px", "targets": 4 }, 
        { "width": "120px", "targets": 5 }, 
        { "width": "100px", "targets": 6 }, 
        { "width": "120px", "targets": 7 }, 
        { "width": "120px", "targets": 8 }, 
        { "width": "80px",  "targets": 9 }, 
    ]

    });
});
</script>

</div>
@endsection
