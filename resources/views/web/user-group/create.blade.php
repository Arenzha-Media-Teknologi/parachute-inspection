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
                        <a href="/group" class="text-muted text-hover-primary">User Group</a>
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
    <div id="app">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">

                <div class="card-body pt-4">
                    <form @submit.prevent="onSubmit" enctype="multipart/form-data">
                        <div class="card">

                            <div class="card-header">
                                <div class="card-title">
                                    <h3>Tambah Grup</h3>
                                </div>
                            </div>

                            <div class="card-body pt-0">
                                <div class="row align-items-center my-10">
                                    <div class="col-md-2">
                                        <label class="required form-label fs-7">Nama:</label>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" v-model="model.name" class="form-control form-control-sm" placeholder="Masukkan nama grup" />
                                    </div>
                                </div>
                                <table class="table table-hover table-rounded table-striped border gy-7 gs-7">
                                    <?php $colspan = 5 ?>
                                    <thead class="bg-light-danger">
                                        <tr class="text-start text-gray-700 fw-bolder fs-7 text-uppercase gs-0 align-middle">
                                            <th class="min-w-100px ps-3" rowspan="2">Nama Akses</th>
                                            <th colspan="{{ $colspan - 1 }}" class="text-center">Hak Akses</th>
                                        </tr>
                                        <tr class="text-start text-gray-700 fw-bolder fs-7 text-uppercase gs-0">
                                            <th class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-check-custom form-check-sm">
                                                        <input v-model="model.checkedAll.view" class="form-check-input" type="checkbox" id="checkedAllView" />
                                                        <label class="form-check-label" for="checkedAllView">
                                                            Lihat
                                                        </label>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-check-custom form-check-sm">
                                                        <input v-model="model.checkedAll.add" class="form-check-input" type="checkbox" id="checkedAllAdd" />
                                                        <label class="form-check-label" for="checkedAllAdd">
                                                            Tambah
                                                        </label>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-check-custom form-check-sm">
                                                        <input v-model="model.checkedAll.edit" class="form-check-input" type="checkbox" id="checkedAllEdit" />
                                                        <label class="form-check-label" for="checkedAllEdit">
                                                            Ubah
                                                        </label>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-check-custom form-check-sm">
                                                        <input v-model="model.checkedAll.delete" class="form-check-input" type="checkbox" id="checkedAllDelete" />
                                                        <label class="form-check-label" for="checkedAllDelete">
                                                            Hapus
                                                        </label>
                                                    </div>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>

                                    <?php $tab = html_entity_decode('&nbsp;&nbsp;&nbsp;&nbsp;'); ?>
                                    <tbody class="fw-bold text-gray-600">
                                        @foreach($permissions as $permission)
                                        <tr>
                                            <td colspan="{{ $colspan }}" class="ps-3 py-5" style="background-color: #fafafa;">
                                                <span class="fw-bold fs-6 text-gray-800">{{ $permission['header'] }}
                                                </span>
                                            </td>
                                        </tr>
                                        @foreach($permission['subheaders'] as $subheader)
                                        <tr>
                                            @if($subheader['type'] == 'header_sub')
                                            <td class="ps-3 py-5">
                                                <span class="text-gray-700"><i class="bi bi-caret-right-fill"></i> <b> {{ $subheader['name'] }}</b></span>
                                            </td>
                                            @else
                                            <td class="ps-3 py-5">
                                                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<span class="text-gray-700"><i class="bi bi-caret-right"></i> {{ $subheader['name'] }}</span>
                                            </td>
                                            @endif
                                            <td class="text-center">
                                                @if(in_array('view', $subheader['items']))
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-check-custom form-check-sm">
                                                        <input v-model="model.permissions" class="form-check-input" type="checkbox" value="view_{{ $subheader['value'] }}" id="{{ $subheader['value'] }}ViewPermissionCheck" />
                                                        <label class="form-check-label" for="{{ $subheader['value'] }}ViewPermissionCheck">

                                                        </label>
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(in_array('add', $subheader['items']))
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-check-custom form-check-sm">
                                                        <input v-model="model.permissions" class="form-check-input" type="checkbox" value="add_{{ $subheader['value'] }}" id="{{ $subheader['value'] }}AddPermissionCheck" />
                                                        <label class="form-check-label" for="{{ $subheader['value'] }}AddPermissionCheck">

                                                        </label>
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(in_array('edit', $subheader['items']))
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-check-custom form-check-sm">
                                                        <input v-model="model.permissions" class="form-check-input" type="checkbox" value="edit_{{ $subheader['value'] }}" id="{{ $subheader['value'] }}EditPermissionCheck" />
                                                        <label class="form-check-label" for="{{ $subheader['value'] }}EditPermissionCheck">
                                                        </label>
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(in_array('delete', $subheader['items']))
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-check-custom form-check-sm">
                                                        <input v-model="model.permissions" class="form-check-input" type="checkbox" value="delete_{{ $subheader['value'] }}" id="{{ $subheader['value'] }}DeletePermissionCheck" />
                                                        <label class="form-check-label" for="{{ $subheader['value'] }}DeletePermissionCheck">

                                                        </label>
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <a href="/group" type="button" class="btn btn-secondary me-5 mb-4">Kembali</a>
                                <button type="submit" class="btn btn-success me-5 mb-4" :data-kt-indicator="loading ? 'on' : null" :disabled="loading">
                                    <span class="indicator-label">Simpan</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
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
    const permissions = <?php echo Illuminate\Support\Js::from($permissions) ?>;
    let app = new Vue({
        el: '#app',
        data() {
            return {
                permissions,
                model: {
                    name: '',
                    permissions: [],
                    checkedAll: {
                        view: false,
                        add: false,
                        edit: false,
                        delete: false,
                    }
                },
                loading: false,
            }
        },
        computed: {
            onlyPermissions() {
                return this.permissions.map(permission => {
                    const items = permission.subheaders.map(subheader => {
                        const mergedItems = subheader.items.map(item => `${item}_${subheader.value}`);
                        return mergedItems;
                    }).flat();
                    return items;
                }).flat();
            }
        },
        methods: {
            typeOnlyPermissions(method = 'view') {
                return this.onlyPermissions.filter(permission => {
                    const splittedPermission = permission.split('_');
                    const [type] = splittedPermission;
                    if (type == method) return true;
                    return false;
                });
            },
            async onSubmit() {
                let self = this;
                try {
                    const {
                        name,
                        permissions
                    } = self.model;
                    self.loading = true;
                    const response = await axios.post('/group', {
                        name,
                        permissions: JSON.stringify(permissions),
                    });
                    if (response) {
                        console.log(response)
                        let message = response?.data?.message;
                        if (!message) {
                            message = 'User Group berhasil disimpan'
                        }
                        toastr.success(message);
                        const toastrTimeOut = setTimeout(redirect, 500);

                        function redirect() {
                            window.location.href = '/group';
                        }

                    }
                } catch (error) {
                    let message = error?.response?.data?.message;
                    if (!message) {
                        message = 'Something wrong...'
                    }
                    toastr.error(message);
                } finally {
                    self.loading = false;
                }
            },
        },
        watch: {
            'model.checkedAll.view': function(isCheckedAllView) {
                const self = this;
                const type = 'view';
                const methodOnlyPermissions = self.typeOnlyPermissions(type);
                if (isCheckedAllView) {
                    const newPermissions = [
                        ...self.model.permissions,
                        ...methodOnlyPermissions,
                    ];
                    self.model.permissions = newPermissions;
                } else {
                    self.model.permissions = self.model.permissions.filter(permission => !methodOnlyPermissions.includes(permission));
                }
            },
            'model.checkedAll.add': function(isCheckedAllView) {
                const self = this;
                const type = 'add';
                const methodOnlyPermissions = self.typeOnlyPermissions(type);
                if (isCheckedAllView) {
                    const newPermissions = [
                        ...self.model.permissions,
                        ...methodOnlyPermissions,
                    ];
                    self.model.permissions = newPermissions;
                } else {
                    self.model.permissions = self.model.permissions.filter(permission => !methodOnlyPermissions.includes(permission));
                }
            },
            'model.checkedAll.edit': function(isCheckedAllView) {
                const self = this;
                const type = 'edit';
                const methodOnlyPermissions = self.typeOnlyPermissions(type);
                if (isCheckedAllView) {
                    const newPermissions = [
                        ...self.model.permissions,
                        ...methodOnlyPermissions,
                    ];
                    self.model.permissions = newPermissions;
                } else {
                    self.model.permissions = self.model.permissions.filter(permission => !methodOnlyPermissions.includes(permission));
                }
            },
            'model.checkedAll.delete': function(isCheckedAllView) {
                const self = this;
                const type = 'delete';
                const methodOnlyPermissions = self.typeOnlyPermissions(type);
                if (isCheckedAllView) {
                    const newPermissions = [
                        ...self.model.permissions,
                        ...methodOnlyPermissions,
                    ];
                    self.model.permissions = newPermissions;
                } else {
                    self.model.permissions = self.model.permissions.filter(permission => !methodOnlyPermissions.includes(permission));
                }
            },
        }

    })
</script>
@endsection