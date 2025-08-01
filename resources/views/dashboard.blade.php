@extends('layouts.app')

@section('content')
  <div class="max-w-7xl mx-auto px-4">
   <h1 class="h2 mb-4 fw-semibold text-dark">Dashboard</h1>


    <div class="bg-white p-6 rounded-xl shadow mb-6 mt-6">

    <!-- Full-width Chart -->
    <div class="h-[500px] w-full flex justify-center items-center">
      <canvas id="myChart" class="w-full h-full"></canvas>
    </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow mb-6 mt-6">
    <form method="GET" action="{{ route('dashboard') }}" class="mb-4">
      <label for="tanggal" class="text-black mr-2">Filter Tanggal:</label>
      <input type="date" id="tanggal" name="tanggal" value="{{ request('tanggal') }}" class="border p-2 rounded text-dark">

      <label for="supplier_part_number" class="text-black ml-4 mr-2">Supplier Part Number:</label>
      <input type="text" id="supplier_part_number" name="supplier_part_number"
      value="{{ request('supplier_part_number') }}" class="border p-2 rounded text-dark">

      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded ml-2">Filter</button>
      <a href="{{ route('dashboard') }}" class="bg-orange-500 text-white px-4 py-2 rounded ml-2">Reset</a>
    </form>

    @if((request('tanggal') || request('supplier_part_number')) && !$timeline->isEmpty())
    <div class="mb-2 text-sm text-blue-700 bg-blue-100 p-2 rounded">
      📅 Menampilkan data untuk:
      @if(request('tanggal'))
      <strong>Tanggal: {{ \Carbon\Carbon::parse(request('tanggal'))->format('d-m-Y') }}</strong>
    @endif
      @if(request('supplier_part_number'))
      <span class="ml-4">🔍 Supplier PN: <strong>{{ request('supplier_part_number') }}</strong></span>
    @endif

      @if($totalQty > 0)
      <div class="text-xl font-bold text-blue-600 mb-4">
      Total Quality: {{ number_format($totalQty) }}
      </div>
    @endif
    </div>
    @endif
    @if($timeline->isEmpty())
    <tr>
      <td colspan="4" class="text-dark py-4">Tidak ada data untuk tanggal tersebut.</td>
    </tr>
    @endif
    <div id="timeline-table">
      <table class="w-full table-auto border-collapse">
      <thead>
        <tr class="bg-dark-100 text-left">
        <th class="border p-2 bg-black text-white">Tanggal</th>
        <th class="border p-2 bg-black text-white">DI No</th>
        <th class="border p-2 bg-black text-white">Qty</th>
        <th class="border p-2 bg-black text-white">Supplier PartNumber</th>
        <th class="border p-2 bg-black text-white">Baan PartNumber</th>
        <th class="border p-2 bg-black text-white">Visteon PartNumber</th>

        </tr>
      </thead>
      <tbody>
        @foreach ($timeline as $item)
      <tr class="hover:bg-gray-50">
      <td class="text-black border px-4 py-2">
        {{ \Carbon\Carbon::parse($item->di_received_date)->format('d-m-Y') }}</td>
      <td class="text-black border p-2">{{ $item->di_no }}</td>
      <td class="text-black border p-2">{{ $item->qty }}</td>
      <td class="text-black border p-2">{{ $item->supplier_part_number }}</td>
      <td class="text-black border p-2">{{ $item->baan_pn }}</td>
      <td class="text-black border p-2">{{ $item->visteon_pn }}</td>
      </tr>
      @endforeach
      </tbody>
      </table>
    </div>
    </div>

    <!-- Inject PHP data into JavaScript variables -->
    <script>
    window.chartConfig = {
      labels: {!! json_encode($chartLabels) !!},
      data: {!! json_encode($chartData) !!}
    };
    </script>

    <!-- Chart Configuration -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      const ctx = document.getElementById('myChart');

      new Chart(ctx, {
      type: 'bar',
      data: {
        labels: window.chartConfig.labels,
        datasets: [{
        label: 'Total Qty Per Tanggal:',
        data: window.chartConfig.data,
        backgroundColor: window.chartConfig.labels.map((_, i) => [
          'rgba(255, 182, 193, 0.7)', // lightpink
          'rgba(173, 216, 230, 0.7)', // lightblue
          'rgba(255, 255, 153, 0.7)', // lightyellow
          'rgba(144, 238, 144, 0.7)', // lightgreen
          'rgba(221, 160, 221, 0.7)', // plum
          'rgba(255, 204, 153, 0.7)', // peach
          'rgba(224, 255, 255, 0.7)'  // lightcyan
        ][i % 7]),

        borderColor: window.chartConfig.labels.map((_, i) => [
          'rgba(255, 105, 180, 1)',   // hotpink
          'rgba(30, 144, 255, 1)',    // dodgerblue
          'rgba(255, 215, 0, 1)',     // gold
          'rgba(60, 179, 113, 1)',    // mediumseagreen
          'rgba(186, 85, 211, 1)',    // mediumorchid
          'rgba(255, 140, 0, 1)',     // darkorange
          'rgba(64, 224, 208, 1)'     // turquoise
        ][i % 7]),
        borderWidth: 1
        }]
      },
      options: {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        color: '#000000' // warna angka di sumbu Y jadi hitam
      },
      title: {
        display: true,
        text: 'Qty',
        color: '#000000'
      }
    },
    x: {
      ticks: {
        color: '#000000' // warna label bawah (tanggal) jadi hitam
      },
      title: {
        display: true,
        text: 'Tanggal',
        color: '#000000'
      }
    }
  },
  plugins: {
    legend: {
      labels: {
        color: '#000000' // warna teks legend jadi hitam
      }
    },
    title: {
      display: false
    }
  }
}

      });
    });
    </script>
  @endsection