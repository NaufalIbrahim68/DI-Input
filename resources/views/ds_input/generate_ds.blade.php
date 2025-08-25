@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="bg-white p-4 shadow rounded">

        {{-- Flash Message --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        
        {{-- Form Generate --}}
        <form action="{{ route('ds_input.generate') }}" method="POST" class="row g-3 justify-content-center mb-4">
            @csrf
            <div class="col-md-4">
                <label for="generate_date" class="form-label">Pilih Tanggal</label>
                <input type="date" name="generate_date" id="generate_date"
                       class="form-control"
                       value="{{ $generateDate ?? '' }}" required>
            </div>
            <div class="col-12 text-center mt-3">
                <button type="submit" class="btn btn-primary px-5 py-2">
                    🚀 Generate DS
                </button>
            </div>
        </form>

        {{-- Tombol Export (bawa query ?tanggal=) --}}
        <div class="d-flex gap-2 mb-3">
            <a href="{{ route('ds_input.export.pdf', ['tanggal' => $generateDate]) }}"
               class="btn btn-danger btn-sm">
               📄 Export PDF
            </a>
            <a href="{{ route('ds_input.export.excel', ['tanggal' => $generateDate]) }}"
               class="btn btn-success btn-sm">
               📊 Export Excel
            </a>
        </div>

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
                @php
                    // pastikan variabel ada
                    $dsInputs = $dsInputs ?? collect();
                @endphp

                @forelse ($dsInputs as $index => $ds)
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
                     
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">Belum ada data DS yang di generate.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
