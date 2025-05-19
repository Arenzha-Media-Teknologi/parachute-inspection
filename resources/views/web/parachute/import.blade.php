@extends('web.layouts.app')

@section('title', 'Import Data Parasut')

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
                        <a href="/parachute/import" class="text-muted text-hover-primary">Upload Data Parasut</a>
                    </li>

                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-300 w-5px h-2px"></span>
                    </li>


                    <li class="breadcrumb-item text-gray-900">Upload Data Parasut</li>

                </ul>
            </div>
            <div class="d-flex align-items-center py-1">
            </div>
        </div>
    </div>
    <div class="post d-flex flex-column-fluid" id="app">
        <div id="kt_content_container" class="container-xxl">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">Upload Data Parasut</h3>
                </div>
                <div class="card-body">

                    @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                    @endif
                    <div class="alert alert-success d-flex align-items-center p-5">
                        <i class="ki-duotone ki-shield-tick fs-2hx text-success me-4"><span class="path1"></span><span class="path2"></span></i>
                        <div class="d-flex flex-column">
                            <h4 class="mb-1 text-dark">Download Template Upload</h4>
                            <span>Untuk Upload data Parasut agar sesui format, maka download Template berikut : <a href="/format/FORMAT PARASUT.xls" class="btn btn-success">Download Template</a></span>
                        </div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center p-5">
                        <div class="d-flex flex-column">
                            <h4 class="mb-1 text-dark">Upload</h4>
                            <form action="{{ route('parachute.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="file" class="form-control" placeholder="Upload Excel" id="excel_file" name="excel_file" required>
                                <span>Upload file excel yang sudah di download dan diisi sesuai template</span>
                                <div class="d-flex flex-row mt-5">
                                    <a href="/parachute" class="btn btn-light-dark me-3">Kembali</a>
                                    <button type="submit" class="btn btn-success">Import</button>
                                </div>
                            </form>
                        </div>
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
        let app = new Vue({
            el: '#app',
            data: {
                loading: false,
            },
            methods: {
                // 
            },
        })
    </script>

    @endsection