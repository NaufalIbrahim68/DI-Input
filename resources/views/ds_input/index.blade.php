@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h2 class="text-xl font-bold mb-4">Data DS Input</h2>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-responsive">
        <table id="dsTable" class="display table table-bordered table-striped" style="width:100%">
            <thead class="bg-dark text-white">
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
                </tr>
            </thead>
            <tbody>
                @foreach ($dsInputs as $index => $ds)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $ds->ds_number }}</td>
                        <td>{{ $ds->gate }}</td>
                        <td>{{ $ds->supplier_part_number }}</td>
                        <td>{{ $ds->qty }}</td>
                        <td>{{ $ds->di_type }}</td>
                        <td>{{ $ds->di_status }}</td>
                        <td>{{ $ds->di_received_date }}</td>
                        <td>{{ $ds->di_received_time }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
    {{-- Jika pakai DataTables --}}
    <script>
        $(document).ready(function () {
            $('#dsTable').DataTable();
        });
    </script>
@endsection
