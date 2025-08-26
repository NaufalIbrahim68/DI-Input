@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    @php
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
        <form method="GET" action="{{ route('ds_input.index') }}">
            <div class="d-flex align-items-center gap-2">
                <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control" style="width:200px;" required>
                <button type="submit" class="btn btn-success">Filter Tanggal</button>
            </div>
        </form>
    </div>

    @if(!empty($selectedDate))
        <div class="alert alert-info text-center">
            Menampilkan data DS untuk tanggal
            <strong>{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</strong>
            - Ditemukan <strong>{{ $dsInputs->count() }}</strong> data
        </div>
    @endif

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('ds_input.export.pdf', ['tanggal' => request('tanggal')]) }}" class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf me-1"></i> Export PDF
        </a>
        <a href="{{ route('ds_input.export.excel', ['tanggal' => request('tanggal')]) }}" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel me-1"></i> Export Excel
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
                    <th>Qty</th>
                    <th>Qty Prep</th>   
                    <th>Status Preparation</th>
                    <th>Qty Delivery</th> 
                    <th>Status Delivery</th>
                    <th>DN Number</th>    
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
                            <td class="text-black">{{ $ds->qty ?? '-' }}</td>
                            <td class="text-black">{{ $ds->qty_prep ?? '' }}</td>
                            <td class="text-black">
                                @if($ds->flag_prep == 1)
                                    <span class="badge bg-success text-white">Completed</span>
                                @else
                                    <span class="badge bg-primary text-white">Non Completed</span>
                                @endif
                            </td>

                            {{-- Form untuk update qty_delivery & dn_number --}}
                            <form action="{{ route('ds_input.update', $ds->ds_number) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <td>
                                    <input type="number" name="qty_delivery" value="{{ $ds->qty_delivery ?? '' }}" class="form-control form-control-sm text-center">
                                </td>
                                <td>
    @php
        $qtyDelivery = (int) ($ds->qty_delivery ?? 0); 
        $qtyDs = (int) ($ds->qty ?? 0);               

        if ($qtyDelivery == 0) {
            $status = 'not completed';
        } elseif ($qtyDelivery < $qtyDs) {
            $status = 'partial';
        } else {
            $status = 'completed';
        }
    @endphp

    @switch($status)
        @case('completed') 
            <span class="badge bg-success text-white">Completed</span> 
            @break
        @case('partial')   
            <span class="badge bg-warning text-white">Partial</span>   
            @break
        @default            
            <span class="badge bg-primary text-white">Non Completed</span>
    @endswitch
</td>
                                <td>
                                    <input type="text" name="dn_number" value="{{ $ds->dn_number ?? '' }}" class="form-control form-control-sm text-center">
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center align-items-center">
                                        {{-- Save Button --}}
                                        <button type="submit" class="btn btn-sm btn-success" title="Simpan">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </div>
                                </td>
                            </form>

                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger btn-delete-ds" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteModal" 
                                    data-ds_number="{{ $ds->ds_number }}" 
                                    title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="14" class="text-center text-muted py-4">
                            <i class="fas fa-calendar-alt fa-2x mb-2 text-muted"></i>
                            <br>Pilih tanggal untuk menampilkan DS.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

{{-- Modal Delete Tunggal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formDeleteDS" action="" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Hapus DS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="password" class="form-label">Masukkan Password</label>
                        <input type="password" name="password" id="password" class="form-control" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Alasan Hapus</label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <p>Apakah Anda yakin ingin menghapus DS ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- JS untuk set action modal --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('.btn-delete-ds');
    const formDelete = document.getElementById('formDeleteDS');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            formDelete.action = this.getAttribute('data-url');
            const dsNumber = this.getAttribute('data-ds_number');
            document.getElementById('deleteModalLabel').innerText = 'Hapus DS ' + dsNumber;
        });
    });
});
</script>

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
            { "width": "80px", "targets": 9 },
        ]
    });
});
</script>

</div>
@endsection
