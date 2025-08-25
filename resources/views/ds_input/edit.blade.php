@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Data DS</h3>
    <form action="{{ route('ds_input.update', $ds->ds_number) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="ds_number" class="form-label">DS Number</label>
            <input type="text" name="ds_number" id="ds_number" 
                   class="form-control" value="{{ $ds->ds_number }}" readonly>
        </div>

        <div class="mb-3">
            <label for="gate" class="form-label">Gate</label>
            <input type="text" name="gate" id="gate" 
                   class="form-control" value="{{ $ds->gate }}">
        </div>

        <div class="mb-3">
            <label for="supplier_part_number" class="form-label">Supplier Part Number</label>
            <input type="text" name="supplier_part_number" id="supplier_part_number" 
                   class="form-control" value="{{ $ds->supplier_part_number }}">
        </div>

        <div class="mb-3">
            <label for="qty" class="form-label">Qty</label>
            <input type="number" name="qty" id="qty" 
                   class="form-control" value="{{ $ds->qty }}">
        </div>

        <div class="mb-3">
            <label for="di_type" class="form-label">DI Type</label>
            <input type="text" name="di_type" id="di_type" 
                   class="form-control" value="{{ $ds->di_type }}">
        </div>

        <div class="mb-3">
            <label for="di_status" class="form-label">DI Status</label>
            <input type="text" name="di_status" id="di_status" 
                   class="form-control" value="{{ $ds->di_status }}">
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('ds_input.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection