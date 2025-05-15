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


                    <li class="breadcrumb-item text-gray-900">Daftar Parsut</li>

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
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_create">Tambah Parasut</button>
                        </div>

                        <div class="d-flex justify-content-end align-items-center d-none" data-kt-customer-table-toolbar="selected">
                            <div class="fw-bold me-5">
                                <span class="me-2" data-kt-customer-table-select="selected_count"></span>Selected
                            </div>
                            <button type="button" class="btn btn-danger" data-kt-customer-table-select="delete_selected">Delete Selected</button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table table-hover table-rounded table-striped border gy-7 gs-7" id="parachute-table">
                        <thead>
                            <tr>
                                <th class="text-center">Serial Number</th>
                                <th class="text-center">Tipe Parasut</th>
                                <th class="text-center">Part Number</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal fade" tabindex="-1" id="kt_modal_create">
                <div class="modal-dialog modal-dialog-centered mw-650px">
                    <div class="modal-content">
                        <form class="form" @submit.prevent="submitForm">
                            <div class="modal-header" id="kt_modal_add_customer_header">
                                <h2 class="fw-bold">Tambah Parasut</h2>

                            </div>
                            <div class="modal-body py-10 px-lg-17">

                                <div class="scroll-y me-n7 pe-7" id="kt_modal_add_customer_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_customer_header" data-kt-scroll-wrappers="#kt_modal_add_customer_scroll" data-kt-scroll-offset="300px">
                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Serial Number
                                            <span class="ms-1" data-bs-toggle="tooltip" title="Serial Number Harus unik">
                                                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control form-control-solid" placeholder="" v-model="serialNumber" />
                                    </div>

                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Tipe Parasut</label>
                                        <input type="text" class="form-control form-control-solid" placeholder="" v-model="type" />
                                    </div>

                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Part Number</label>
                                        <input type="text" class="form-control form-control-solid" placeholder="" v-model="partNumber" />
                                    </div>

                                </div>
                            </div>
                            <div class="modal-footer flex-center">
                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success" :data-kt-indicator="loading ? 'on' : null" :disabled="loading">
                                    <span class="indicator-label">Simpan</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" tabindex="-1" id="kt_modal_edit">
                <div class="modal-dialog modal-dialog-centered mw-650px">
                    <div class="modal-content">
                        <form class="form" @submit.prevent="submitFormEdit">
                            <div class="modal-header" id="kt_modal_add_customer_header">
                                <h2 class="fw-bold">Edit Parasut</h2>

                            </div>
                            <div class="modal-body py-10 px-lg-17">

                                <div class="scroll-y me-n7 pe-7" id="kt_modal_add_customer_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_customer_header" data-kt-scroll-wrappers="#kt_modal_add_customer_scroll" data-kt-scroll-offset="300px">
                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Serial Number
                                            <span class="ms-1" data-bs-toggle="tooltip" title="Serial Number Harus unik">
                                                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control form-control-solid" placeholder="" v-model="parachuteDetail.serial_number" />
                                    </div>

                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Tipe Parasut</label>
                                        <input type="text" class="form-control form-control-solid" placeholder="" v-model="parachuteDetail.type" />
                                    </div>

                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Part Number</label>
                                        <input type="text" class="form-control form-control-solid" placeholder="" v-model="parachuteDetail.part_number" />
                                    </div>

                                </div>
                            </div>
                            <div class="modal-footer flex-center">
                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success" :data-kt-indicator="loading ? 'on' : null" :disabled="loading">
                                    <span class="indicator-label">Simpan</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
        <!--end::Container-->
    </div>
    <!--end::Post-->
</div>
@endsection
@section('script')
<!-- <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script> -->
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
            type: '',
            partNumber: '',
            parachuteDetail: [],

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
                        partNumber: this.partNumber,
                    })
                    .then(function(response) {
                        vm.loading = false;

                        let message = response?.data?.message;
                        if (!message) {
                            message = 'Data berhasil disimpan'
                        }

                        const data = response?.data?.data;

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
                        partNumber: this.parachuteDetail['part_number'],
                    })
                    .then(function(response) {
                        vm.loading = false;

                        let message = response?.data?.message;
                        if (!message) {
                            message = 'Data berhasil disimpan'
                        }

                        const data = response?.data?.data;

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
        },
    })
</script>

<script>
    $(function() {
        var Table = $('#parachute-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/parachute/datatables',
                data: function(d) {
                    d.number = $('.searchNumber').val()
                }
            },
            columns: [{
                    data: 'serial_number',
                    name: 'serial_number',
                    render: function(data, type) {
                        return `<div class="text-center font-weight-bolder">${data}</div>`;
                    }
                },
                {
                    data: 'part_number',
                    name: 'part_number',
                    render: function(data, type, row) {
                        return `<div class="text-center font-weight-bolder">${data}</div>`;
                    }
                },
                {
                    data: 'type',
                    name: 'type',
                    render: function(data, type, row) {
                        return `<div  class="text-center font-weight-bolder">${data}</div>`;
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                },

            ],
            searching: false, // Menonaktifkan pencarian
            order: [
                [3, 'desc']
            ]
        });

        $(".searchNumber").keyup(function() {
            Table.draw();
        });

        $('#parachute-table').on('click', 'tr .btn-delete', function(e) {
            e.preventDefault();
            // alert('click');
            const id = $(this).attr('data-id');
            Swal.fire({
                title: 'Yakin Ingin menghapus data Parasut?',
                // text: "The data will be deleted",
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
                            // window.location.reload();
                            Table.ajax.reload();
                        }
                    })
                }
            })
        })

        $(document).on('click', '.btn-edit-parasut', function(e) {
            e.preventDefault();

            const id = $(this).data('id');

            if (typeof app !== 'undefined' && app.onSelcected) {
                app.onSelcected(id);
            }
        });
    })
</script>
@endsection