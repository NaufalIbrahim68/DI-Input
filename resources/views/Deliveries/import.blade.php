@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h2 class="text-black my-4 fw-bold">Import Excel - Delivery Data</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('deliveries.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
               <div class="mb-3">
    <label for="file" class="text-black form-label">Pilih File Excel</label>
    <div class="input-group">
        <input type="file" name="file" class="form-control" id="fileInput" required>
    </div>
</div>
                <button class="btn btn-primary">Import</button>
            </form>
        </div>
    </div>
</div>
@endsection
