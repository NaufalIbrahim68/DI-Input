@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.min.css">

    <div class="container-fluid">

        <div class="bg-white p-4 shadow rounded">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-5xl  text-dark">Data DI</h1>
                <a href="{{ route('deliveries.import.form') }}"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-2 rounded text-sm">
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
                            <th class="border p-2 bg-black text-white">Supplier Part Number</th>
                            <th class="border p-2 bg-black text-white">BAAN PN</th>
                            <th class="border p-2 bg-black text-white">Visteon PN</th>
                            <th class="border p-2 bg-black text-white">Part Desc</th>
                            <th class="border p-2 bg-black text-white">Qty</th>
                            <th class="border p-2 bg-black text-white">DI Type</th>
                            <th class="border p-2 bg-black text-white">DI Received Date</th>
                            <th class="border p-2 bg-black text-white">DI Received Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $index => $DI)
                            <tr>
                                <td class="text-dark border p-2">{{ $index + 1 }}</td>
                                <td class="text-dark border p-2">{{ $DI->di_no ?? '-' }}</td>
                                <td class="text-dark border p-2">{{ $DI->gate ?? '-' }}</td>
                                <td class="text-dark border p-2">{{ $DI->supplier_part_number ?? '-' }}</td>
                                <td class="text-dark border p-2">{{ $DI->baan_pn ?? '-' }}</td>
                                <td class="text-dark border p-2">{{ $DI->visteon_pn ?? '-' }}</td>
                                <td class="text-dark border p-2">{{ $DI->supplier_part_number_desc ?? '-' }}</td>
                                <td class="text-dark border p-2">{{ $DI->qty ?? '-' }}</td>
                                <td class="text-dark border p-2">{{ $DI->di_type ?? '-' }}</td>
                                <td class="text-dark border p-2">
                                    {{ $DI->di_received_date_string ?? '-' }}
                                </td>

                                <td class="text-dark border p-2">{{ $DI->di_received_time ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- jQuery & DataTables -->
                <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

                <!-- Inisialisasi DataTables -->
                <script>
                    $(document).ready(function () {
                        $('#example').DataTable({
                            "columnDefs": [
                                { "defaultContent": "", "targets": "_all" }
                            ],
                            "pageLength": 25,
                            "responsive": true,
                            "scrollX": true
                        });
                    });

                </script>


                {{-- Detail Tabel --}}
                <script>
                    function showDetail(id) {
                        fetch(`/deliveries/${id}`)
                            .then(res => res.json())
                            .then(data => {
                                console.log(`📦 Data dari ID ${id}:`, data);


                                if (!data || typeof data !== 'object') {
                                    alert("❌ Data tidak valid atau tidak ditemukan.");
                                    return;
                                }

                                let html = '<table class="table-auto w-full">';
                                for (const key in data) {
                                    if (!data.hasOwnProperty(key)) continue;

                                    let label = key.replace(/_/g, ' ');
                                    let value = data[key];

                                    html += `
                      <tr>
                        <td class="font-semibold p-2 border">${label}</td>
                        <td class="p-2 border">${value ?? '-'}</td>
                      </tr>`;
                                }
                                html += '</table>';

                                document.getElementById('modalContent').innerHTML = html;
                                document.getElementById('detailModal').classList.remove('hidden');
                                document.getElementById('detailModal').classList.add('flex');
                            })
                            .catch(err => {
                                console.error("❌ Error saat fetch:", err);
                                alert("❌ Gagal mengambil detail.");
                            });
                    }


                    function closeModal() {
                        document.getElementById('detailModal').classList.remove('flex');
                        document.getElementById('detailModal').classList.add('hidden');
                    }
                </script>


@endsection