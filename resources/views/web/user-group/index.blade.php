@extends('web.layouts.app')

@section('title', 'User Group')

@section('prehead')
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <div data-kt-swapper="true" data-kt-swapper-mode="prepend" data-kt-swapper-parent="{default: '#kt_content_container', 'lg': '#kt_toolbar_container'}" class="page-title d-flex align-items-center me-3 flex-wrap lh-1">
                <h1 class="d-flex align-items-center text-gray-900 fw-bold my-1 fs-3">User Group</h1>
                <span class="h-20px border-gray-200 border-start mx-4"></span>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="/" class="text-muted text-hover-primary">Dashboard</a>
                    </li>

                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-300 w-5px h-2px"></span>
                    </li>

                    <li class="breadcrumb-item text-muted">
                        <a href="/user-group" class="text-muted text-hover-primary">User Group</a>
                    </li>

                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-300 w-5px h-2px"></span>
                    </li>


                    <li class="breadcrumb-item text-gray-900">Manage User Group</li>

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
                            <input type="text" data-kt-customer-table-filter="search" class="form-control form-control-solid w-250px ps-13 searchNumber" placeholder="Cari User Group" />
                        </div>
                    </div>
                    <div class="card-toolbar">

                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                            <a href="/user-group" type="button" class="btn btn-primary">Tambah User Group</a>
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



        </div>
        <!--end::Container-->
    </div>
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
    const userGroup = <?php echo Illuminate\Support\Js::from($userGroup) ?>;
    let app = new Vue({
        el: '#app',
        data: {
            userGroup,

            loading: false,
        },
        methods: {
            // 
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