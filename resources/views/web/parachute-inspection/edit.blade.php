@extends('web.layouts.app')

@section('title', 'Pemeriksaan Parasut')

@section('prehead')
@endsection


@section('content')

@php
$user = auth()->user();
$userLoginPermissions = [];
if (request()->session()->has('userLoginPermissions')) {
$userLoginPermissions = request()->session()->get('userLoginPermissions');
}
@endphp

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <div data-kt-swapper="true" data-kt-swapper-mode="prepend" data-kt-swapper-parent="{default: '#kt_content_container', 'lg': '#kt_toolbar_container'}" class="page-title d-flex align-items-center me-3 flex-wrap lh-1">
                <h1 class="d-flex align-items-center text-gray-900 fw-bold my-1 fs-3">Pemeriksaan Parasut</h1>
                <span class="h-20px border-gray-200 border-start mx-4"></span>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="/" class="text-muted text-hover-primary">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-300 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="/parachute-inspection" class="text-muted text-hover-primary">Daftar Pemeriksaan Parasut</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-300 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-gray-900">Riwayat Pemeriksaan Parasut</li>
                </ul>
            </div>
            <div class="d-flex align-items-center py-1">
            </div>
        </div>
    </div>
    <div class="post d-flex flex-column-fluid" id="app">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="p-6 m-3">
                    <div class="text-center" style="background-color: turquoise;">
                        <span class="text-white pt-3 pb-3" style="font-size: 35px; font-weight: bold;">Riwayat Pemeriksaan Parasut</span>
                    </div>
                </div>

                <div class="p-6 m-3">
                    <form class="form" @submit.prevent="submitFormDetail(parachuteDetail.id)">
                        <div class="modal-content">
                            <div class="card border-0 mb-3">
                                <h2>Informasi Parasut</h2>
                                <hr>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-7">
                                                <label class="required fs-6 fw-semibold mb-2">Kode Pemeriksaan</label>
                                                <input type="text" class="form-control" placeholder="" v-model="parachuteDetail.number" disabled />
                                            </div>
                                            <div class="mb-7">
                                                <label class="required fs-6 fw-semibold mb-2">Tanggal Pemeriksaan</label>
                                                <input type="date" class="form-control" placeholder="" v-model="parachuteDetail.date" />
                                            </div>
                                            <div class="mb-7">
                                                <label class="required fs-6 fw-semibold mb-2">Diperiksa Oleh</label>
                                                <input type="text" class="form-control" placeholder="" v-model="parachuteDetail.person_in_charge" />
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-7">
                                                <label class="required fs-6 fw-semibold mb-2">Jenis Parasut</label>
                                                <input type="text" class="form-control" placeholder="" :value="parachuteDetail?.parachute?.category" disabled />
                                            </div>
                                            <div class="mb-7">
                                                <label class="required fs-6 fw-semibold mb-2">Tipe Parasut</label>
                                                <input type="text" class="form-control" placeholder="" :value="parachuteDetail?.parachute?.type" disabled />
                                            </div>
                                            <div class="mb-7">
                                                <label class="required fs-6 fw-semibold mb-2">Part Number</label>
                                                <input type="text" class="form-control" placeholder="" :value="parachuteDetail?.parachute?.part_number" disabled />
                                            </div>
                                            <div class="mb-7">
                                                <label class="required fs-6 fw-semibold mb-2">Serial Number</label>
                                                <input type="text" class="form-control" placeholder="" :value="parachuteDetail?.parachute?.serial_number" disabled />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="mb-5">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h2 class="mb-0">Daftar Hasil Pemeriksaan Parasut</h2>
                                            <a class="btn btn-primary btn-sm text-white" @click="addDetailItems">
                                                <i class="fa fa-plus"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <table class="table table-sm table-bordered align-middle" style="width: 100%">
                                            <thead style="background-color: lightgray;">
                                                <tr>
                                                    <th class="border-0" style="width: 20%">Waktu Periksa</th>
                                                    <th class="border-0" style="width: 40%">Keterangan</th>
                                                    <th class="border-0" style="width: 35%">Gambar</th>
                                                    <th class="text-center border-0" style="width: 5%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody v-if="detailItems.length">
                                                <tr v-for="(item,index) in detailItems">
                                                    <td class="border-0 align-top" style="width: 20%">
                                                        <input type="datetime-local" class="form-control form-control-sm" :value="formatDateForInput(item.created)" @input="item.created = $event.target.value" />
                                                    </td>
                                                    <td class="border-0 align-top" style="width: 40%">
                                                        <textarea v-if="item.file" v-model="item.description" class="form-control form-control-sm" rows="6" required></textarea>
                                                        <textarea v-else v-model="item.description" class="form-control form-control-sm" rows="1" required></textarea>
                                                    </td>
                                                    <td class="border-0" style="width: 35%"> <input type="file" accept="image/*" class="form-control form-control-sm" v-on:change="handleFileUploadDetail($event, index)">
                                                        <div v-if="item.file || item.previewUrl" class="mt-2">
                                                            <img v-if="item.previewUrl" :src="item.previewUrl" alt="Preview" style="max-width: 200px; max-height: 100px;" />
                                                            <img v-else :src="`/storage/${item.file}`" alt="Preview" style="max-width: 200px; max-height: 100px;" />
                                                        </div>
                                                    </td>
                                                    <td class="border-0 align-top" style="width: 5%">
                                                        <button type="button" class="btn btn-sm btn-light" @click="removeDetailItem(index)"><i class="fas fa-fw fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tbody v-else>
                                                <tr>
                                                    <td colspan="3" class="border-0 text-center text-muted">Belum ada item pemeriksaan.</td>
                                                </tr>
                                                <tr class="border-0" style="visibility: hidden; height: 0;">
                                                    <td class="border-0" style="width: 20%"><input type="datetime" class="form-control form-control-sm" style="width: 100%;"></td>
                                                    <td class="border-0" style="width: 40%"><input type="text" class="form-control form-control-sm" style="width: 100%;"></td>
                                                    <td class="border-0" style="width: 35%"><input type="file" class="form-control form-control-sm" style="width: 100%;"></td>
                                                    <td class="border-0" style="width: 5%"><button type="button" class="btn btn-sm btn-light"><i class="fas fa-fw fa-trash"></i></button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-6 text-end">
                                        <button type="button" class="btn btn-light">Batal</button>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-success" :data-kt-indicator="loading ? 'on' : null" :disabled="loading">
                                            <span class="indicator-label">Simpan</span>
                                            <span class="indicator-progress">Please wait...
                                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
    <!--end::Container-->
</div>
<!--end::Post-->

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
    const parachuteInspection = <?php echo Illuminate\Support\Js::from($parachute_inspection) ?>;
    const parachute = <?php echo Illuminate\Support\Js::from($parachute) ?>;
    let app = new Vue({
        el: '#app',
        data: {
            parachuteInspection,
            parachute,
            code: '',
            date: '',
            activity: '',
            checker: '',

            category: '',
            type: '',
            partNumber: '',
            serialNumber: '',
            parachuteDetail: [],

            parachuteItems: [],
            detailItems: [],

            loading: false,
        },
        mounted() {
            this.parachuteDetail = this.parachuteInspection;
            console.log('parachuteDetail:', this.parachuteDetail);
            if (this.parachuteDetail.items.length > 0) {
                this.parachuteDetail.items.forEach(item => {
                    this.detailItems.push({
                        id: item.id || null,
                        created: item.created_at || "",
                        description: item.description || "",
                        file: item.image_url || "",
                        previewUrl: null,
                    });
                });
            }
            console.log('parachuteDetail.items :', this.parachuteDetail.items);
        },
        methods: {
            formatDateForInput(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                const pad = (n) => n.toString().padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            },
            addDetailItems: function() {
                this.detailItems.push({
                    "id": null,
                    "created": "",
                    "description": "",
                    "file": "",
                    "previewUrl": null,
                });
            },
            removeDetailItem: function(index) {
                this.detailItems.splice(index, 1);
            },
            handleFileUploadDetail(event, index) {
                console.log(event);
                const file = event.target.files[0];
                if (!file) return;
                const maxSize = 2 * 1024 * 1024; // 2MB
                const allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (file.size > maxSize) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Ukuran File Terlalu Besar',
                        text: 'Ukuran file maksimal 2MB.',
                    });
                    event.target.value = null;
                    return;
                }
                if (!allowedImageTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tipe File Tidak Valid',
                        text: 'Hanya file gambar (JPG, PNG, GIF, WEBP) yang diperbolehkan.',
                    });
                    event.target.value = null;
                    return;
                }
                const reader = new FileReader();
                if (this.detailItems[index]) {
                    this.detailItems[index].file = file;
                    reader.onload = (e) => {
                        this.detailItems[index].previewUrl = e.target.result;
                    };
                } else {
                    this.$set(this.detailItems, index, {
                        id: null,
                        created: '',
                        description: '',
                        file: file,
                        previewUrl: ''
                    });
                    console.error(`detailItems[${index}] tidak ditemukan!`);
                }
                reader.readAsDataURL(file);
            },

            submitFormDetail: function(id) {
                console.log('submitFormDetail_id:', id);
                // return;
                if (this.parachuteDetail['date'] == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Tanggal Pemeriksaan tidak boleh kosong.',
                        'warning'
                    );
                    // } else if (this.parachuteDetail['activity_name'] == '') {
                    //     Swal.fire(
                    //         'Terjadi Kesalahan!',
                    //         'Nama Kegiatan tidak boleh kosong.',
                    //         'warning'
                    //     )
                } else if (this.parachuteDetail['person_in_charge'] == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Nama Petugas tidak boleh kosong.',
                        'warning'
                    )
                } else {
                    this.sendDataDetail(id);
                    this.loading = true;
                }
            },
            sendDataDetail: function(id) {
                let vm = this;
                vm.loading = true;
                let formData = new FormData();
                formData.append('code', this.parachuteDetail['number']);
                formData.append('date', this.parachuteDetail['date']);
                formData.append('activity', this.parachuteDetail['activity_name']);
                formData.append('checker', this.parachuteDetail['person_in_charge']);
                formData.append('parachute_id', this.parachuteDetail['parachute_id']);
                // this.parachuteItems.forEach((item, index) => {
                //     if (item.id) {
                //         formData.append(`items[${index}][id]`, item.id);
                //     }
                //     if (item.file) {
                //         formData.append(`items[${index}][created]`, item.created);
                //         formData.append(`items[${index}][description]`, item.description);
                //         formData.append(`items[${index}][file]`, item.file);
                //     }
                // });            
                console.log('sendDataDetail:', id);
                this.parachuteItems = this.detailItems;
                this.parachuteItems.forEach((item, index) => {
                    if (item.id) {
                        formData.append(`items[${index}][id]`, item.id);
                    }
                    formData.append(`items[${index}][created]`, item.created || '');
                    formData.append(`items[${index}][description]`, item.description || '');
                    if (item.file instanceof File) {
                        formData.append(`items[${index}][file]`, item.file);
                    }
                });

                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                // return;
                axios.post('/parachute-inspection/' + id, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
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
                            window.location.href = '/parachute-inspection';
                        }, 1000);
                    })
                    .catch(function(error) {
                        vm.loading = false;
                        console.log(error);
                        let message = error?.response?.data?.message;
                        if (!message) {
                            message = 'Terdapat kesalahan..'
                        }
                        toastr.error(message);
                    });
            },

        },
    })
</script>

@endsection