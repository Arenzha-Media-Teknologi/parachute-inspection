@extends('web.layouts.app')

@section('title', 'Dashboard')

@section('prehead')
@endsection

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <div data-kt-swapper="true" data-kt-swapper-mode="prepend" data-kt-swapper-parent="{default: '#kt_content_container', 'lg': '#kt_toolbar_container'}" class="page-title d-flex align-items-center me-3 flex-wrap lh-1">
                <h1 class="d-flex align-items-center text-gray-900 fw-bold my-1 fs-3">Dashboard</h1>

            </div>
            <div class="d-flex align-items-center py-1">
            </div>
        </div>
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">

                    <div class="row col-12">

                        <div class="col-6">
                            <a href="/parachute">
                                <div class="alert alert-primary d-flex align-items-center p-5">
                                    <img src="files/icon/parachute.png" width="80px" alt="">
                                    <div class="d-flex flex-column">
                                        <h4 class="mb-1 text-dark">Total Data Parasut</h4>
                                        <h1>{{ $totalParachute }}</h1>
                                    </div>
                                </div>
                            </a>
                        </div>


                        <div class="col-6">
                            <a href="/parachute-inspection">
                                <div class="alert alert-primary d-flex align-items-center p-5">
                                    <img src="files/icon/parachute-check.png" width="80px" alt="">
                                    <div class="d-flex flex-column">
                                        <h4 class="mb-1 text-dark">Total Pemeriksaan Parasut</h4>
                                        <h1>{{ $totalParachuteInspection }}</h1>
                                    </div>
                                </div>
                            </a>
                        </div>
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

</script>
@endsection