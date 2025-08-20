@extends('layouts.app')

@section('content')
<div class="container-fluid">

 <div class="bg-white p-4 shadow rounded">
    <div class="d-flex justify-content-center mb-4">
        <h1 class="text-4xl text-dark fw-bold">Data DS</h1>
    </div>

   
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
    @foreach ($dsInputs as $index => $ds)
        <tr>
            <td class="text-black">{{ $dsInputs->firstItem() + $index }}</td>
            <td class="text-black">{{ $ds->ds_number ?? '-' }}</td>
            <td class="text-black">{{ $ds->gate ?? '-' }}</td>
            <td class="text-black">{{ $ds->di_type ?? '-' }}</td>
            <td class="text-black">{{ $ds->supplier_part_number ?? '-' }}</td>
            <td class="text-black">{{ $ds->di_received_date_string ?? '-' }}</td>
            <td class="text-black">{{ $ds->di_received_time ?? '-' }}</td>
            <td class="text-black">{{ $ds->flag == 1 ? 'Completed' : 'Non Completed' }}</td>
  <td>
    @php
    $totalDn = (int) \App\Models\Dn_Input::where('ds_number', $ds->ds_number)->sum('qty_dn');
    $qtyDs = (int) $ds->qty;

    if ($totalDn == 0) {
        $status = 'not completed'; // biru
    } elseif ($totalDn == $qtyDs) {
        $status = 'partial';       // kuning
    } elseif ($totalDn > $qtyDs) {
        $status = 'completed';     // hijau
    } else {
        $status = 'not completed';
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
            <td class="text-black">{{ $ds->qty ?? '-' }}</td>
            <td class="text-black">
                <div class="d-flex justify-content-start gap-2">
                    {{-- Edit --}}
                    <button class="btn btn-sm bg-white show-edit-form" data-ds="{{ $ds->ds_number }}">✏️</button>

                    {{-- Delete --}}
                    <form action="{{ route('ds_input.destroy', $ds->ds_number) }}" method="POST" 
                          onsubmit="return confirm('Yakin ingin hapus data ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm bg-white">🗑️</button>
                    </form>
                    
  {{-- Tombol DN Number --}}
    <a href="{{ route('ds_input.create_dn', $ds->ds_number) }}" class="btn btn-sm">📦</a>
</td>
        </tr>
    </div>

    
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
                        {{-- Gate --}}
                        <td><input type="text" name="gate" class="form-control form-control-sm text-black" value="{{ $ds->gate }}" required></td>
                        
                        {{-- DI Type --}}
                        <td><input type="text" name="di_type" class="form-control form-control-sm text-black" value="{{ $ds->di_type }}"></td>
                        
                        {{-- Supplier Part Number --}}
                        <td><input type="text" name="supplier_part_number" class="form-control form-control-sm text-black" value="{{ $ds->supplier_part_number }}" required></td>
                        
                        {{-- Received Date --}}
                        <td><input type="date" name="di_received_date_string" class="form-control form-control-sm text-black" value="{{ $ds->di_received_date_string }}"></td>
                        
                        {{-- Received Time --}}
                        <td><input type="time" name="di_received_time" class="form-control form-control-sm text-black" value="{{ $ds->di_received_time }}"></td>
                        
                        {{-- Status Preparation (pakai flag) --}}
                        <td>
                           <select name="flag" class="form-control form-control-sm text-black">
    <option value="0" {{ $ds->flag == 0 ? 'selected' : '' }}>Non Completed</option>
    <option value="1" {{ $ds->flag == 1 ? 'selected' : '' }}>Completed</option>
</select>

                        </td>
                        
                        {{-- Qty --}}
                        <td><input type="number" name="qty" class="form-control form-control-sm text-black" value="{{ $ds->qty }}" required></td>

                        {{-- Action --}}
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
