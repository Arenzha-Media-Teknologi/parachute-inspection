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

$permission = json_decode(Auth::user()->user_groups->permissions);
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
                        <a href="/parachute-inspection" class="text-muted text-hover-primary">Pemeriksaan Parasut</a>
                    </li>

                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-300 w-5px h-2px"></span>
                    </li>


                    <li class="breadcrumb-item text-gray-900">Daftar Pemeriksaan Parasut</li>

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
                        <span class="text-white pt-3 pb-3" style="font-size: 35px; font-weight: bold;">Daftar Pemeriksaan</span>
                    </div>
                </div>
                <div class="p-6 m-3 col-4">
                    <div class="text-center" style="background-color: blue;">
                        <h1 class="pt-3 pb-3" style="color: yellow">Total Pemeriksaan :
                            <span style="color: orange; font-size: 30px; font-weight: bold;"> @{{ parachuteInspection.length }} </span>
                        </h1>
                    </div>
                </div>

                <div class="card-header border-0 pt-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center w-100 gap-4">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="position-relative">
                                <i class="ki-outline ki-magnifier fs-3 position-absolute top-50 start-0 translate-middle-y ms-4"></i>
                                <input type="text" class="form-control form-control-solid ps-13 searchNumber" style="width: 220px;" placeholder="Cari Data Parasut" />
                            </div>

                            <input type="date" class="form-control" v-model="date_start" style="width: 160px;" />
                            <input type="date" class="form-control" v-model="date_end" style="width: 160px;" />
                            <select v-model="parachuteType" class="form-select" style="width: 180px;">
                                <option value="">-- Tipe Parasut --</option>
                                <option v-for="item in parachute" :value="item.type">@{{ item.type }}</option>
                            </select>
                            <button class="btn btn-secondary" @click="applyFilter"><i class="fas fa-filter fs-4"></i>&nbsp; Filter</button>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            @if(in_array("add_parachute_check", $permission))
                            <button class="btn btn-primary" @click="onModalOpen" data-bs-toggle="modal" data-bs-target="#kt_modal_create"> Tambah Periksa </button>
                            @endif

                            @if(in_array("view_report_parachute_check", $permission))
                            <button class="btn btn-success" @click="openReportModal"> Laporan </button>
                            <!-- <a class="dropdown-item" href="/parachute-inspection/report" target="_blank">
                                <i class="fas fa-calendar-day me-2 text-success"></i> Laporan Pemeriksaan
                            </a> -->
                            <!-- <a class="dropdown-item" href="/parachute-inspection/report-doc" target="_blank">
                                <i class="fas fa-calendar-alt me-2 text-danger"></i> Lampiran Pemeriksaan
                            </a> -->
                            <!-- <div class="dropdown">
                                <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"> Laporan </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item" @click="openReport">
                                            <i class="fas fa-calendar-day me-2 text-success"></i> Laporan Pemeriksaan
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" @click="openAttachment">
                                            <i class="fas fa-calendar-alt me-2 text-danger"></i> Lampiran Pemeriksaan
                                        </button>
                                    </li>
                                </ul>
                            </div> -->
                            @endif
                        </div>
                    </div>
                    <div class="d-flex justify-content-end align-items-center d-none mt-3" data-kt-customer-table-toolbar="selected">
                        <div class="fw-bold me-5">
                            <span class="me-2" data-kt-customer-table-select="selected_count"></span>Selected
                        </div>
                        <button class="btn btn-danger" data-kt-customer-table-select="delete_selected">Delete Selected</button>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <table class="table table-hover table-rounded table-striped border gy-7 gs-7" id="parachute-table">
                        <thead>
                            <tr>
                                <th class="text-center fw-bold fs-5">Tgl.Pemeriksaan</th>
                                <th class="text-center  fw-bold fs-5">Kode Pemeriksaan</th>
                                <th class="text-center  fw-bold fs-5">Jenis Parasut</th>
                                <th class="text-center  fw-bold fs-5">Tipe Parasut</th>
                                <th class="text-center  fw-bold fs-5">Part Number</th>
                                <th class="text-center  fw-bold fs-5">Serial Number</th>
                                <th class="text-center  fw-bold fs-5">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" tabindex="-1" id="kt_modal_create">
            <div class="modal-dialog modal-dialog-centered mw-650px modal-fullscreen-sm-down">
                <form class="form" @submit.prevent="submitForm">
                    <div class="modal-content">
                        <div class="modal-header" id="kt_modal_add_customer_header">
                            <h2 class="fw-bold">Tambah Data Periksa</h2>
                        </div>
                        <div class="modal-body py-10 px-lg-17">
                            <div class="scroll-y me-n7 pe-7" id="kt_modal_add_customer_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_customer_header" data-kt-scroll-wrappers="#kt_modal_add_customer_scroll" data-kt-scroll-offset="300px">
                                <div class="fv-row mb-7">
                                    <label class="required fs-6 fw-semibold mb-2">Kode Pemeriksaan </label>
                                    <input type="text" class="form-control form-control" placeholder="" v-model="code" disabled />
                                </div>
                                <div class="fv-row mb-7">
                                    <label class="required fs-6 fw-semibold mb-2">Tanggal Pemeriksaan </label>
                                    <input type="date" class="form-control form-control" placeholder="" v-model="date" />
                                </div>
                                <div class="fv-row mb-7">
                                    <label class="required fs-6 fw-semibold mb-2">Nama Kegiatan</label>
                                    <input type="text" class="form-control form-control" placeholder="" v-model="activity" />
                                </div>
                                <div class="fv-row mb-7">
                                    <label class="required fs-6 fw-semibold mb-2">Nama Petugas</label>
                                    <input type="text" class="form-control form-control" placeholder="" v-model="checker" />
                                </div>

                                <div class="fv-row mb-7">
                                    <label class="required fs-6 fw-semibold mb-2">Data Parasut</label>
                                    <select v-model="parachuteSelect" class="form-select" @change="onParachuteChange">
                                        <option disabled value="">-- Pilih Data Parasut --</option>
                                        <option v-for="item in parachute" :value="item.id">@{{ item.id }} - @{{ item.serial_number }}</option>
                                    </select>
                                </div>
                                <!-- Tampilkan detail setelah data parasut dipilih -->
                                <div v-if="parachuteSelect">
                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Jenis Parasut</label>
                                        <input type="text" class="form-control form-control" placeholder="" v-model="category" disabled />
                                    </div>
                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Tipe Parasut</label>
                                        <input type="text" class="form-control form-control" placeholder="" v-model="type" disabled />
                                    </div>
                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Part Number</label>
                                        <input type="text" class="form-control form-control" placeholder="" v-model="partNumber" disabled />
                                    </div>
                                    <div class="fv-row mb-7">
                                        <label class="required fs-6 fw-semibold mb-2">Serial Number </label>
                                        <input type="text" class="form-control form-control" placeholder="" v-model="serialNumber" disabled />
                                    </div>
                                </div>

                                <div class="fv-row mb-7">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h2 class="mb-0">Hasil Pemeriksaan</h2>
                                        <a class="btn btn-primary btn-sm text-white" @click="addItems">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    </div>
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th class="border-0">Upload File (2MB)</th>
                                                <!-- <th class="border-0"></th> -->
                                                <th class="border-0">Keterangan</th>
                                                <th class="text-center border-0">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody v-if="parachuteItems.length">
                                            <tr v-for="(item,index) in parachuteItems">
                                                <td class="border-0"> <input type="file" accept="image/*" class="form-control form-control-sm" v-on:change="handleFileUpload($event, index)">
                                                    <div v-if="item.previewUrl" class="mt-2">
                                                        <img :src="item.previewUrl" alt="Preview" style="max-width: 200px; max-height: 100px;" />
                                                    </div>
                                                </td>
                                                <!-- <td class="border-0">
                                                    <div v-if="item.previewUrl" class="mt-2">
                                                        <img :src="item.previewUrl" alt="Preview" style="max-width: 150px; max-height: 100px;" />
                                                    </div>
                                                </td> -->
                                                <td class="border-0 align-top">
                                                    <textarea v-if="item.previewUrl" v-model="item.description" class="form-control form-control-sm" rows="6" required></textarea>
                                                    <textarea v-else v-model="item.description" class="form-control form-control-sm" rows="1" required></textarea>
                                                </td>
                                                <!-- <td class="border-0"> <input type="text" v-model="item.description" class="form-control form-control-sm" required></td> -->
                                                <td class="border-0 align-top">
                                                    <button type="button" class="btn btn-sm btn-light" @click="removeItem(index)"><i class="fas fa-fw fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tbody v-else>
                                            <tr>
                                                <td colspan="3" class="border-0 text-center text-muted">Belum ada item pemeriksaan.</td>
                                            </tr>
                                            <tr class="border-0" style="visibility: hidden; height: 0;">
                                                <td class="border-0"><input type="file" class="form-control form-control-sm" style="width: 100%;"></td>
                                                <!-- <td class="border-0"><input type="file" class="form-control form-control-sm" style="width: 100%;"></td> -->
                                                <td class="border-0"><input type="text" class="form-control form-control-sm" style="width: 100%;"></td>
                                                <td class="border-0"><button type="button" class="btn btn-sm btn-light"><i class="fas fa-fw fa-trash"></i></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
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
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" tabindex="-1" id="kt_modal_detail">
            <div class="modal-dialog modal-dialog-centered mw-650px modal-fullscreen-sm-down">
                <form class="form" @submit.prevent="submitFormDetail(parachuteDetail.id)">
                    <div class="modal-content">
                        <div class="modal-header" id="kt_modal_add_customer_header">
                            <div class="card" style="width: 100%;">
                                <div class="p-6 m-3">
                                    <div class="text-center" style="background-color: turquoise;">
                                        <h2 class="text-white fw-bold pt-3 pb-3">Riwayat Pemeriksaan Parasut</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-body py-10 px-lg-17">
                            <div class="scroll-y me-n7 pe-7" id="kt_modal_add_customer_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_customer_header" data-kt-scroll-wrappers="#kt_modal_add_customer_scroll" data-kt-scroll-offset="300px">
                                <h2 class="mt-0 mb-3">Informasi Parasut</h2>
                                <div class="d-flex justify-content-between gap-5">
                                    <div class="col-6">
                                        <div class="fv-row mb-7">
                                            <label class="required fs-6 fw-semibold mb-2">Kode Pemeriksaan </label>
                                            <input type="text" class="form-control form-control" placeholder="" v-model="parachuteDetail.number" disabled />
                                        </div>
                                        <div class="fv-row mb-7">
                                            <label class="required fs-6 fw-semibold mb-2">Tanggal Pemeriksaan </label>
                                            <input type="date" class="form-control form-control" placeholder="" v-model="parachuteDetail.date" />
                                        </div>
                                        <!-- <div class="fv-row mb-7">
                                            <label class="required fs-6 fw-semibold mb-2">Nama Kegiatan</label>
                                            <input type="text" class="form-control form-control" placeholder="" v-model="parachuteDetail.activity_name" />
                                        </div> -->
                                        <div class="fv-row mb-7">
                                            <label class="required fs-6 fw-semibold mb-2">Diperiksa Oleh</label>
                                            <input type="text" class="form-control form-control" placeholder="" v-model="parachuteDetail.person_in_charge" />
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="fv-row mb-7">
                                            <label class="required fs-6 fw-semibold mb-2">Jenis Parasut</label>
                                            <input type="text" class="form-control form-control" placeholder="" :value="parachuteDetail?.parachute?.category" disabled />
                                        </div>
                                        <div class="fv-row mb-7">
                                            <label class="required fs-6 fw-semibold mb-2">Tipe Parasut</label>
                                            <input type="text" class="form-control form-control" placeholder="" :value="parachuteDetail?.parachute?.type" disabled />
                                        </div>
                                        <div class="fv-row mb-7">
                                            <label class="required fs-6 fw-semibold mb-2">Part Number</label>
                                            <input type="text" class="form-control form-control" placeholder="" :value="parachuteDetail?.parachute?.part_number" disabled />
                                        </div>
                                        <div class="fv-row mb-7">
                                            <label class="required fs-6 fw-semibold mb-2">Serial Number </label>
                                            <input type="text" class="form-control form-control" placeholder="" :value="parachuteDetail?.parachute?.serial_number" disabled />
                                        </div>
                                    </div>
                                </div>

                                <div class="fv-row mb-7">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h2 class="mb-0">Daftar Hasil Pemeriksaan Parasut</h2>
                                        <a class="btn btn-primary btn-sm text-white" @click="addDetailItems">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    </div>
                                    <!-- cek data -->
                                    <!-- <pre>@{{ detailItems }}</pre>-->
                                    <table class="table table-sm table-bordered align-middle" style="width: 100%">
                                        <thead>
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
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success" :data-kt-indicator="loading ? 'on' : null" :disabled="loading">
                                <span class="indicator-label">Simpan</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </div>
                </form>
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

        <!-- begin Modal -->
        <div class="modal fade" id="reportDateModal" tabindex="-1" aria-labelledby="reportDateModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reportDateModalLabel">Pilih Periode Laporan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <input type="date" v-model="reportDate" class="form-control" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" @click="submitReport">
                            <i class="fas fa-calendar-day text-success"></i> Laporan
                        </button>
                        <button type="button" class="btn btn-primary" @click="submitReportAttachment">
                            <i class="fas fa-calendar-alt text-danger"></i> Lampiran
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Modal -->

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
    window.parachuteReportPreviewUrl = "{{ route('parachute-inspection.reportPreview') }}";
    window.parachuteReportAttachmentPreviewUrl = "{{ route('parachute-inspection.reportAttachmentPreview') }}";
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

            parachuteSelect: '',
            parachuteItems: [],
            detailItems: [],

            date_start: '',
            date_end: '',
            parachuteType: '',
            reportDate: '',
            loading: false,
        },
        methods: {
            applyFilter() {
                const today = new Date().toISOString().split('T')[0];
                if (this.date_end && !this.date_start) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tanggal tidak lengkap',
                        text: 'Tanggal mulai harus diisi jika tanggal akhir diisi.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        this.date_start = today;
                        this.date_end = today;
                    });
                    return;
                }
                const start = new Date(this.date_start);
                const end = new Date(this.date_end);
                if (this.date_start && this.date_end && end < start) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tanggal tidak valid',
                        text: 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        this.date_start = today;
                        this.date_end = today;
                    });
                    return;
                }
                $('#parachute-table').DataTable().ajax.reload();
            },

            openReportModal() {
                this.reportDate = ''; // reset
                if (!this.date_start) {
                    alert('Tanggal mulai harus diisi');
                    return;
                }
                const modal = new bootstrap.Modal(document.getElementById('reportDateModal'));
                modal.show();
            },
            submitReport() {
                if (!this.reportDate) {
                    alert('Tanggal harus dipilih');
                    return;
                }
                const dateObj = new Date(this.reportDate);
                const year = dateObj.getFullYear();
                const month = dateObj.getMonth() + 1; // 1-12
                const romawiBulan = [
                    '', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'
                ];
                const bulanRomawi = romawiBulan[month];
                const modalEl = document.getElementById('reportDateModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                // let url = `${window.parachuteReportPreviewUrl}?date_start=${this.date_start}&bulan_romawi=${bulanRomawi}&tahun=${year}`;
                let url = `${window.parachuteReportPreviewUrl}?date_start=${this.date_start}&periode=${this.reportDate}`;
                if (this.date_end) url += `&date_end=${this.date_end}`;
                if (this.parachuteType) url += `&type=${encodeURIComponent(this.parachuteType)}`;
                window.open(url, '_blank');
            },
            submitReportAttachment() {
                if (!this.reportDate) {
                    alert('Tanggal harus dipilih');
                    return;
                }
                const dateObj = new Date(this.reportDate);
                const year = dateObj.getFullYear();
                const month = dateObj.getMonth() + 1; // 1-12
                const romawiBulan = [
                    '', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'
                ];
                const bulanRomawi = romawiBulan[month];
                const modalEl = document.getElementById('reportDateModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                // let url = `${window.parachuteReportAttachmentPreviewUrl}?date_start=${this.date_start}&bulan_romawi=${bulanRomawi}&tahun=${year}`;
                let url = `${window.parachuteReportAttachmentPreviewUrl}?date_start=${this.date_start}&periode=${this.reportDate}`;
                if (this.date_end) url += `&date_end=${this.date_end}`;
                if (this.parachuteType) url += `&type=${encodeURIComponent(this.parachuteType)}`;
                window.open(url, '_blank');
            },

            openReport() {
                if (!this.date_start) {
                    alert('Tanggal mulai harus diisi');
                    return;
                }
                let url = `${window.parachuteReportPreviewUrl}?date_start=${this.date_start}`;
                // let url = `/parachute-inspection/report/preview?date_start=${this.date_start}`;
                if (this.date_end) url += `&date_end=${this.date_end}`;
                if (this.parachuteType) url += `&type=${encodeURIComponent(this.parachuteType)}`;
                window.open(url, '_blank');
            },
            openAttachment() {
                if (!this.date_start) {
                    alert('Tanggal mulai harus diisi');
                    return;
                }
                let url = `${window.parachuteReportAttachmentPreviewUrl}?date_start=${this.date_start}`;
                // let url = `/parachute-inspection/report-attachment?date_start=${this.date_start}`;
                if (this.date_end) url += `&date_end=${this.date_end}`;
                if (this.parachuteType) url += `&type=${encodeURIComponent(this.parachuteType)}`;
                window.open(url, '_blank');
            },

            generateCode() {
                // const today = new Date();
                // const dd = String(today.getDate()).padStart(2, '0');
                // const mm = String(today.getMonth() + 1).padStart(2, '0');
                // const yyyy = today.getFullYear();
                // const dateStr = dd + mm + yyyy;
                // const todayInspections = this.parachuteInspection.filter(item => {
                //     const createdAt = new Date(item.created_at);
                //     return (
                //         createdAt.getDate() === today.getDate() &&
                //         createdAt.getMonth() === today.getMonth() &&
                //         createdAt.getFullYear() === today.getFullYear()
                //     );
                // });
                // const usedNumbers = todayInspections.map(item => {
                //     const parts = item.number.split('-');
                //     const numPart = parts[2] ?? '000';
                //     return parseInt(numPart, 10);
                // });
                // const maxUsed = usedNumbers.length ? Math.max(...usedNumbers) : 0;
                // const nextNumber = maxUsed + 1;
                // const paddedNumber = String(nextNumber).padStart(3, '0');
                // this.code = `PR-${dateStr}-${paddedNumber}`;

                axios.get('/parachute-inspection/generate-code')
                    .then(response => {
                        this.code = response.data.code;
                    })
                    .catch(error => {
                        console.error('Gagal generate code:', error);
                    });
            },
            onModalOpen() {
                this.generateCode();
                this.date = '';
                this.activity = '';
                this.checker = '';

                this.category = '';
                this.type = '';
                this.partNumber = '';
                this.serialNumber = '';
                this.parachuteItems = [];
            },

            onParachuteChange() {
                const selected = this.parachute.find(item => item.id === this.parachuteSelect);
                if (selected) {
                    this.category = selected.category;
                    this.type = selected.type;
                    this.partNumber = selected.part_number;
                    this.serialNumber = selected.serial_number;
                } else {
                    this.category = '';
                    this.type = '';
                    this.partNumber = '';
                    this.serialNumber = '';
                }
            },

            addItems: function() {
                this.parachuteItems.push({
                    "file": "",
                    "previewUrl": null,
                    "description": "",
                });
            },
            removeItem: function(index) {
                this.parachuteItems.splice(index, 1);
            },
            handleFileUpload(event, index) {
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

                if (!this.parachuteItems[index]) {
                    console.error(`parachuteItems[${index}] tidak ditemukan!`);
                    // return;
                    this.$set(this.parachuteItems, index, {
                        file: '',
                        description: '',
                        previewUrl: ''
                    });
                }
                if (this.parachuteItems[index]) {
                    this.parachuteItems[index].file = file;
                    reader.onload = (e) => {
                        this.parachuteItems[index].previewUrl = e.target.result;
                    };
                }
                reader.readAsDataURL(file);
            },

            submitForm: function() {
                if (this.date == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Tanggal Pemeriksaan tidak boleh kosong.',
                        'warning'
                    );
                } else if (this.activity == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Nama Kegiatan tidak boleh kosong.',
                        'warning'
                    )
                } else if (this.checker == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Nama Petugas tidak boleh kosong.',
                        'warning'
                    )
                } else if (this.parachuteSelect == '') {
                    Swal.fire(
                        'Terjadi Kesalahan!',
                        'Data Parasut tidak boleh kosong .',
                        'warning'
                    )
                } else {
                    this.sendData();
                    this.loading = true;
                }
            },
            sendData: function() {
                let vm = this;
                vm.loading = true;
                let formData = new FormData();
                formData.append('code', this.code);
                formData.append('date', this.date);
                formData.append('activity', this.activity);
                formData.append('checker', this.checker);
                formData.append('parachute_id', this.parachuteSelect);
                this.parachuteItems.forEach((item, index) => {
                    if (item.file) {
                        formData.append(`items[${index}][file]`, item.file);
                        formData.append(`items[${index}][description]`, item.description);
                    }
                });
                axios.post('/parachute-inspection', formData, {
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

            onSelcected: function(id) {
                this.parachuteDetail = this.parachuteInspection.filter((item) => {
                    return item.id == id;
                })[0];
                console.log('parachuteDetail:', this.parachuteDetail);
                if (this.parachuteDetail.items.length > 0) {
                    this.parachuteDetail.items.forEach(item => {
                        this.detailItems.push({
                            created: item.created_at || "",
                            description: item.description || "",
                            file: item.image_url || "",
                            previewUrl: null,
                        });
                    });
                }
                console.log('parachuteDetail.items :', this.parachuteDetail.items);
            },
            formatDateForInput(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                const pad = (n) => n.toString().padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            },
            addDetailItems: function() {
                this.detailItems.push({
                    "created": "",
                    "description": "",
                    "file": "",
                    "previewUrl": null,
                });
            },
            removeDetailItem: function(index) {
                // this.detailItems.splice(index, 1);
                this.detailItems.splice(index, 1, {
                    ...this.detailItems[index],
                    file: file,
                    previewUrl: e.target.result
                });

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

                if (!this.detailItems[index]) {
                    console.error(`detailItems[${index}] tidak ditemukan!`);
                    // return;
                    this.$set(this.detailItems, index, {
                        created: '',
                        description: '',
                        file: '',
                        previewUrl: ''
                    });
                }
                if (this.detailItems[index]) {
                    this.detailItems[index].file = file;
                    reader.onload = (e) => {
                        this.detailItems[index].previewUrl = e.target.result;
                    };
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
                this.parachuteItems.forEach((item, index) => {
                    if (item.file) {
                        formData.append(`items[${index}][created]`, item.file);
                        formData.append(`items[${index}][description]`, item.description);
                        formData.append(`items[${index}][file]`, item.file);
                    }
                });
                console.log('sendDataDetail:', id);
                console.log('formData_detail:', formData);
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
                            // window.location.href = '/parachute-inspection';
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

<script>
    $(function() {
        var Table = $('#parachute-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/parachute-inspection/datatables',
                data: function(d) {
                    d.number = $('.searchNumber').val();
                    d.date_start = app.date_start;
                    d.date_end = app.date_end;
                    d.type = app.parachuteType;
                }
            },
            columns: [

                {
                    data: 'date',
                    name: 'date',
                    render: function(data, type, row) {
                        return `<div  class="text-center font-weight-bolder">${data}</div>`;
                    }
                },
                {
                    data: 'number',
                    name: 'number',
                    render: function(data, type, row) {
                        return `<div  class="text-center font-weight-bolder">${data}</div>`;
                    }
                },

                {
                    data: 'category',
                    name: 'category',
                    render: function(data, type, row) {
                        return `<div  class="text-center font-weight-bolder">${data}</div>`;
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
                    data: 'part_number',
                    name: 'part_number',
                    render: function(data, type, row) {
                        return `<div class="text-center font-weight-bolder">${data}</div>`;
                    }
                },
                {
                    data: 'serial_number',
                    name: 'serial_number',
                    render: function(data, type) {
                        return `<div class="text-center font-weight-bolder">${data}</div>`;
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
            console.log('delete_id:', id);
            const $row = $(this).closest('tr');
            const rowData = Table.row($row).data();
            const itemNumber = rowData.number;
            Swal.fire({
                title: 'Yakin ingin menghapus data ?',
                html: `Kode : <strong>${itemNumber}</strong> akan dihapuskan.`,
                // text: "The data will be deleted",
                icon: 'warning',
                reverseButtons: true,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return axios.delete('/parachute-inspection/' + id)
                        .then(function(response) {
                            console.log(response.data);
                        })
                        .catch(function(error) {
                            console.log(error.data);
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops..',
                                text: 'Terdapat kesalahan!',
                            })
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Data berhasil dihapus',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // window.location.reload();
                            Table.ajax.reload();
                        }
                    })
                }
            })
        })

        $(document).on('click', '.btn-detail', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            console.log('edit_id:', id);
            if (typeof app !== 'undefined' && app.onSelcected) {
                app.onSelcected(id);
            }
        });

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