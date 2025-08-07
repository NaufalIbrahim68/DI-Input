@extends('layouts.app')

@section('content')
    <div class="container-fluid">

        <div class="bg-white p-4 shadow rounded">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-5xl  text-dark">Data DS</h1>
            </div>
        </div>
        {{-- Flash message --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif


        <div class="row mb-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center">

                    <label for="entriesSelect" class="me-2 py-3">Show:</label>
                    <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center">
                        <!-- Preserve existing query parameters -->
                        @foreach(request()->except(['per_page', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <select name="per_page" id="entriesSelect" class="form-select form-select-sm" style="width: auto;"
                            onchange="this.form.submit()">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <span class="ms-2">entries</span>
                    </form>
                </div>
            </div>
            <div class="col-md-6">
                <!-- Optional: Add search functionality -->
                <form method="GET" action="{{ request()->url() }}" class="d-flex py-2">
                    @foreach(request()->except(['search', 'page']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search..."
                            value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">🔍</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive" style="overflow-x: auto;">
            <table id="dsTable" class="table table-bordered bg-white">
                <thead class="bg-black text-white">
                    <tr>
                        <th>No</th>
                        <th>DS Number</th>
                        <th>Gate</th>
                        <th>Supplier Part Number</th>
                        <th>Qty</th>
                        <th>DI Type</th>
                        <th>DI Status</th>
                        <th>Flag</th>
                        <th>Received Date</th>
                        <th>Received Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dsInputs as $index => $ds)
                        <tr>
                            <td class="text-dark">{{ $dsInputs->firstItem() + $index }}</td>
                            <td class="text-dark">{{ $ds->ds_number }}</td>
                            <td class="text-dark">{{ $ds->gate }}</td>
                            <td class="text-dark">{{ $ds->supplier_part_number }}</td>
                            <td class="text-dark">{{ $ds->qty }}</td>
                            <td class="text-dark">{{ $ds->di_type }}</td>
                            <td class="text-dark">{{ $ds->di_status }}</td>
                            <td class="text-dark">{{ $ds->flag }}</td>
                            <td class="text-dark">
                                {{ $ds->di_received_date_display ?? '-' }}
                            </td>
                            <td class="text-dark">{{ $ds->di_received_time }}</td>
                            <td class="text-dark">
                                <div class="d-flex justify-content-start gap-2">
                                    <button class="btn btn-sm  flex-fill bg-white text-nowrap show-edit-form"
                                        data-ds="{{ $ds->ds_number }}">
                                        ✏️
                                    </button>

                                    <form action="{{ route('ds_input.destroy', $ds->id ?? '') }}" method="POST"
                                        onsubmit="return confirm('Yakin ingin hapus data ini?')" class="flex-fill">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm  bg-white text-nowrap">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                        {{-- Tempat munculnya form edit --}}
                        <tr id="edit-row-{{ $ds->ds_number }}" class="edit-form-row" style="display: none;">
                            <td colspan="10">
                                <form method="POST" action="{{ route('ds_input.update', $ds->ds_number) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col">
                                            <input type="text" name="gate" class="form-control" value="{{ $ds->gate }}"
                                                required>
                                        </div>
                                        <div class="col">
                                            <input type="text" name="supplier_part_number" class="form-control"
                                                value="{{ $ds->supplier_part_number }}" required>
                                        </div>
                                        <div class="col">
                                            <input type="number" name="qty" class="form-control" value="{{ $ds->qty }}"
                                                required>
                                        </div>
                                        <div class="col">
                                            <input type="text" name="di_type" class="form-control" value="{{ $ds->di_type }}">
                                        </div>
                                        <div class="col">
                                            <input type="text" name="di_status" class="form-control"
                                                value="{{ $ds->di_status }}">
                                        </div>
                                        <div class="col">
                                            <input type="date" name="di_received_date_string" class="form-control"
                                                value="{{ $ds->di_received_date_string }}">
                                        </div>
                                        <div class="col">
                                            <input type="time" name="di_received_time" class="form-control"
                                                value="{{ \Carbon\Carbon::parse($ds->di_received_time)->format('H:i') }}">
                                        </div>
                                        <div class="col">
                                            <select name="flag" class="form-control">
                                                <option value="0" {{ $ds->flag == 0 ? 'selected' : '' }}>0</option>
                                                <option value="1" {{ $ds->flag == 1 ? 'selected' : '' }}>1</option>
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <button class="btn btn-success btn-sm">💾 Simpan</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="row mt-3">
            <div class="col-md-6">
                <small class="text-muted">
                    Showing {{ $dsInputs->firstItem() ?? 0 }} to {{ $dsInputs->lastItem() ?? 0 }}
                    of {{ $dsInputs->total() }} entries
                </small>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    {{ $dsInputs->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

        <!-- Optional: Custom pagination styling -->
        <style>
            .pagination {
                margin: 0;
            }

            .pagination .page-link {
                color: #495057;
                border-color: #dee2e6;
            }

            .pagination .page-item.active .page-link {
                background-color: #007bff;
                border-color: #007bff;
            }

            .pagination .page-link:hover {
                color: #0056b3;
                background-color: #e9ecef;
                border-color: #dee2e6;
            }

            .form-select-sm {
                font-size: 0.875rem;
                padding: 0.25rem 0.5rem;
            }
        </style>

@endsection

    @section('scripts')
        {{-- Jika pakai DataTables --}}
        <script>
            $(document).ready(function () {
                $('#dsTable').DataTable();
            });
        </script>
    @endsection

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const editButtons = document.querySelectorAll(".show-edit-form");
            editButtons.forEach(btn => {
                btn.addEventListener("click", function () {
                    const dsNumber = this.getAttribute("data-ds");
                    const formRow = document.getElementById("edit-row-" + dsNumber);
                    formRow.style.display = formRow.style.display === "none" ? "table-row" : "none";
                });
            });
        });
    </script>

    <script>
        $(document).ready(function () {
            $('#dsTable').DataTable({
                responsive: true,
                scrollX: true
            });
    </script>