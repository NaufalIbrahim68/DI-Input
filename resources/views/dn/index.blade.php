@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="bg-white p-4 shadow rounded">
        <h2 class="mb-4 text-dark">Data DN</h2>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>DS Number</th>
                    <th>Qty DS</th>
                    <th>DN Number</th>
                    <th>Qty DN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dnData as $row)
                    <tr>
                        <td>{{ $row->ds_number }}</td>
                        <td>{{ $row->ds_qty ?? '-' }}</td>        {{-- Qty DS --}}
                        <td>{{ $row->dn_number ?? '-' }}</td>
                      <td>{{ $row->dn_qty ?? '-' }}</td>   {{-- Qty DN --}}
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Belum ada data DN</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
