@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.min.css">

    <style>
        /* Border untuk setiap cell di tabel */
        #example th,
        #example td {
            border: 1px solid #dee2e6 !important;
        }
        #example {
            border-collapse: collapse !important;
        }
    </style>

    <div class="container-fluid">

        <div class="bg-white p-4 shadow rounded">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-5xl  text-dark">Data DI</h1>
                <a href="{{ route('deliveries.import.form') }}"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-2 rounded text-sm">
                    + Import Data DI
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
                    <thead class="bg-dark-100 text-xs">
                        <tr>
                            <th class="border p-2 bg-black text-white">No</th>
                            <th class="border p-2 bg-black text-white text-center">DI No</th>
                            <th class="border p-2 bg-black text-white text-center">Gate</th>
                            <th class="border p-2 bg-black text-white text-center">PO Number</th>
                            <th class="border p-2 bg-black text-white text-center">Supplier Part Number</th>
                            <th class="border p-2 bg-black text-white text-center">BAAN PN</th>
                            <th class="border p-2 bg-black text-white text-center">Visteon PN</th>
                            <th class="border p-2 bg-black text-white text-center">Supplier Part Desc</th>
                            <th class="border p-2 bg-black text-white text-center">Qty</th>
                            <th class="border p-2 bg-black text-white text-center">DI Type</th>
                            <th class="border p-2 bg-black text-white text-center">Received Date</th>
                            <th class="border p-2 bg-black text-white text-center">Received Time</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs leading-tight">
                        <!-- Data akan diload via AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- Modal for showing details -->
            <div id="detailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-white p-6 rounded-lg shadow-lg max-w-4xl w-full mx-4 max-h-96 overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Detail Data DI</h2>
                        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                    </div>
                    <div id="modalContent">
                        <!-- Content will be loaded here -->
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button onclick="closeModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- jQuery & DataTables -->
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
            <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

            <!-- Inisialisasi DataTables dengan Server-Side Processing -->
            <script>
                $(document).ready(function () {
                    $('#example').DataTable({
                        "processing": true,
                        "serverSide": true,
                        "ajax": {
                            "url": "{{ route('DI_Input.datatable') }}",
                            "type": "GET"
                        },
                        "columns": [
                            { "data": "DT_RowIndex", "orderable": false, "searchable": false },
                            { "data": "di_no" },
                            { "data": "gate" },
                            { "data": "po_number" },
                            { "data": "supplier_part_number" },
                            { "data": "baan_pn" },
                            { "data": "visteon_pn" },
                            { "data": "supplier_part_number_desc" },
                            { "data": "qty" },
                            { "data": "di_type" },
                            { "data": "di_received_date_string" },
                            { "data": "di_received_time" }
                        ],
                        "pageLength": 10,
                        "responsive": true,
                        "scrollX": true,
                        "language": {
                            "processing": "Memuat data...",
                            "search": "Cari:",
                            "lengthMenu": "Tampilkan _MENU_ data",
                            "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            "infoEmpty": "Tidak ada data",
                            "infoFiltered": "(difilter dari _MAX_ total data)",
                            "zeroRecords": "Tidak ditemukan data yang cocok",
                            "paginate": {
                                "first": "«",
                                "last": "»",
                                "next": "›",
                                "previous": "‹"
                            }
                        }
                    });
                });
            </script>

            {{-- Detail Tabel --}}
            <script>
                function showDetail(id) {
                    console.log('🔍 Mencoba mengambil data untuk ID:', id);

                    // Show loading state
                    document.getElementById('modalContent').innerHTML = '<div class="text-center p-4">Loading...</div>';
                    document.getElementById('detailModal').classList.remove('hidden');
                    document.getElementById('detailModal').classList.add('flex');

                    const url = `/deliveries/${id}/detail`;
                    console.log('🌐 URL yang dipanggil:', url);

                    fetch(`/deliveries/${id}`)
                        .then(response => {
                            console.log('📡 Response status:', response.status);
                            console.log('📡 Response ok:', response.ok);

                            if (!response.ok) {
                                return response.text().then(text => {
                                    console.error('❌ Response error text:', text);
                                    throw new Error(`HTTP error! status: ${response.status} - ${text}`);
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log(`📦 Data berhasil diterima untuk ID ${id}:`, data);

                            if (!data || typeof data !== 'object') {
                                document.getElementById('modalContent').innerHTML =
                                    '<div class="text-red-500 p-4">❌ Data tidak valid atau tidak ditemukan.</div>';
                                return;
                            }

                            // Create a more structured display
                            let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

                            // Define field labels in Indonesian
                            const fieldLabels = {
                                'id': 'ID',
                                'di_no': 'Nomor DI',
                                'gate': 'Gate',
                                'po_number': 'Nomor PO',
                                'supplier_part_number': 'Nomor Part Supplier',
                                'baan_pn': 'BAAN PN',
                                'visteon_pn': 'Visteon PN',
                                'supplier_part_number_desc': 'Deskripsi Part',
                                'qty': 'Quantity',
                                'di_type': 'Tipe DI',
                                'di_received_date_string': 'Tanggal Terima DI',
                                'di_received_time': 'Waktu Terima DI',
                                'created_at': 'Dibuat Pada',
                                'updated_at': 'Diupdate Pada'
                            };

                            for (const key in data) {
                                if (!data.hasOwnProperty(key)) continue;

                                let label = fieldLabels[key] || key.replace(/_/g, ' ').toUpperCase();
                                let value = data[key];

                                // Format dates if needed
                                if (key.includes('date') || key.includes('_at')) {
                                    try {
                                        value = new Date(value).toLocaleString('id-ID');
                                    } catch (e) {
                                        // Keep original value if date parsing fails
                                    }
                                }

                                html += `
                                        <div class="border rounded p-3">
                                            <div class="font-semibold text-gray-700 text-sm mb-1">${label}</div>
                                            <div class="text-gray-900">${value ?? '-'}</div>
                                        </div>`;
                            }
                            html += '</div>';

                            document.getElementById('modalContent').innerHTML = html;
                        })
                        .catch(err => {
                            console.error("❌ Error saat fetch:", err);
                            document.getElementById('modalContent').innerHTML =
                                '<div class="text-red-500 p-4">❌ Gagal mengambil detail data.</div>';
                        });
                }

                function closeModal() {
                    document.getElementById('detailModal').classList.remove('flex');
                    document.getElementById('detailModal').classList.add('hidden');
                }

                // Close modal when clicking outside
                document.getElementById('detailModal').addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeModal();
                    }
                });

                // Close modal with Escape key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        closeModal();
                    }
                });
            </script>
        </div>
    </div>
@endsection