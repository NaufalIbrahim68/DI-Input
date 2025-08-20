<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data DN</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #333; padding: 5px; text-align: center; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Data Delivery Note (DN)</h2>
    @if($selectedDate)
        <p style="text-align:center">Tanggal: <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</strong></p>
    @endif

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>DS Number</th>
                <th>DN Number</th>
                <th>Qty DN</th>
                <th>Qty DS</th>
                <th>Received Date</th>
                <th>Dibuat</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dnData as $index => $dn)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $dn->ds_number }}</td>
                    <td>{{ $dn->dn_number }}</td>
                    <td>{{ $dn->qty_dn }}</td>
                    <td>{{ $dn->qty_ds }}</td>
                    <td>{{ $dn->di_received_date_string }}</td>
                    <td>{{ $dn->created_at }}</td>
                    <td>{{ $dn->updated_at }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
