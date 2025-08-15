@extends('layouts.app')

@section('content')
<div class="container-fluid">

 <div class="bg-white p-4 shadow rounded">
    <div class="d-flex justify-content-center mb-4">
        <h1 class="text-4xl text-dark fw-bold">Data DS</h1>
    </div>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> 
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Form Generate di Tengah --}}
    <div class="d-flex justify-content-center mb-4">
        <form id="generateForm" action="{{ route('ds_input.generate') }}" method="POST" class="d-flex align-items-center gap-3 p-3 border rounded bg-light">
            @csrf
            <label class="mb-0 fw-semibold">Pilih Tanggal:</label>
            <input type="date" name="selected_date" class="form-control" style="width: 200px;" required>
            <button type="submit" class="btn btn-primary px-4">
                Generate Data From DI
            </button>
        </form>
    </div>

    {{-- Form Search di Kanan --}}
    <div class="d-flex justify-content-end mb-3">
        <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center gap-2">
            @foreach(request()->except(['search', 'page']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <input type="text" name="search" class="form-control form-control-sm" 
                   placeholder="Search DS Number, Gate, Part..." 
                   value="{{ request('search') }}" style="width: 250px;">
            <button class="btn btn-outline-secondary btn-sm px-3" type="submit">
                <i class="fas fa-search"></i>
            </button>
            @if(request('search'))
                <a href="{{ request()->url() }}" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </form>
    </div>

  {{-- Info Messages --}}
@if(!$selectedDate)
    {{-- User belum memilih tanggal --}}
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle me-2"></i>
        Pilih tanggal dan klik "Generate Data from DI" untuk menampilkan data DS.
    </div>
@elseif($dsInputs->isEmpty())
    {{-- Data kosong untuk tanggal terpilih --}}
    <div class="alert alert-warning text-center">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Tidak ada data untuk tanggal <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>.
    </div>
@else
    {{-- Data ditemukan untuk tanggal terpilih --}}
    <div class="alert alert-success text-center">
        <i class="fas fa-check-circle me-2"></i>
        Menampilkan data DI untuk tanggal 
        <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>
        - Ditemukan <strong>{{ $dsInputs->total() }}</strong> data DS
        @if(request('search'))
            dengan pencarian "<strong>{{ request('search') }}</strong>"
        @endif
    </div>
@endif

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-bordered bg-white">
                <thead class="bg-black text-white">
                    <tr>
                        <th>No</th>
                        <th>DS Number</th>
                        <th>Gate</th>
                        <th>Supplier Part Number</th>
                        <th>Qty</th>
                        <th>DI Type</th>
                        <th>DI Status</th>
                        <th>Received Date</th>
                        <th>Received Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dsInputs as $index => $ds)
                        <tr>
                            <td class="text-dark">{{ $dsInputs->firstItem() + $index }}</td>
                            <td class="text-dark">{{ $ds->ds_number ?? '-' }}</td>
                            <td class="text-dark">{{ $ds->gate ?? '-' }}</td>
                            <td class="text-dark">{{ $ds->supplier_part_number ?? '-' }}</td>
                            <td class="text-dark">{{ $ds->qty ?? '-' }}</td>
                            <td class="text-dark">{{ $ds->di_type ?? '-' }}</td>
                            <td class="text-dark">{{ $ds->di_status ?? '-' }}</td>
                            <td class="text-dark">{{ $ds->di_received_date_string ?? '-' }}</td>
                            <td class="text-dark">{{ $ds->di_received_time ?? '-' }}</td>
                            <td class="text-dark">{{ $ds->flag == 1 ? 'Completed' : 'Non Completed' }}</td>
                            <td class="text-dark">
                                <div class="d-flex justify-content-start gap-2">
                                    <button class="btn btn-sm bg-white show-edit-form" data-ds="{{ $ds->ds_number }}">✏️</button>
                                    <form action="{{ route('ds_input.destroy', $ds->ds_number) }}" method="POST" onsubmit="return confirm('Yakin ingin hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm bg-white">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Form Edit Inline --}}
                        <tr id="edit-row-{{ $ds->ds_number }}" class="edit-form-row" style="display: none;">
                            <td colspan="11">
                                <form method="POST" action="{{ route('ds_input.update', $ds->ds_number) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                    @foreach(request()->except(['_token', '_method']) as $key => $value)
                                        @if(!is_array($value))
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach

                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0">
                                            <tr>
                                                <td><input type="text" name="gate" class="form-control form-control-sm" value="{{ $ds->gate }}" required></td>
                                                <td><input type="text" name="supplier_part_number" class="form-control form-control-sm" value="{{ $ds->supplier_part_number }}" required></td>
                                                <td><input type="number" name="qty" class="form-control form-control-sm" value="{{ $ds->qty }}" required></td>
                                                <td><input type="text" name="di_type" class="form-control form-control-sm" value="{{ $ds->di_type }}"></td>
                                                <td><input type="text" name="di_status" class="form-control form-control-sm" value="{{ $ds->di_status }}"></td>
                                                <td><input type="date" name="di_received_date_string" class="form-control form-control-sm" value="{{ $ds->di_received_date_string }}"></td>
                                                <td><input type="time" name="di_received_time" class="form-control form-control-sm" value="{{ $ds->di_received_time }}"></td>
                                                <td>
                                                    <select name="flag" class="form-control form-control-sm">
                                                        <option value="0" {{ $ds->flag == 0 ? 'selected' : '' }}>Non Completed</option>
                                                        <option value="1" {{ $ds->flag == 1 ? 'selected' : '' }}>Completed</option>
                                                    </select>
                                                </td>
                                                <td colspan="2">
                                                    <div class="d-flex gap-1">
                                                        <button type="submit" class="btn btn-success btn-sm w-100">💾 Simpan</button>
                                                        <button type="button" class="btn btn-secondary btn-sm w-100" onclick="document.getElementById('edit-row-{{ $ds->ds_number }}').style.display='none'">❌ Batal</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-end">
            {{ $dsInputs->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const editButtons = document.querySelectorAll(".show-edit-form");
    editButtons.forEach(btn => {
        btn.addEventListener("click", function () {
            const dsNumber = this.getAttribute("data-ds");
            const formRow = document.getElementById("edit-row-" + dsNumber);
            formRow.style.display = formRow.style.display === "none" ? "table-row" : "none";
        });
    });
});
</script>
@endsection
