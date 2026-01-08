<!DOCTYPE html>
<html>

<head>
    <title>DS Input</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>

 <h2 style="text-align: center;">
    Data DS(Delivery Section)
</h2>

@if(!empty($tanggal))
    <p style="text-align: center; font-size: 16px; margin-top: 5px;">
        {{ \Carbon\Carbon::make($tanggal)->translatedFormat('d F Y') }}
    </p>
@endif
    <table>
        <thead>
            <tr>
                <th style="text-align: center;">DS Number</th>
                <th style="text-align: center;">Gate</th>
                <th style="text-align: center;">Supplier Part Number</th>
                <th style="text-align: center;">DI Type</th>
                <th style="text-align: center;">Received Date</th>
                <th style="text-align: center;">Received Time</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: center;">Qty Prep</th>
                <th style="text-align: center;">Qty Delivery</th>
                <th style="text-align: center;">DN Number</th>
                <th style="text-align: center;">QR Code</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dsInputs as $ds)
                    <tr>
                        <td>{{ $ds->ds_number }}</td>
                        <td>{{ $ds->gate }}</td>
                        <td style="text-align: center;">{{ $ds->supplier_part_number }}</td>
                        <td style="text-align: center;">{{ $ds->di_type }}</td>
                        <td style="text-align: center;">{{ $ds->di_received_date_string
                ? \Carbon\Carbon::parse($ds->di_received_date_string)->format('d-m-Y')
                : '-' }}</td>
                        <td style="text-align: center;">{{ $ds->di_received_time ?? '-' }}</td>
                        <td style="text-align: center;">{{ $ds->qty }}</td>

                        <td class="text-black" style="text-align: center;">
                            {{ ($ds->qty_prep ?? 0) > 0 ? $ds->qty_prep : '' }}
                        </td>
                        <td class="text-black" style="text-align: center;">
                            {{ ($ds->qty_agv ?? 0) > 0 ? $ds->qty_agv : '' }}
                        </td>
                        <td class="text-black" style="text-align: center;">
                            {{ ($ds->dn_number ?? 0) > 0 ? $ds->dn_number : '' }}
                        </td>
                        <td style="text-align: center;">
                            <img src="data:image/svg+xml;base64,{{ base64_encode(SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->generate($ds->ds_number)) }}" width="100">
                        </td>
                    </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center;">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>