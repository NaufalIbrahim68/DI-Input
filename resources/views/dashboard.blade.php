@extends('layouts.app')

@section('content')
    <style>
        /* Custom Dropdown Styling */
        .date-dropdown {
            position: relative;
            display: inline-block;
            width: auto;
            min-width: 250px;
        }

        .date-dropdown-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }

        .date-dropdown-trigger:hover {
            border-color: #9ca3af;
        }

        .date-dropdown-trigger.active {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
        }

        .date-dropdown-arrow {
            margin-left: 12px;
            transition: transform 0.2s;
        }

        .date-dropdown-trigger.active .date-dropdown-arrow {
            transform: rotate(180deg);
        }

        .date-dropdown-options {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            max-height: 0;
            overflow: hidden;
            background: white;
            border: none;
            z-index: 1000;
            opacity: 0;
            transition: max-height 0.3s ease, opacity 0.2s ease;
        }

        .date-dropdown-options.active {
            max-height: 300px;
            overflow-y: auto;
            opacity: 1;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .date-dropdown-option {
            padding: 10px 16px;
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .date-dropdown-option:hover {
            background-color: #f3f4f6;
        }

        .date-dropdown-option.selected {
            background-color: #dbeafe;
            color: #1e40af;
            font-weight: 500;
        }

        /* Styling untuk scrollbar */
        .date-dropdown-options::-webkit-scrollbar {
            width: 8px;
        }

        .date-dropdown-options::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .date-dropdown-options::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .date-dropdown-options::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>

    <div class="max-w-7xl mx-auto px-4">
        <h1 class="h2 mb-2 fw-semibold text-dark">Dashboard</h1>

        <!-- Container untuk kedua chart - posisi dinaikkan -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 mt-2">

            <!-- Chart Kiri - Chart Timeline yang sudah ada -->
            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-dark mb-4">Timeline Data</h3>

                <!-- Custom Dropdown untuk memilih tanggal chart -->
                <div class="flex justify-end mb-4">
                    <div class="date-dropdown" id="customDateSelect">
                        <div class="date-dropdown-trigger">
                            <span class="date-dropdown-text">
                                @php
                                    $firstGroup = collect($groupedChartData)->first();
                                @endphp
                                @if ($firstGroup)
                                    {{ \Carbon\Carbon::parse($firstGroup['labels'][0])->format('d-m-Y') }} s/d
                                    {{ \Carbon\Carbon::parse(end($firstGroup['labels']))->format('d-m-Y') }}
                                @endif
                            </span>
                            <svg class="date-dropdown-arrow w-4 h-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414L10 13.414 5.293 8.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="date-dropdown-options">
                            @foreach ($groupedChartData as $index => $group)
                                <div class="date-dropdown-option {{ $index === 0 ? 'selected' : '' }}"
                                    data-value="{{ $index }}">
                                    {{ \Carbon\Carbon::parse($group['labels'][0])->format('d-m-Y') }} s/d
                                    {{ \Carbon\Carbon::parse(end($group['labels']))->format('d-m-Y') }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Chart Timeline -->
                <div class="h-[320px] w-full flex justify-center items-center">
                    <canvas id="myChart" class="w-full h-full drop-shadow-md rounded-xl"></canvas>
                </div>
            </div>

            <!-- Chart Kanan - Status Preparation -->
            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-dark mb-4">Status Preparation (7 Hari Terakhir)</h3>

                <!-- Chart Status Preparation -->
                <div class="h-[320px] w-full flex justify-center items-center">
                    <canvas id="statusChart" class="w-full h-full drop-shadow-md rounded-xl"></canvas>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white p-6 rounded-xl shadow mb-6 mt-4">
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

            @if ((request('tanggal') || request('supplier_part_number')) && !$timeline->isEmpty())
                <div class="mb-2 text-sm text-blue-700 bg-blue-100 p-2 rounded">
                    📅 Menampilkan data untuk:
                    @if (request('tanggal'))
                        <strong>Tanggal: {{ request('tanggal') }}</strong>
                    @endif

                    @if (request('supplier_part_number'))
                        <span class="ml-4">🔍 Supplier PN: <strong>{{ request('supplier_part_number') }}</strong></span>
                    @endif

                    @if ($totalQty > 0)
                        <div class="text-xl font-bold text-blue-600 mb-4">
                            Total Qty: {{ number_format($totalQty) }}
                        </div>
                    @endif
                </div>
            @endif

            @if ($timeline->isEmpty())
                <div class="text-dark py-4">Tidak ada data untuk tanggal tersebut.</div>
            @endif
            <div id="timeline-table" class="w-full">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs md:text-sm">
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
                                    <td class="text-black border p-2">
                                        {{ $item->di_received_date_string ? \Carbon\Carbon::parse($item->di_received_date_string)->format('d-m-Y') : '-' }}
                                    </td>
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

            @if ($isFiltered)
                <div class="mt-4">
                    {{ $timeline->appends(request()->except('page'))->links() }}
                </div>
            @endif

            <!-- JavaScript untuk kedua chart -->
            <script>
                // Data dari controller
                const chartConfig = @json($groupedChartData);
                const statusData = @json($statusData ?? ['completed' => 0, 'non_completed' => 0]);
                let chartInstance = null;
                let statusChartInstance = null;

                // Fungsi bantu konversi hex ke rgba
                function hexToRgba(hex, alpha) {
                    const bigint = parseInt(hex.replace('#', ''), 16);
                    const r = (bigint >> 16) & 255;
                    const g = (bigint >> 8) & 255;
                    const b = bigint & 255;
                    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                }

                // Warna dasar untuk chart timeline
                const colors = [
                    '#f87171', // merah muda
                    '#facc15', // kuning
                    '#4ade80', // hijau
                    '#60a5fa', // biru
                    '#a78bfa', // ungu
                    '#f472b6', // pink
                    '#f97316', // oranye
                ];
            </script>

            <script>
                // Fungsi render chart timeline (kiri)
                function renderChart(labels, data) {
                    const ctx = document.getElementById('myChart').getContext('2d');

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
                                    hexToRgba(colors[i % colors.length], 0.3)
                                ),
                                borderColor: labels.map((_, i) =>
                                    hexToRgba(colors[i % colors.length], 0.6)
                                ),
                                borderWidth: 1,
                                hoverBackgroundColor: labels.map((_, i) =>
                                    hexToRgba(colors[i % colors.length], 0.5)
                                )
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#000'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Qty',
                                        color: '#000'
                                    }
                                },
                                x: {
                                    ticks: {
                                        color: '#000'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Tanggal',
                                        color: '#000'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
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

                // Fungsi render chart status preparation (kanan) pakai Doughnut
                function renderStatusChart() {
                    const ctx = document.getElementById('statusChart').getContext('2d');

                    if (statusChartInstance) {
                        statusChartInstance.destroy();
                    }

                    const completed = statusData.completed || 0;
                    const partial = statusData.partial || 0;

                    statusChartInstance = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Completed', 'Partial'],
                            datasets: [{
                                data: [completed, partial],
                                backgroundColor: [
                                    'rgba(34, 197, 94, 0.8)', // hijau
                                    'rgba(234, 179, 8, 0.8)' // kuning
                                ],
                                borderColor: [
                                    'rgba(34, 197, 94, 1)',
                                    'rgba(234, 179, 8, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: '#000'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0,0,0,0.7)',
                                    titleColor: '#fff',
                                    bodyColor: '#fff',
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.parsed;
                                            const total = completed + partial;
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            return `${context.label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // Jalankan saat halaman selesai dimuat
                document.addEventListener('DOMContentLoaded', () => {
                    const defaultGroup = chartConfig[0];

                    if (defaultGroup) {
                        renderChart(defaultGroup.labels, defaultGroup.data);
                    }

                    renderStatusChart();

                    // Custom Dropdown Functionality
                    const customSelect = document.getElementById('customDateSelect');
                    const trigger = customSelect.querySelector('.date-dropdown-trigger');
                    const options = customSelect.querySelector('.date-dropdown-options');
                    const optionItems = customSelect.querySelectorAll('.date-dropdown-option');
                    const selectedText = customSelect.querySelector('.date-dropdown-text');

                    // Toggle dropdown on click
                    trigger.addEventListener('click', function(e) {
                        e.stopPropagation();
                        trigger.classList.toggle('active');
                        options.classList.toggle('active');
                    });

                    // Handle option selection
                    optionItems.forEach(option => {
                        option.addEventListener('click', function() {
                            const value = this.getAttribute('data-value');
                            const text = this.textContent.trim();

                            // Update selected state
                            optionItems.forEach(opt => opt.classList.remove('selected'));
                            this.classList.add('selected');

                            // Update display text
                            selectedText.textContent = text;

                            // Update chart
                            const selected = chartConfig[value];
                            if (selected) {
                                renderChart(selected.labels, selected.data);
                            }

                            // Close dropdown
                            trigger.classList.remove('active');
                            options.classList.remove('active');
                        });
                    });

                    // Close dropdown when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!customSelect.contains(e.target)) {
                            trigger.classList.remove('active');
                            options.classList.remove('active');
                        }
                    });
                });
            </script>


        @endsection
