@extends('layouts.app')

@section('content')
    <div class="container-fluid">

       
        @php
            $selectedDate = request('tanggal');
        @endphp

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show shadow-sm rounded-3">
                <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="mb-3">
            <form method="GET" action="{{ route('ds_input.index') }}">
                <div class="d-flex align-items-center gap-2">
                    <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control"
                        style="width:200px;" required>
                    <button type="submit" class="btn btn-success">Filter Tanggal</button>
                </div>
            </form>
        </div>

    @if(!empty($selectedDate))
    <div class="alert alert-info text-center">
        Menampilkan data DS untuk tanggal 
        <strong>{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</strong>,
        total <strong>{{ $total }}</strong> data
    </div>
@endif

        <div class="d-flex gap-2 mb-3">
            <a href="{{ route('ds_input.export.pdf', ['tanggal' => request('tanggal')]) }}" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf me-1"></i> Export PDF
            </a>
            <a href="{{ route('ds_input.export.excel', ['tanggal' => request('tanggal')]) }}"
                class="btn btn-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
        </div>

        <div class="table-responsive" style="overflow-x:auto; width:100%;">
            <table class="table table-bordered table-sm bg-white small">
                <thead class="bg-black text-white">
                    <tr>
                        <th>No</th>
                        <th>DS Number</th>
                        <th>Gate</th>
                        <th>DI Type</th>
                        <th>Supplier Part Number</th>
                        <th>Received Date</th>
                        <th>Received Time</th>
                        <th>Qty</th>
                        <th>Qty Prep</th>
                        <th>Status Preparation</th>
                        <th>Qty Delivery</th>
                        <th>Status Delivery</th>
                        <th>DN Number</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if($dsInputs && $dsInputs->count() > 0)
                        @foreach ($dsInputs as $index => $ds)
                                <tr data-ds-number="{{ $ds->ds_number }}">
                                   <td class="text-black">{{ $dsInputs->firstItem() + $index }}</td>
                                    <td class="text-black">{{ $ds->ds_number ?? '-' }}</td>
                                    <td class="text-black">{{ $ds->gate ?? '-' }}</td>
                                    <td class="text-black">{{ $ds->di_type ?? '-' }}</td>
                                    <td class="text-black">{{ $ds->supplier_part_number ?? '-' }}</td>
                                    <td class="text-black">
                                        {{ !empty($ds->di_received_date_string)
                            ? \Carbon\Carbon::parse($ds->di_received_date_string)->format('d-m-Y')
                            : '-' }}
                                    </td>
                                    <td class="text-black">{{ $ds->di_received_time ?? '-' }}</td>
                                    <td class="text-black">{{ $ds->qty ?? '-' }}</td>
                                    <td class="text-black">
                                        {{ ($ds->qty_prep ?? 0) > 0 ? $ds->qty_prep : '' }}
                                    </td>
                                    <td class="text-black">
                                        @php
                                            $qtyDI = (int) ($ds->qty ?? 0);
                                            $qtyPrep = (int) ($ds->qty_prep ?? 0);

                                            if ($qtyPrep < $qtyDI) {
                                                $statusPrep = $qtyPrep - $qtyDI; // negatif
                                            } elseif ($ds->flag_prep == 1) {
                                                $statusPrep = 'Completed';
                                            } else { // qtyPrep > qtyDI
                                                $statusPrep = 'Over';
                                            }
                                        @endphp

                                        @if(is_numeric($statusPrep) && $statusPrep < 0)
                                            <span class="badge bg-danger text-white">{{ $statusPrep }}</span>
                                        @elseif($statusPrep === 'Completed')
                                            <span class="badge bg-success text-white">{{ $statusPrep }}</span>
                                        @elseif($statusPrep === 'Over')
                                            <span class="badge bg-warning text-white">{{ $statusPrep }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="number" name="qty_agv" value="{{ $ds->qty_agv > 0 ? $ds->qty_agv : '' }}"
                                            class="form-control form-control-sm text-center qty-delivery-input"
                                            data-ds-number="{{ $ds->ds_number }}">
                                    </td>
                                    <td>
                                        @php
                                            $qtyDelivery = (int) ($ds->qty_delivery ?? 0);
                                            $qtyDs = (int) ($ds->qty ?? 0);

                                            $status = ($ds->flag_agv == 1) ? 'completed' : 'partial';
                                        @endphp

                                        <span class="status-badge" data-ds-number="{{ $ds->ds_number }}">
                                            @if($status === 'completed')
                                                <span class="badge bg-success text-white">Completed</span>
                                            @else
                                                <span class="badge bg-warning text-white">Partial</span>
                                            @endif
                                        </span>
                                    </td>

                                    <td>
                                        <input type="text" name="dn_number" value="{{ $ds->dn_number ?? '' }}"
                                            class="form-control form-control-sm text-center dn-number-input"
                                            data-ds-number="{{ $ds->ds_number }}">
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center align-items-center">
                                            {{-- Save Button --}}
                                            <button type="button" class="btn btn-sm btn-success btn-save-ds"
                                                data-ds-number="{{ $ds->ds_number }}"
                                                data-update-url="{{ route('ds_input.update', $ds->ds_number) }}" title="Simpan">
                                                <i class="fas fa-save"></i>
                                            </button>

                                            {{-- Delete Button --}}
                                            <button type="button" class="btn btn-sm btn-danger btn-delete-ds"
                                                onclick="openDeleteModal('{{ $ds->ds_number }}', '{{ route('ds_input.destroy', $ds->ds_number) }}')"
                                                title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="14" class="text-center text-muted py-4">
                                <i class="fas fa-calendar-alt fa-2x mb-2 text-muted"></i>
                                <br>Pilih tanggal untuk menampilkan DS.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

       {{-- Pagination --}}
<div class="d-flex justify-content-center mt-3">
    {{ $dsInputs->onEachSide(2)->withQueryString()->links('pagination::bootstrap-5') }}
</div>

        {{--Modal Delete--}}
        <div id="deleteModal" class="custom-modal" style="display: none;">
            <div class="custom-modal-backdrop" onclick="closeDeleteModal()"></div>
            <div class="custom-modal-dialog">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <h5 id="deleteModalLabel">Hapus DS</h5>
                        <button type="button" class="custom-close-btn" onclick="closeDeleteModal()">&times;</button>
                    </div>
                    <form id="formDeleteDS" action="" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="custom-modal-body">
                            <div class="form-group mb-3">
                                <label for="password" class="form-label">Masukkan Password</label>
                                <input type="password" name="password" id="password" class="form-control" required
                                    autocomplete="off">
                            </div>
                            <div class="form-group mb-3">
                                <label for="reason" class="form-label">Alasan Hapus</label>
                                <textarea name="reason" id="reason" class="form-control" rows="3" required></textarea>
                            </div>
                            <p>Apakah Anda yakin ingin menghapus DS ini?</p>
                        </div>
                        <div class="custom-modal-footer">
                            <button type="button" class="btn btn-secondary custom-btn-cancel"
                                onclick="closeDeleteModal()">Batal</button>
                            <button type="submit" class="btn btn-danger custom-btn-delete"
                                onclick="return confirmDelete()">Hapus</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Hidden forms untuk submit data --}}
        <div id="hiddenForms" style="display: none;">
            @if($dsInputs && $dsInputs->count() > 0)
                @foreach ($dsInputs as $ds)
                    <form id="updateForm_{{ $ds->ds_number }}" action="{{ route('ds_input.update', $ds->ds_number) }}"
                        method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="qty_agv" id="hiddenQtyAgv_{{ $ds->ds_number }}">
                        <input type="hidden" name="dn_number" id="hiddenDnNumber_{{ $ds->ds_number }}">
                    </form>
                @endforeach
            @endif
        </div>

        <script>
            function openDeleteModal(dsNumber, deleteUrl) {
                console.log('Opening delete modal for DS:', dsNumber);
                console.log('Delete URL:', deleteUrl);


                document.getElementById('formDeleteDS').action = deleteUrl;


                document.getElementById('deleteModalLabel').innerText = 'Hapus DS ' + dsNumber;


                document.getElementById('password').value = '';
                document.getElementById('reason').value = '';


                document.getElementById('deleteModal').style.display = 'block';
                document.body.classList.add('modal-open');
            }

            // Fungsi untuk close modal
            function closeDeleteModal() {
                console.log('Closing delete modal...');
                document.getElementById('deleteModal').style.display = 'none';
                document.body.classList.remove('modal-open');

                // Reset form
                document.getElementById('password').value = '';
                document.getElementById('reason').value = '';
            }

            // Fungsi untuk konfirmasi delete
            function confirmDelete() {
                const password = document.getElementById('password').value.trim();
                const reason = document.getElementById('reason').value.trim();

                if (!password || !reason) {
                    alert('Password dan alasan hapus harus diisi!');
                    return false;
                }

                return confirm('Apakah Anda yakin ingin menghapus Data DS ini?');
            }

            // Close modal dengan ESC key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeDeleteModal();
                }
            });

            document.addEventListener('DOMContentLoaded', function () {
                console.log('DOM loaded...');

                // Initialize save buttons
                const saveButtons = document.querySelectorAll('.btn-save-ds');

                saveButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        const dsNumber = this.getAttribute('data-ds-number');
                        const qtyAgvInput = document.querySelector(`input[name="qty_agv"][data-ds-number="${dsNumber}"]`);
                        const dnNumberInput = document.querySelector(`input[name="dn_number"][data-ds-number="${dsNumber}"]`);

                        // Set values in hidden form
                        document.getElementById(`hiddenQtyAgv_${dsNumber}`).value = qtyAgvInput.value;
                        document.getElementById(`hiddenDnNumber_${dsNumber}`).value = dnNumberInput.value;

                        // Submit form
                        document.getElementById(`updateForm_${dsNumber}`).submit();
                    });
                });

                // Handle form submission validation
                if (formDelete) {
                    formDelete.addEventListener('submit', function (e) {
                        const password = passwordInput.value.trim();
                        const reason = reasonInput.value.trim();

                        if (!password || !reason) {
                            e.preventDefault();
                            alert('Password dan alasan hapus harus diisi!');
                            return false;
                        }
                    });
                }

                // Debug: Test if buttons are clickable
                setTimeout(() => {
                    const testButton = document.querySelector('.btn-delete-ds');
                    if (testButton) {
                        console.log('Test button found:', testButton);
                        console.log('Button style:', window.getComputedStyle(testButton));
                        console.log('Button z-index:', window.getComputedStyle(testButton).zIndex);
                    }
                }, 1000);
            });

            // Fallback untuk debugging
            setTimeout(() => {
                console.log('Checking for any JavaScript errors...');
                const deleteButtons = document.querySelectorAll('.btn-delete-ds');
                console.log('Delete buttons found after timeout:', deleteButtons.length);

                deleteButtons.forEach((btn, index) => {
                    console.log(`Button ${index}:`, btn, 'Is visible:', btn.offsetParent !== null);
                });
            }, 2000);
        </script>

        <style>
            /* Pastikan z-index yang benar */
            .btn {
                position: relative;
                z-index: 1;
            }

            .table td {
                position: relative;
            }

            /* Debug styles */
            .btn-delete-ds {
                pointer-events: auto !important;
                cursor: pointer !important;
            }

            .btn-delete-ds:hover {
                background-color: #dc3545 !important;
                border-color: #dc3545 !important;
                opacity: 0.8;
            }

            /* Custom Modal Styles */
            .custom-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                overflow: auto;
            }

            .custom-modal-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1;
            }

            .custom-modal-dialog {
                position: relative;
                z-index: 2;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }

            .custom-modal-content {
                background: white;
                border-radius: 8px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
                max-width: 500px;
                width: 100%;
                position: relative;
            }

            .custom-modal-header {
                padding: 16px 20px;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                justify-content: between;
                align-items: center;
                background: #f8f9fa;
                border-radius: 8px 8px 0 0;
            }

            .custom-modal-header h5 {
                margin: 0;
                flex: 1;
                font-size: 1.25rem;
                font-weight: 500;
            }

            .custom-close-btn {
                background: none;
                border: none;
                font-size: 24px;
                font-weight: bold;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #6c757d;
                margin-left: auto;
            }

            .custom-close-btn:hover {
                color: #000;
                background: #e9ecef;
                border-radius: 4px;
            }

            .custom-modal-body {
                padding: 20px;
            }

            .custom-modal-footer {
                padding: 16px 20px;
                border-top: 1px solid #dee2e6;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                background: #f8f9fa;
                border-radius: 0 0 8px 8px;
            }

            .custom-btn-cancel,
            .custom-btn-delete {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer !important;
                font-size: 14px;
                font-weight: 500;
                min-width: 80px;
                transition: all 0.2s;
            }

            .custom-btn-cancel {
                background: #6c757d;
                color: white;
            }

            .custom-btn-cancel:hover {
                background: #5a6268 !important;
                transform: translateY(-1px);
            }

            .custom-btn-delete {
                background: #dc3545;
                color: white;
            }

            .custom-btn-delete:hover {
                background: #c82333 !important;
                transform: translateY(-1px);
            }

            /* Prevent body scroll when modal is open */
            body.modal-open {
                overflow: hidden;
            }

            /* Form styles */
            .form-group {
                margin-bottom: 1rem;
            }

            .form-label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
            }

            .form-control {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ced4da;
                border-radius: 4px;
                font-size: 14px;
            }

            .form-control:focus {
                outline: none;
                border-color: #80bdff;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }
        </style>
       
      
    </div>
@endsection