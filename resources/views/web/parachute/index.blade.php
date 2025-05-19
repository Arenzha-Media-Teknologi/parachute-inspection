@extends('web.layouts.app')

@section('title', 'Parasut')

@section('prehead')
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <div data-kt-swapper="true" data-kt-swapper-mode="prepend" data-kt-swapper-parent="{default: '#kt_content_container', 'lg': '#kt_toolbar_container'}" class="page-title d-flex align-items-center me-3 flex-wrap lh-1">
                <h1 class="d-flex align-items-center text-gray-900 fw-bold my-1 fs-3">Parasut</h1>
                <span class="h-20px border-gray-200 border-start mx-4"></span>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="/" class="text-muted text-hover-primary">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-300 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="/parachute" class="text-muted text-hover-primary">Parasut</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-300 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-gray-900">Daftar Parasut</li>
                </ul>
            </div>
            <div class="d-flex align-items-center py-1">
            </div>
        </div>
    </div>
    <div class="post d-flex flex-column-fluid" id="app">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-kt-customer-table-filter="search" class="form-control form-control-solid w-250px ps-13 searchNumber" placeholder="Cari Data Parasut" />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                            <a href="/parachute/import" type="button" class="btn btn-light-success me-3"><i class="ki-outline ki-exit-up fs-2"></i>Upload Data Parasut</a>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_create">Tambah Parasut</button>
                        </div>
                        <div class="d-flex justify-content-end align-items-center d-none" data-kt-customer-table-toolbar="selected">
                            <div class="fw-bold me-5">
                                <!-- <span class="me-2" data-kt-customer-table-select="selected_count"></span>Selected -->
                            </div>
                            <button type="button" class="btn btn-danger" data-kt-customer-table-select="delete_selected">Hapus Data yang dipilih</button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table table-hover table-rounded table-striped border gy-7 gs-7" id="parachute-table">
                        <thead>
                            <tr>
                                <th style="width: 20px; text-align: center;">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" id="select-all-checkbox" />
                                    </div>
                                </th>
                                <th style="font-weight: bolder; text-align:left; font-size:14px;">Serial Number</th>
                                <th style="font-weight: bolder; text-align:center; font-size:14px;">Jenis Parasut</th>
                                <th style="font-weight: bolder; text-align:center; font-size:14px;">Tipe Parasut</th>
                                <th style="font-weight: bolder; text-align:center; font-size:14px;">Part Number</th>
                                <th style="font-weight: bolder; text-align:center; font-size:14px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modals (keep your existing modals) -->
            <!-- ... -->

        </div>
    </div>
</div>
@endsection

@section('script')
<style>
    .form-check-input {
        width: 1.2em;
        height: 1.2em;
    }

    .form-check-input:checked {
        background-color: #009EF7;
        border-color: #009EF7;
    }

    .dataTables_wrapper .dataTables_filter input {
        margin-left: 0.5em;
    }
</style>
@endsection

@section('pagescript')
<script>
    $(function() {
        toastr.options = {
            "closeButton": false,
            "debug": false,
            "newestOnTop": false,
            "progressBar": false,
            "positionClass": "toastr-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
    })
</script>
<script>
    const parachute = <?php echo Illuminate\Support\Js::from($parachute) ?>;
    let app = new Vue({
        el: '#app',
        data: {
            parachute,
            serialNumber: '',
            category: '',
            type: '',
            partNumber: '',
            parachuteDetail: [],
            selectedParachute: [],
            loading: false,
        },
        methods: {
            onSelcected: function(id) {
                this.parachuteDetail = this.parachute.filter((item) => {
                    return item.id == id;
                })[0]
            },
            submitForm: function() {
                if (this.serialNumber == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Serial Number tidak boleh kosong.',
                        'warning'
                    )
                } else if (this.type == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Tipe Parasut tidak boleh kosong.',
                        'warning'
                    )
                } else if (this.partNumber == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Part Number tidak boleh kosong .',
                        'warning'
                    )
                } else {
                    this.sendData();
                    this.loading = true;
                }
            },
            submitFormEdit: function() {
                if (this.parachuteDetail['serial_number'] == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Serial Number tidak boleh kosong.',
                        'warning'
                    )
                } else if (this.parachuteDetail['type'] == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Tipe Parasut tidak boleh kosong.',
                        'warning'
                    )
                } else if (this.parachuteDetail['part_number'] == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Part Number tidak boleh kosong .',
                        'warning'
                    )
                } else {
                    this.sendDataEdit();
                    this.loading = true;
                }
            },
            sendData: function() {
                let vm = this;
                vm.loading = true;
                axios.post('/parachute', {
                        serialNumber: this.serialNumber,
                        type: this.type,
                        category: this.category,
                        partNumber: this.partNumber,
                    })
                    .then(function(response) {
                        vm.loading = false;
                        let message = response?.data?.message;
                        if (!message) {
                            message = 'Data berhasil disimpan'
                        }
                        toastr.success(message);
                        setTimeout(function() {
                            window.location.href = '/parachute';
                        }, 1000);
                    })
                    .catch(function(error) {
                        vm.loading = false;
                        console.log(error);
                        let message = error?.response?.data?.message;
                        if (!message) {
                            message = 'Something wrong...'
                        }
                        toastr.error(message);
                    });
            },
            sendDataEdit: function() {
                let vm = this;
                vm.loading = true;
                axios.patch('/parachute/' + this.parachuteDetail['id'], {
                        serialNumber: this.parachuteDetail['serial_number'],
                        type: this.parachuteDetail['type'],
                        category: this.parachuteDetail['category'],
                        partNumber: this.parachuteDetail['part_number'],
                    })
                    .then(function(response) {
                        vm.loading = false;
                        let message = response?.data?.message;
                        if (!message) {
                            message = 'Data berhasil disimpan'
                        }
                        toastr.success(message);
                        setTimeout(function() {
                            window.location.href = '/parachute';
                        }, 1000);
                    })
                    .catch(function(error) {
                        vm.loading = false;
                        console.log(error);
                        let message = error?.response?.data?.message;
                        if (!message) {
                            message = 'Something wrong...'
                        }
                        toastr.error(message);
                    });
            },
            toggleSelectAll: function(isChecked) {
                const table = $('#parachute-table').DataTable();
                const currentPageIds = table.rows({
                    search: 'applied'
                }).data().toArray().map(row => row.id);

                if (isChecked) {
                    // Add all visible IDs to selected array
                    currentPageIds.forEach(id => {
                        if (!this.selectedParachute.includes(id)) {
                            this.selectedParachute.push(id);
                        }
                    });
                } else {
                    // Remove all visible IDs from selected array
                    this.selectedParachute = this.selectedParachute.filter(id => !currentPageIds.includes(id));
                }
            },
            deleteSelected: function() {
                if (this.selectedParachute.length === 0) {
                    Swal.fire('Error', 'No items selected', 'error');
                    return;
                }


                Swal.fire({
                    title: 'Delete Selected Items?',
                    text: `Apakah yakin ingin menghapus data parasut yang diceklis.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('/parachute/delete-multiple', {
                                ids: this.selectedParachute

                            })
                            .then(response => {
                                Swal.fire('Success', 'Data berhasil dihapus', 'success');
                                $('#parachute-table').DataTable().ajax.reload();
                                this.selectedParachute = [];
                                this.updateSelectedCount();
                            })
                            .catch(error => {
                                Swal.fire('Error', 'Gagal Menghapus data', 'error');
                            });
                    }
                });
            },
            updateSelectedCount: function() {
                const count = this.selectedParachute.length;
                $('[data-kt-customer-table-select="selected_count"]').text(count);

                if (count > 0) {
                    $('[data-kt-customer-table-toolbar="base"]').addClass('d-none');
                    $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
                } else {
                    $('[data-kt-customer-table-toolbar="base"]').removeClass('d-none');
                    $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
                }
            }
        },
        mounted() {
            const vm = this;

            // Initialize DataTable
            const Table = $('#parachute-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/parachute/datatables',
                    data: function(d) {
                        d.number = $('.searchNumber').val()
                    }
                },
                columns: [{
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            const isChecked = vm.selectedParachute.includes(row.id) ? 'checked' : '';
                            return `<div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input select-checkbox" type="checkbox" value="${row.id}" ${isChecked} />
                            </div>`;
                        }
                    },
                    {
                        data: 'serial_number',
                        name: 'serial_number',
                        render: function(data, type) {
                            return `<div class="text-start">${data}</div>`;
                        }
                    },
                    {
                        data: 'category',
                        name: 'category',
                        render: function(data, type, row) {
                            return `<div class="text-center">${data}</div>`;
                        }
                    },
                    {
                        data: 'type',
                        name: 'type',
                        render: function(data, type, row) {
                            return `<div class="text-center">${data}</div>`;
                        }
                    },
                    {
                        data: 'part_number',
                        name: 'part_number',
                        render: function(data, type, row) {
                            return `<div class="text-center">${data}</div>`;
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                    },
                ],
                searching: false,
                order: [
                    [1, 'desc']
                ],
                drawCallback: function(settings) {
                    // Update checkboxes state after table redraw
                    $('.select-checkbox').each(function() {
                        const id = $(this).val();
                        $(this).prop('checked', vm.selectedParachute.includes(id));
                    });

                    // Update "Select All" checkbox state
                    const allChecked = $('.select-checkbox:visible').length > 0 &&
                        $('.select-checkbox:visible').length === $('.select-checkbox:visible:checked').length;
                    $('#select-all-checkbox').prop('checked', allChecked);
                }
            });

            // Search functionality
            $(".searchNumber").keyup(function() {
                Table.draw();
            });

            // Handle individual checkbox clicks
            $(document).on('change', '.select-checkbox', function() {
                const id = $(this).val();
                const isChecked = $(this).is(':checked');

                if (isChecked) {
                    if (!vm.selectedParachute.includes(id)) {
                        vm.selectedParachute.push(id);
                    }
                } else {
                    vm.selectedParachute = vm.selectedParachute.filter(item => item !== id);
                    $('#select-all-checkbox').prop('checked', false);
                }

                vm.updateSelectedCount();
            });

            // Handle "Select All" checkbox
            $(document).on('change', '#select-all-checkbox', function() {
                const isChecked = $(this).is(':checked');
                $('.select-checkbox').prop('checked', isChecked).trigger('change');
                vm.toggleSelectAll(isChecked);
                vm.updateSelectedCount();
            });

            // Handle delete selected button
            $(document).on('click', '[data-kt-customer-table-select="delete_selected"]', function() {
                vm.deleteSelected();
            });

            // Handle single delete
            $('#parachute-table').on('click', 'tr .btn-delete', function(e) {
                e.preventDefault();
                const id = $(this).attr('data-id');
                Swal.fire({
                    title: 'Yakin Ingin menghapus data Parasut?',
                    icon: 'warning',
                    reverseButtons: true,
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return axios.delete('/parachute/' + id)
                            .then(function(response) {
                                console.log(response.data);
                            })
                            .catch(function(error) {
                                console.log(error.data);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops',
                                    text: 'Something wrong',
                                })
                            });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Parasut berhasil dihapus',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Table.ajax.reload();
                                // Remove from selected if it was selected
                                vm.selectedParachute = vm.selectedParachute.filter(item => item !== id);
                                vm.updateSelectedCount();
                            }
                        })
                    }
                })
            });

            // Edit button handler
            $(document).on('click', '.btn-edit-parasut', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                vm.onSelcected(id);
            });
        }
    });
</script>
@endsection