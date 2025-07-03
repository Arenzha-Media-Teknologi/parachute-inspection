@extends('web.layouts.app')

@section('title', 'Pemeriksaan Parasut')

@section('prehead')
@endsection


@section('content')
<style scoped>
    .custom-checkbox {
        width: 50px;
        height: 35px;
        cursor: pointer;
        accent-color: green;
    }

    .custom-checkbox:checked {
        background-color: green;
        border-color: green;
    }
</style>

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
                                                <label class="required fs-6 fw-semibold mb-2">Diperiksa Oleh (Nama Petugas)</label>
                                                <input type="text" class="form-control" placeholder="" v-model="parachuteDetail.person_in_charge" />
                                            </div>
                                            <div class="mb-7">
                                                <label class="required fs-6 fw-semibold mb-2">Diperbaiki Oleh (Nama Petugas)</label>
                                                <input type="text" class="form-control" placeholder="" v-model="parachuteDetail.repaired_by" />
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
                                                    <th class="border-0" style="width: 15%">Waktu Periksa</th>
                                                    <th class="border-0" style="width: 40%">Keterangan</th>
                                                    <th class="border-0" style="width: 30%">Gambar</th>
                                                    <th class="text-center border-0" style="width: 10%">Perbaikan</th>
                                                    <th class="text-center border-0" style="width: 5%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody v-if="detailItems.length">
                                                <tr v-for="(detail, idx) in detailItems" :key="detail.id">
                                                    <td class="border-0 align-top" style="width: 15%">
                                                        <input type="datetime-local" class="form-control form-control-sm" :value="formatDateForInput(detail.created)" @input="detail.created = $event.target.value" />
                                                    </td>
                                                    <td class="border-0 align-top" style="width: 40%">
                                                        <!-- <textarea v-if="detail.file" v-model="detail.description" class="form-control form-control-sm" rows="6" required></textarea>
                                                        <textarea v-else v-model="detail.description" class="form-control form-control-sm" rows="1" required></textarea> -->
                                                        <div class="mb-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <label class="form-label mb-0">Utama</label>
                                                                <button type="button" class="btn btn-primary btn-sm text-white" @click="addMainItem(idx)"> <i class="fa fa-plus"></i>
                                                                </button>
                                                            </div>
                                                            <div v-for="(m, mi) in detail.mainItems" :key="`main-${idx}-${mi}`" class="d-flex gap-2 mb-2">
                                                                <input type="text" class="form-control form-control-sm" v-model="detail.mainItems[mi].description" />
                                                                <button type="button" class="btn btn-sm btn-light" @click="removeMainItem(idx, mi)"> <i class="fas fa-fw fa-trash"></i> </button>
                                                            </div>

                                                            <div class="d-flex justify-content-between align-items-center mb-1 mt-3">
                                                                <label class="form-label mb-0">Cadangan</label>
                                                                <button type="button" class="btn btn-primary btn-sm text-white" @click="addSecondItem(idx)"> <i class="fa fa-plus"></i> </button>
                                                            </div>
                                                            <div v-for="(s, si) in detail.secondItems" :key="`second-${idx}-${si}`" class="d-flex gap-2 mb-2">
                                                                <input type="text" class="form-control form-control-sm" v-model="detail.secondItems[si].description" />
                                                                <button type="button" class="btn btn-sm btn-light" @click="removeSecondItem(idx, si)"> <i class="fas fa-fw fa-trash"></i> </button>
                                                            </div>
                                                        </div>

                                                    </td>
                                                    <td class="border-0" style="width: 30%"> <input type="file" accept="image/*" class="form-control form-control-sm" v-on:change="handleFileUploadDetail($event, idx)">
                                                        <div v-if="detail.file || detail.previewUrl" class="mt-2">
                                                            <img v-if="detail.previewUrl" :src="detail.previewUrl" alt="Preview" style="max-width: 200px; max-height: 100px;" />
                                                            <img v-else :src="`/storage/${detail.file}`" alt="Preview" style="max-width: 200px; max-height: 100px;" />
                                                        </div>
                                                    </td>
                                                    <td class="border-0 align-top text-center" style="width: 10%">
                                                        <input type="checkbox" class="form-check-input custom-checkbox" v-model="detail.status" />
                                                        <div v-if="detail.status">
                                                            <p>
                                                                <label for="status_date"><b>Waktu Selesai : </b></label>
                                                                <input type="datetime-local" class="form-control form-control-sm" :value="formatDateStatusInput(detail.status_date)" @input="detail.status_date = $event.target.value" />
                                                            </p>
                                                        </div>
                                                    </td>
                                                    <td class="border-0 align-top" style="width: 5%">
                                                        <button type="button" class="btn btn-sm btn-light" @click="removeDetailItem(idx)"><i class="fas fa-fw fa-trash"></i></button>
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
                                        <!-- <button type="button" class="btn btn-light">Batal</button> -->
                                        <a href="/parachute-inspection" type="button" class="btn btn-light">Batal</a>
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
            repairman: '',

            category: '',
            type: '',
            partNumber: '',
            serialNumber: '',
            parachuteDetail: [],

            parachuteItems: [],
            detailItems: [],

            mainItems: [],
            secondItems: [],

            loading: false,
        },
        mounted() {
            this.parachuteDetail = this.parachuteInspection;
            console.log('parachuteDetail:', this.parachuteDetail);
            if (this.parachuteDetail.items.length > 0) {
                this.parachuteDetail.items.forEach(item => {
                    const detailItem = {
                        id: item.id || null,
                        // created: item.created_at || "",
                        created: item.date || "",
                        description: item.description || "",
                        file: item.image_url || "",
                        previewUrl: null,
                        status: item.status === 1 || item.status === '1',
                        status_date: item.status_date || "",
                        mainItems: [],
                        secondItems: [],
                    };

                    if (Array.isArray(item.item_descriptions)) {
                        item.item_descriptions.forEach(desc => {
                            const entry = {
                                id: desc.id || null,
                                type: desc.type || '',
                                description: desc.description || ''
                            };
                            if (entry.type === 'utama') {
                                detailItem.mainItems.push(entry);
                            } else if (entry.type === 'cadangan') {
                                detailItem.secondItems.push(entry);
                            }
                        });
                    }
                    this.detailItems.push(detailItem);
                });
            }
            console.log('parachuteDetail.items :', this.parachuteDetail.items);
        },
        methods: {
            addMainItem(idx) {
                this.detailItems[idx].mainItems.push({
                    type: 'utama',
                    description: ''
                });
            },
            removeMainItem(idx, mi) {
                this.detailItems[idx].mainItems.splice(mi, 1);
            },

            addSecondItem(idx) {
                this.detailItems[idx].secondItems.push({
                    type: 'cadangan',
                    description: ''
                });
            },
            removeSecondItem(idx, si) {
                this.detailItems[idx].secondItems.splice(si, 1);
            },


            formatDateForInput(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                const pad = (n) => n.toString().padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            },
            formatDateStatusInput(dateString) {
                if (!dateString) return '';
                if (dateString instanceof Date) {
                    const date = dateString;
                    const pad = (n) => n.toString().padStart(2, '0');
                    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
                }
                if (typeof dateString === 'string') {
                    const iso = dateString.replace(' ', 'T');
                    const date = new Date(iso);
                    if (isNaN(date.getTime())) return '';
                    const pad = (n) => n.toString().padStart(2, '0');
                    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
                }
                return '';
            },

            addDetailItems: function() {
                this.detailItems.push({
                    "id": null,
                    "created": "",
                    "description": "",
                    "file": "",
                    "previewUrl": null,
                    mainItems: [],
                    secondItems: [],
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
                } else if (this.parachuteDetail['person_in_charge'] == '' || this.parachuteDetail['repaired_by'] == '') {
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
                console.log('sendDataDetail:', id);
                let formData = new FormData();
                formData.append('code', this.parachuteDetail['number']);
                formData.append('date', this.parachuteDetail['date']);
                formData.append('activity', this.parachuteDetail['activity_name']);
                formData.append('checker', this.parachuteDetail['person_in_charge']);
                formData.append('repairman', this.parachuteDetail['repaired_by']);

                formData.append('parachute_id', this.parachuteDetail['parachute_id']);
                let idx = 0;
                this.detailItems.forEach(detail => {
                    formData.append(`items[${idx}][id]`, detail.id || '');
                    if (detail.created && detail.created !== '0' && detail.created !== 'null') {
                        formData.append(`items[${idx}][created]`, detail.created);
                    }
                    formData.append(`items[${idx}][description]`, detail.description || '');
                    formData.append(`items[${idx}][status]`, detail.status ? 1 : 0);
                    if (detail.status_date && detail.status_date !== '0' && detail.status_date !== 'null') {
                        formData.append(`items[${idx}][status_date]`, detail.status_date);
                    }
                    if (detail.file instanceof File) {
                        formData.append(`items[${idx}][file]`, detail.file);
                    }
                    idx++;
                });

                this.detailItems.forEach(detail => {
                    detail.mainItems.forEach(m => {
                        formData.append(`items[${idx}][parent_item_id]`, detail.id);
                        formData.append(`items[${idx}][type]`, 'utama');
                        formData.append(`items[${idx}][description]`, m.description);
                        idx++;
                    });
                });
                this.detailItems.forEach(detail => {
                    detail.secondItems.forEach(s => {
                        formData.append(`items[${idx}][parent_item_id]`, detail.id);
                        formData.append(`items[${idx}][type]`, 'cadangan');
                        formData.append(`items[${idx}][description]`, s.description);
                        idx++;
                    });
                });

                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                // vm.loading = false;
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