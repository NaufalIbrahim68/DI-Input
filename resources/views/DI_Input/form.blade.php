@extends('layouts.app')

@section('content')
    <div class="container mx-auto mt-6">
        <h2 class="text-xl font-bold mb-4">Tambah Data</h2>

        <form action="/DI_Input/store" method="POST" class="space-y-4">
            @csrf
            <div>
                <label>Gate:</label>
                <input type="text" name="Gate" class="border p-2 w-full" required>
            </div>
            <div>
                <label>PO Number:</label>
                <input type="text" name="PO_Number" class="border p-2 w-full" required>
            </div>
            <div>
                <label>BAAN PN:</label>
                <input type="text" name="BAAN_PN" class="border p-2 w-full" required>
            </div>
            <div>
                <label>Qty:</label>
                <input type="number" name="Qty" class="border p-2 w-full" required>
            </div>
            <div>
                <label>Delivery Date:</label>
                <input type="date" name="Deliv_Date" class="border p-2 w-full" required>
            </div>

            <button type="submit" class="bg-blue-500 text-dark px-4 py-2 rounded">Simpan</button>
        </form>
    </div>
@endsection
