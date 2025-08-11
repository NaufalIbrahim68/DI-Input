@extends('layouts.app')

@section('content')
  <div class="max-w-7xl mx-auto px-4">
    <h1 class="h2 mb-4 fw-semibold text-dark">Dashboard</h1>


    <div class="bg-white p-6 rounded-xl shadow mb-6 mt-6">

    <!-- Dropdown untuk memilih tanggal chart -->
    <div class="flex justify-end mb-4">
      <div class="relative inline-block">
      <select id="dateSelector"
        class="appearance-none border border-gray-300 rounded-lg pl-4 pr-10 py-2 bg-white shadow focus:outline-none focus:ring-2 focus:ring-blue-400 text-gray-800">
        @foreach ($groupedChartData as $index => $group)
      <option value="{{ $index }}">
     {{ \Carbon\Carbon::parse($group['labels'][0])->format('d-m-Y') }} s/d
      {{ \Carbon\Carbon::parse(end($group['labels']))->format('d-m-Y') }}
      </option>
      @endforeach
      </select>
      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd"
          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414L10 13.414 5.293 8.707a1 1 0 010-1.414z"
          clip-rule="evenodd" />
        </svg>
      </div>
      </div>
    </div>
    <!-- Full-width Chart -->
    <div class="h-[500px] w-full flex justify-center items-center">
      <canvas id="myChart" class="w-full h-full drop-shadow-md rounded-xl"></canvas>
    </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow mb-6 mt-6">
    <form method="GET" action="{{ route('dashboard') }}" class="mb-4">
      <label for="tanggal" class="text-black mr-2">Filter Tanggal:</label>
      <input type="date" id="tanggal" name="tanggal" value="{{ request('tanggal') }}"
      class="border p-2 rounded text-dark">

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
      Total Quantity: {{ number_format($totalQty) }}
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
      <td class="text-dark border p-2">
        {{ $item->di_received_date_string ?? '-' }}
      </td>
      <td class="text-dark border p-2">{{ $item->di_no }}</td>
      <td class="text-dark border p-2">{{ $item->qty }}</td>
      <td class="text-dark border p-2">{{ $item->supplier_part_number }}</td>
      <td class="text-dark border p-2">{{ $item->baan_pn }}</td>
      <td class="text-dark border p-2">{{ $item->visteon_pn }}</td>
      </tr>
      @endforeach
      </tbody>
      </table>
    </div>
    </div>

    <!-- Inject PHP data into JavaScript variables -->
    <script>
    // Data dari controller
    const chartConfig = @json($groupedChartData);
    let chartInstance = null;

    // Fungsi bantu konversi hex ke rgba
    function hexToRgba(hex, alpha) {
      const bigint = parseInt(hex.replace('#', ''), 16);
      const r = (bigint >> 16) & 255;
      const g = (bigint >> 8) & 255;
      const b = bigint & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    // Warna dasar untuk chart
    const colors = [
      '#f87171', // merah muda
      '#facc15', // kuning
      '#4ade80', // hijau
      '#60a5fa', // biru
      '#a78bfa', // ungu
      '#f472b6', // pink
      '#f97316', // oranye
    ];

    // Fungsi render chart
    function renderChart(labels, data) {
      const ctx = document.getElementById('myChart').getContext('2d');

      // Hapus chart sebelumnya jika ada
      if (chartInstance) {
      chartInstance.destroy();
      }

      chartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
        label: 'Total Qty',
        data: data,
        backgroundColor: labels.map((_, i) =>
          hexToRgba(colors[i % colors.length], 0.3) // transparan lembut
        ),
        borderColor: labels.map((_, i) =>
          hexToRgba(colors[i % colors.length], 0.6) // garis sedikit tajam
        ),
        borderWidth: 1,
        hoverBackgroundColor: labels.map((_, i) =>
          hexToRgba(colors[i % colors.length], 0.5) // saat hover
        )
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
        y: {
          beginAtZero: true,
          ticks: { color: '#000' },
          title: {
          display: true,
          text: 'Qty',
          color: '#000'
          }
        },
        x: {
          ticks: { color: '#000' },
          title: {
          display: true,
          text: 'Tanggal',
          color: '#000'
          }
        }
        },
        plugins: {
        legend: {
          labels: {
          color: '#000'
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.7)',
          titleColor: '#fff',
          bodyColor: '#fff',
        }
        }
      }
      });
    }

    // Jalankan saat halaman selesai dimuat
    document.addEventListener('DOMContentLoaded', () => {
      const selector = document.getElementById('dateSelector');
      const defaultGroup = chartConfig[0];

      if (defaultGroup) {
      renderChart(defaultGroup.labels, defaultGroup.data);
      }

      selector.addEventListener('change', function () {
      const index = this.value;
      const selected = chartConfig[index];
      if (selected) {
        renderChart(selected.labels, selected.data);
      }
      });
    });
    </script>

  @endsection