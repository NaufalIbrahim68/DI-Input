@extends('layouts.app')

@section('content')
<div class="container">
   
    <form action="{{ route('ds_input.update', $ds->ds_number) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="ds_number" class="form-label text-black">DS Number</label>
            <input type="text" name="ds_number" id="ds_number" 
                   class="form-control" value="{{ $ds->ds_number }}" readonly>
        </div>

        <div class="mb-3">
            <label for="gate" class="form-label text-black">Gate</label>
            <input type="text" name="gate" id="gate" 
                   class="form-control text-dark" value="{{ $ds->gate }}">
        </div>

        <div class="mb-3">
            <label for="supplier_part_number" class="form-label text-black">Supplier Part Number</label>
            <input type="text" name="supplier_part_number" id="supplier_part_number" 
                   class="form-control text-dark" value="{{ $ds->supplier_part_number }}">
        </div>

        <div class="mb-3">
            <label for="qty" class="form-label text-black">Qty</label>
            <input type="number" name="qty" id="qty" 
                   class="form-control text-dark" value="{{ $ds->qty }}">
        </div>

        <div class="mb-3">
            <label for="di_type" class="form-label text-black">DI Type</label>
            <input type="text" name="di_type" id="di_type" 
                   class="form-control text-dark" value="{{ $ds->di_type }}">
        </div>

        <div class="mb-3">
            <label for="di_received_date_string" class="form-label text-black">Received Date</label>
            <input type="date" name="di_received_date_string" id="di_received_date_string" 
                   class="form-control text-dark" 
                   value="{{ $ds->di_received_date_string ? \Carbon\Carbon::parse($ds->di_received_date_string)->format('Y-m-d') : '' }}">
        </div>

        <div class="mb-3">
            <label for="di_received_time" class="form-label text-black">Received Time</label>
            <input type="time" name="di_received_time" id="di_received_time" 
                   class="form-control text-dark" 
                   value="{{ $ds->di_received_time ?? '' }}">
        </div>

        <div class="mb-3">
            <label for="flag_prep" class="form-label text-black">Status Preparation</label>
            <select name="flag_prep" id="flag_prep" class="form-select text-dark">
                <option value="0" {{ $ds->flag_prep == 0 ? 'selected' : '' }}>Non Completed</option>
                <option value="1" {{ $ds->flag_prep == 1 ? 'selected' : '' }}>Completed</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('ds_input.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
