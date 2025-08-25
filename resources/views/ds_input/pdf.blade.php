<!DOCTYPE html>
<html>
<head>
    <title>DS Input</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

    <h3 style="text-align: center;">
        Data DS
        @if(!empty($tanggal))
            {{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}
        @endif
    </h3>

    <table>
        <thead>
            <tr>
                <th>DS Number</th>
                <th>Gate</th>
                <th>Supplier Part Number</th>
                 <th>DI Type</th>
                <th>Received Date</th>
                <th>Received Time</th>
                <th>Qty</th>
       
                
            </tr>
        </thead>
        <tbody>
            @forelse($dsInputs as $ds)
                <tr>
                    <td>{{ $ds->ds_number }}</td>
                    <td>{{ $ds->gate }}</td>
                    <td>{{ $ds->supplier_part_number }}</td>
                     <td>{{ $ds->di_type }}</td>
                        <td>{{ $ds->di_received_date_string 
                        ? \Carbon\Carbon::parse($ds->di_received_date_string)->format('d-m-Y') 
                        : '-' }}</td>
                    <td>{{ $ds->di_received_time ?? '-' }}</td>  
                    <td>{{ $ds->qty }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>