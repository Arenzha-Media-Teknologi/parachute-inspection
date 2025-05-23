@extends('web.layouts.app')

@section('title', 'PITL SATHAR72')

@section('prehead')
@endsection

@section('content')

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <div data-kt-swapper="true" data-kt-swapper-mode="prepend" data-kt-swapper-parent="{default: '#kt_content_container', 'lg': '#kt_toolbar_container'}" class="page-title d-flex align-items-center me-3 flex-wrap lh-1">
                <h1 class="d-flex align-items-center text-gray-900 fw-bold my-1 fs-3">Backup Database</h1>

            </div>
            <div class="d-flex align-items-center py-1">
            </div>
        </div>
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">

                    <div class="col-12">
                        @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                        @endif

                        <!--begin::Alert-->
                        <div class="alert d-flex flex-column flex-sm-row p-5 mb-10" style="background-color:rgb(85, 90, 98) !important;">
                            <i class="ki-duotone ki-shield fs-2hx text-light me-4 mb-5 mb-sm-0"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                <h4 class="mb-2 text-white">Backup Database parasut</h4>


                                <div class="card-body">

                                    <div id="alertContainer"></div>


                                    <button id="backupBtn" class="btn btn-success">
                                        <i class="fas fa-file-export me-2"></i> Buat Backup Sekarang
                                    </button>


                                    <div id="progressContainer" class="mt-3" style="display:none;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span id="progressStatus">Mempersiapkan backup...</span>
                                            <span id="progressPercent">0%</span>
                                        </div>
                                        <div class="progress" style="height: 20px;">
                                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                                role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <div class="mt-2 text-muted small">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <span id="fileInfo">Ukuran file: -</span>
                                        </div>
                                    </div>
                                    <br>
                                    <br>
                                    <span>Terakhir Download : {{ \Carbon\Carbon::parse($lastBackup->updated_at)->format('d M Y H:i') }} </span>


                                    <div class="alert alert-danger d-flex flex-column flex-sm-row p-5 mb-10 mt-3">
                                        <i class="ki-duotone ki-shield fs-2hx text-danger me-4 mb-5 mb-sm-0"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        <div class="d-flex flex-column text-danger pe-0 pe-sm-10">
                                            <h4 class="mb-2">Penting!</h4>
                                            <span><b>Siapkan Penyimpanan external seperti Flashdisk/Hardisk untuk menyimpan file backup database Anda.</b></span>
                                            <br>
                                            <span>Backup database adalah tindakan penting untuk menjaga keamanan data Anda. Pastikan untuk menyimpan file backup di tempat yang aman dan terpisah dari server utama Anda.</span>
                                        </div>
                                    </div>


                                    </span>
                                </div>

                            </div>
                            <!--end::Alert-->


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

    @push('styles')
    <style>
        #progressBar {
            transition: width 0.3s ease;
        }

        #alertContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
    </style>
    @endpush


    @section('pagescript')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const backupBtn = document.getElementById('backupBtn');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');
            const progressStatus = document.getElementById('progressStatus');
            const fileInfo = document.getElementById('fileInfo');
            const alertContainer = document.getElementById('alertContainer');

            function formatBytes(bytes, decimals = 2) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            }

            function showAlert(message, type = 'success') {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show`;
                alert.role = 'alert';
                alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
                alertContainer.appendChild(alert);

                setTimeout(() => {
                    alert.classList.remove('show');
                    setTimeout(() => alert.remove(), 150);
                }, 5000);
            }

            backupBtn.addEventListener('click', function() {
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';
                progressStatus.textContent = 'Mempersiapkan backup...';
                fileInfo.textContent = 'Ukuran file: -';

                backupBtn.disabled = true;
                backupBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';
                progressContainer.style.display = 'block';

                let downloadSize = 0;
                let lastUpdateTime = 0;
                let downloadSpeed = 0;
                let lastLoaded = 0;

                axios({
                        method: 'post',
                        url: '{{ route("backup.database") }}',
                        responseType: 'blob',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        onDownloadProgress: function(progressEvent) {
                            const now = Date.now();
                            const elapsedTime = (now - lastUpdateTime) / 1000;

                            if (progressEvent.total) {
                                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                                progressBar.style.width = percentCompleted + '%';
                                progressPercent.textContent = percentCompleted + '%';

                                if (elapsedTime > 0.5) {
                                    downloadSpeed = (progressEvent.loaded - lastLoaded) / elapsedTime;
                                    lastLoaded = progressEvent.loaded;
                                    lastUpdateTime = now;
                                }

                                const speedText = downloadSpeed > 0 ? ` (${formatBytes(downloadSpeed)}/detik)` : '';
                                progressStatus.textContent = `Mengunduh backup... ${speedText}`;
                                fileInfo.textContent = `Ukuran file: ${formatBytes(progressEvent.total)}`;
                            }
                        }
                    })
                    .then(response => {
                        if (!(response.data instanceof Blob)) {
                            throw new Error('Format response tidak valid');
                        }

                        const url = window.URL.createObjectURL(response.data);
                        const a = document.createElement('a');
                        a.href = url;

                        let filename = 'backup_' + new Date().toISOString().slice(0, 10) + '.sql';
                        const contentDisposition = response.headers['content-disposition'];
                        if (contentDisposition) {
                            const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
                            if (filenameMatch && filenameMatch[1]) {
                                filename = filenameMatch[1];
                            }
                        }

                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);

                        showAlert('Backup database berhasil dibuat dan diunduh', 'success');

                        // Tambahkan reload halaman setelah 1.5 detik
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    })
                    .catch(error => {
                        let errorMessage = 'Terjadi kesalahan saat membuat backup';

                        if (error.response) {
                            if (error.response.status === 422) {
                                errorMessage = 'Validasi gagal: ' +
                                    Object.values(error.response.data.errors).join(', ');
                            } else if (error.response.data && error.response.data.message) {
                                errorMessage = error.response.data.message;
                            } else {
                                errorMessage = `Error ${error.response.status}: ${error.response.statusText}`;
                            }

                            if (error.response.data instanceof Blob) {
                                const reader = new FileReader();
                                reader.onload = () => {
                                    try {
                                        const errorData = JSON.parse(reader.result);
                                        if (errorData.message) {
                                            errorMessage = errorData.message;
                                        }
                                    } catch (e) {
                                        console.error('Error parsing blob response', e);
                                    }
                                    showAlert(errorMessage, 'danger');
                                };
                                reader.readAsText(error.response.data);
                                return;
                            }
                        } else if (error.request) {
                            errorMessage = 'Tidak ada response dari server. Periksa koneksi internet Anda.';
                        } else {
                            errorMessage = error.message;
                        }

                        showAlert(errorMessage, 'danger');
                    })
                    .finally(() => {
                        backupBtn.disabled = false;
                        backupBtn.innerHTML = '<i class="fas fa-file-export me-2"></i> Buat Backup Sekarang';
                        setTimeout(() => {
                            progressContainer.style.display = 'none';
                        }, 2000);
                    });
            });
        });
    </script>


    @endsection