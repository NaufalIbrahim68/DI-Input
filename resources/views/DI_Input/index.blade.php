@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.min.css">

<div class="container-fluid">
     
    <div class="bg-white p-4 shadow rounded">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-5xl  text-dark">Data DI</h1>
            <a href="{{ route('deliveries.import.form') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-2 rounded text-sm">
                + Import Data From Excel
            </a>
        </div>

        @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
        @endif

        @if (session()->has('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-2 rounded mb-4">
            {{ session('warning') }}
        </div>
        @endif

        @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
            {{ session('error') }}
        </div>
        @endif

        @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
            {{ session('message') }}
        </div>
        @endif
      

        <div class="table-responsive">
            <table id="example" class="display" style="width: 100%">
                <thead class="bg-dark-100">
                    <tr>
                        <th class="border p-2 bg-black text-white">No</th>
                        <th class="border p-2 bg-black text-white">DI No</th>
                        <th class="border p-2 bg-black text-white">Gate</th>
                        <th class="border p-2 bg-black text-white">PO Number</th>
                        <th class="border p-2 bg-black text-white">PO Item</th>
                        <th class="border p-2 bg-black text-white">Supplier ID</th>
                        <th class="border p-2 bg-black text-white">Supplier Desc</th>
                        <th class="border p-2 bg-black text-white">Supplier Part Number</th>
                        <th class="border p-2 bg-black text-white">Baan PartNumber</th>
                        <th class="border p-2 bg-black text-white">Visteon PartNumber</th>
                        <th class="border p-2 bg-black text-white">Supplier Part Number Desc</th>
                        <th class="border p-2 bg-black text-white">Qty</th>
                        <th class="border p-2 bg-black text-white">UOM</th>
                        <th class="border p-2 bg-black text-white">Critical Part</th>
                        <th class="border p-2 bg-black text-white">Flag Subcontracting</th>
                        <th class="border p-2 bg-black text-white">PO Status</th>
                        <th class="border p-2 bg-black text-white">Latest GR Date PO</th>
                        <th class="border p-2 bg-black text-white">DI Type</th>
                        <th class="border p-2 bg-black text-white">DI Status</th>
                        <th class="border p-2 bg-black text-white">DI Received Date</th>
                        <th class="border p-2 bg-black text-white">DI Received Time</th>
                        <th class="border p-2 bg-black text-white">DI Created Date</th>
                        <th class="border p-2 bg-black text-white">DI Created Time</th>
                        <th class="border p-2 bg-black text-white">DI No Original</th>
                        <th class="border p-2 bg-black text-white">DI No Split</th>
                        <th class="border p-2 bg-black text-white">DN No</th>
                        <th class="border p-2 bg-black text-white">Plant ID (DN)</th>
                        <th class="border p-2 bg-black text-white">Plant Desc (DN)</th>
                        <th class="border p-2 bg-black text-white">Supplier ID (DN)</th>
                        <th class="border p-2 bg-black text-white">Supplier Desc (DN)</th>
                        <th class="border p-2 bg-black text-white">Plant Supplier (DN)</th>
                    </tr>
                </thead>
                <tbody>     
                    @forelse($data as $DI)
                    <tr class="hover:bg-gray-50">
                         <td class="text-dark border p-2">{{ $DI->id ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->di_no ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->gate ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->po_number ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->po_item ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->supplier_id ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->supplier_desc ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->supplier_part_number ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->baan_pn ?? '-' }}</td>       
                        <td class="text-dark border p-2">{{ $DI->visteon_pn ?? '-' }}</td>    
                        <td class="text-dark border p-2">{{ $DI->supplier_part_number_desc ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->qty ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->uom ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->critical_part ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->flag_subcontracting ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->po_status ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->latest_gr_date_po ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->di_type ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->di_status ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ \Carbon\Carbon::parse($DI->di_received_date)->format('d-m-Y') }}</td>
                        <td class="text-dark border p-2">{{ $DI->di_received_time ?? '-' }}</td>
                       <td class="text-dark border p-2">{{ \Carbon\Carbon::parse($DI->di_created_date)->format('d-m-Y') }}</td>
                        <td class="text-dark border p-2">{{ $DI->di_created_time ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->di_no_original ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->di_no_split ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->dn_no ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->plant_id_dn ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->plant_desc_dn ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->supplier_id_dn ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->supplier_desc_dn ?? '-' }}</td>
                        <td class="text-dark border p-2">{{ $DI->plant_supplier_dn ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- jQuery & DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

<!-- Inisialisasi DataTables -->
<script>
  $(document).ready(function() {
    $('#example').DataTable({
        "columnDefs": [
            {"defaultContent": "", "targets": "_all"}
        ],
        "pageLength": 25,
        "responsive": true,
        "scrollX": true  // For horizontal scrolling with many columns
    });
});
    
</script>

@endsection