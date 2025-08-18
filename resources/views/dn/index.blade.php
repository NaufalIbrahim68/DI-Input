@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="bg-white p-4 shadow rounded">
        <h1 class="text-3xl mb-4">Data DN</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No DS</th>
                    <th>Qty DS</th>
                    <th>No DN</th>
                    <th>Qty DN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dnList as $i => $dn)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $dn->ds_number }}</td>
                    <td>{{ $dn->ds->qty ?? '-' }}</td>
                    <td>{{ $dn->dn_number }}</td>
                    <td>{{ $dn->qty }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
