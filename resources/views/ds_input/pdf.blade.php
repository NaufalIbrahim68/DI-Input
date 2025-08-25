<table border="1" cellspacing="0" cellpadding="5" width="100%">
    <thead>
        <tr>
            <th>DS Number</th>
            <th>Gate</th>
            <th>Supplier Part Number</th>
            <th>Qty</th>
            <th>DI Type</th>
            <th>Received Date</th>
            <th>Received Time</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dsInputs as $ds)
            <tr>
                <td>{{ $ds->ds_number }}</td>
                <td>{{ $ds->gate }}</td>
                <td>{{ $ds->supplier_part_number }}</td>
                <td>{{ $ds->qty }}</td>
                <td>{{ $ds->di_type }}</td>
                <td>{{ $ds->di_received_date_string }}</td>
                <td>{{ $ds->di_received_time }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
