<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data DS</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: center; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Data Delivery Schedule (DS)</h2>
    @if($selectedDate)
        <p style="text-align:center">
            Tanggal: <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong>
        </p>
    @endif

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>DS Number</th>
                <th>Gate</th>
                <th>DI Type</th>
                <th>Supplier Part Number</th>
                <th>Qty</th>
                <th>Received Date</th>
                <th>Received Time</th>
                <th>Status Preparation</th>
                <th>Status Delivery</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dsData as $index => $ds)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $ds->ds_number }}</td>
                    <td>{{ $ds->gate }}</td>
                    <td>{{ $ds->di_type }}</td>
                    <td>{{ $ds->supplier_part_number }}</td>
                    <td>{{ $ds->qty }}</td>
                    <td>{{ \Carbon\Carbon::parse($ds->di_received_date_string)->format('d/m/Y') }}</td>
                    <td>{{ $ds->di_received_time }}</td>
                    <td>{{ $ds->flag_prep ? 'Selesai' : 'Belum' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
