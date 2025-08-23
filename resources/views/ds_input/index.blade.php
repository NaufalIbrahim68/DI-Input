@extends('layouts.app')

@section('content')
<div class="container-fluid">

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
    <form method="POST" action="{{ route('ds_input.generate') }}">
        @csrf
        <div class="d-flex align-items-center gap-2">
            <input type="date" name="selected_date" value="{{ request('tanggal') }}" class="form-control" style="width:200px;" required>
            <button type="submit" class="btn btn-success">Filter Tanggal</button>
        </div>
    </form>
</div>

{{-- Info tanggal & jumlah DS --}}
@if($selectedDate)
    <div class="alert alert-info text-center">
        Menampilkan data DS untuk tanggal 
        <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>
        - Ditemukan <strong>{{ $dsInputs->total() }}</strong> data
    </div>
@endif

    {{-- Tabel DS --}}
    <div class="table-responsive" style="overflow-x: auto;">
        <table class="table table-bordered table-sm bg-white small">
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
            <td class="text-black">{{ $dsInputs->firstItem() + $index }}</td>
            <td class="text-black">{{ $ds->ds_number ?? '-' }}</td>
            <td class="text-black">{{ $ds->gate ?? '-' }}</td>
            <td class="text-black">{{ $ds->di_type ?? '-' }}</td>
            <td class="text-black">{{ $ds->supplier_part_number ?? '-' }}</td>
            <td class="text-black">{{ \Carbon\Carbon::parse($ds->di_received_date_string)->format('d-m-Y') }}</td>
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
            <td class="text-black">{{ $ds->qty ?? '-' }}</td>
            <td class="d-flex gap-2">
                {{-- Edit Inline Toggle --}}
                <button type="button" class="btn btn-sm bg-white show-edit-form" data-ds="{{ $ds->ds_number }}">✏️</button>

                {{-- Delete --}}
                <form action="{{ route('ds_input.destroy', $ds->ds_number) }}" method="POST" style="display: inline-block;" 
                      onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tanggal" value="{{ request('tanggal') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="page" value="{{ request('page') }}">
                    <button type="submit" class="btn btn-sm">🗑️</button>
                </form>
                                    </td>
                                </tr>
                            </table>
                        </div>
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

{{-- Pagination --}}
@if ($dsInputs instanceof \Illuminate\Contracts\Pagination\Paginator && $dsInputs->count() > 0)
    <div class="d-flex justify-content-end">
        {{-- Tambahkan query filter supaya pagination tetap ikut --}}
        {{ $dsInputs->appends(request()->except('page'))->links() }}
    </div>
@endif


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
            if (formRow) {
                formRow.style.display = formRow.style.display === "none" ? "table-row" : "none";
            }
        });
    });
});
</script>
@endsection
