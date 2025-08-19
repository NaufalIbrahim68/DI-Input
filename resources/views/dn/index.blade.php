@extends('layouts.app')

@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.min.css">

    <div class="container-fluid">
        <div class="bg-white p-4 shadow rounded">
             <h1 class="text-4xl text-black">Data DN</h1>
            {{-- Flash message --}}
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @elseif(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

         
            <div class="table-responsive">
                 <table id="example" class="display w-full text-xs leading-tight">
                    <thead class="bg-white">
                    <tr>
                        <th class="text-black">DS Number</th>
                        <th class="text-black">Qty DS</th>
                        <th class="text-black">DN Number</th>
                        <th class="text-black">Qty DN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dnData as $row)
                        <tr>
                            <td class="text-black">{{ $row->ds_number }}</td>
                            <td class="text-black">{{ $row->ds_qty ?? '-' }}</td> 
                            <td class="text-black">{{ $row->dn_number ?? '-' }}</td>
                            <td class="text-black">{{ $row->dn_qty ?? '-' }}</td> 
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data DN</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

              <!-- jQuery & DataTables -->
                <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

          <script>
    $(document).ready(function () {
        $('#example').DataTable({
            "columnDefs": [
                { "defaultContent": "", "targets": "_all" }
            ],
            "responsive": true,
            "scrollX": true,
            "searching": false, 
            "paging": false      
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