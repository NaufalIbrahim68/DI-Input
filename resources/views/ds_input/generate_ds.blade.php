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
                        <input type="date" name="generate_date" id="generate_date" class="form-control"
                            value="{{ $generateDate ?? '' }}" required>
                    </div>
                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-primary px-5 py-2">
                            🚀 Generate DS
                        </button>
                    </div>
                </form>
                

<div class="d-flex justify-content-end mb-3">
   {{-- Tombol Regenerate kecil di pojok kanan atas container form --}}
<form action="{{ route('ds_input.regenerate') }}" method="POST">
    @csrf

    {{-- Hidden input akan otomatis mengikuti tanggal filter --}}
    <input type="hidden" name="generate_date" id="regen_date">

    <button type="submit" class="btn btn-warning btn-sm">
        🔄 Regenerate DS
    </button>
</form>


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
                                <th>Qty</th>
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
                                                <td class="text-black">{{ $ds->qty ?? '-' }}</td>
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


        <script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.querySelector('#generate_date');
    const regenDateInput = document.querySelector('#regen_date');

    // Sync otomatis setiap kali tanggal diubah
    function syncDate() {
        regenDateInput.value = dateInput.value;
    }

    // Sync default ketika halaman dibuka
    syncDate();

    // Sync ketika user mengganti tanggal
    dateInput.addEventListener('change', syncDate);
});
</script>

    @endsection