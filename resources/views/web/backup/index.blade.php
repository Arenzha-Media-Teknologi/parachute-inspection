@extends('web.layouts.app')

@section('title', 'PITL SATHAR72')

@section('prehead')
<meta name="csrf-token" content="{{ csrf_token() }}">
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
                                    <button id="cancelBackupBtn" class="btn btn-outline-danger ms-2" style="display: none;">
                                        <i class="fas fa-times-circle me-2"></i> Batalkan
                                    </button>


                                    <div id="progressContainer" class="mt-3" style="display: none;">
                                        <div class="progress mb-2" style="height: 25px;">
                                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                                role="progressbar" style="width: 0%;"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small id="progressStatus">Mempersiapkan backup...</small>
                                            <small id="progressPercent">0%</small>
                                        </div>
                                        <div class="mt-1">
                                            <small id="fileInfo" class="text-muted">Ukuran file: -</small>
                                        </div>
                                        <div>
                                            <small id="estimatedTime" class="text-muted"></small>
                                        </div>
                                    </div>
                                    <br>
                                    <br>
                                    <span>Terakhir Download : {{ \Carbon\Carbon::parse($lastBackup->updated_at ?? '')->format('d M Y H:i') }} </span>


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
            const estimatedTime = document.getElementById('estimatedTime');
            const cancelBackupBtn = document.getElementById('cancelBackupBtn');

            // Variabel untuk cancel request
            const CancelToken = axios.CancelToken;
            let cancelRequest;

            // Format bytes ke readable format
            const formatBytes = (bytes, decimals = 2) => {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            };

            // Format waktu
            const formatTime = (seconds) => {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.floor(seconds % 60);
                return `${minutes}m ${remainingSeconds}s`;
            };

            // Fungsi untuk menampilkan alert
            const showAlert = (message, type = 'success') => {
                // Clear existing alerts first
                while (alertContainer.firstChild) {
                    alertContainer.removeChild(alertContainer.firstChild);
                }

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
            };

            // Fungsi untuk trigger download
            const triggerDownload = (url, filename) => {
                const anchor = document.createElement('a');
                anchor.href = url;
                anchor.download = filename || 'backup_' + new Date().toISOString().slice(0, 10) + '.zip';
                anchor.style.display = 'none';
                document.body.appendChild(anchor);
                anchor.click();
                document.body.removeChild(anchor);
            };

            // Fungsi untuk reset UI
            const resetUI = () => {
                backupBtn.disabled = false;
                backupBtn.innerHTML = '<i class="fas fa-file-export me-2"></i> Buat Backup Sekarang';
                progressContainer.style.display = 'none';
            };

            // Fungsi untuk update progress
            const updateProgress = (percent, status, size = null, timeRemaining = null) => {
                progressBar.style.width = `${percent}%`;
                progressPercent.textContent = `${percent}%`;
                progressStatus.textContent = status;

                if (size) {
                    fileInfo.textContent = `Ukuran file: ${formatBytes(size)}`;
                }

                if (timeRemaining !== null) {
                    estimatedTime.textContent = `Perkiraan waktu tersisa: ${formatTime(timeRemaining)}`;
                }
            };

            // Event listener untuk tombol cancel
            if (cancelBackupBtn) {
                cancelBackupBtn.addEventListener('click', function() {
                    if (cancelRequest) {
                        cancelRequest('Backup dibatalkan oleh pengguna');
                        showAlert('Backup dibatalkan', 'warning');
                        resetUI();
                    }
                });
            }

            // Event listener untuk tombol backup
            backupBtn.addEventListener('click', async () => {
                // Reset UI state
                progressBar.style.width = '0%';
                progressBar.style.backgroundColor = '';
                updateProgress(0, 'Mempersiapkan backup...');
                progressContainer.style.display = 'block';
                estimatedTime.textContent = '';

                backupBtn.disabled = true;
                backupBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';

                // Variabel untuk tracking progress dan estimasi waktu
                let startTime = Date.now();
                let lastLoaded = 0;
                let lastTime = startTime;

                try {
                    // 1. Request untuk membuat backup
                    const response = await axios({
                        method: 'post',
                        url: '{{ route("backup.database") }}',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        cancelToken: new CancelToken(function executor(c) {
                            cancelRequest = c;
                        }),
                        onUploadProgress: (progressEvent) => {
                            if (progressEvent.lengthComputable) {
                                const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);

                                // Hitung kecepatan download dan estimasi waktu
                                const currentTime = Date.now();
                                const timeDiff = (currentTime - lastTime) / 1000; // dalam detik
                                const loadedDiff = progressEvent.loaded - lastLoaded;

                                if (timeDiff > 0) {
                                    const speed = loadedDiff / timeDiff; // bytes per second
                                    const remainingBytes = progressEvent.total - progressEvent.loaded;
                                    const timeRemaining = remainingBytes / speed;

                                    updateProgress(
                                        percent,
                                        `Membuat backup... (${percent}%)`,
                                        progressEvent.total,
                                        timeRemaining
                                    );

                                    lastLoaded = progressEvent.loaded;
                                    lastTime = currentTime;
                                } else {
                                    updateProgress(
                                        percent,
                                        `Membuat backup... (${percent}%)`,
                                        progressEvent.total
                                    );
                                }
                            }
                        }
                    });

                    if (response.data.success && response.data.download_url) {
                        showAlert('Backup berhasil dibuat. Memulai download...', 'success');
                        updateProgress(100, 'Mengunduh backup...', response.data.file_size);

                        // 2. Trigger download langsung tanpa axios
                        // Karena kita sudah punya URL download dari server
                        triggerDownload(response.data.download_url, response.data.filename);

                        updateProgress(100, 'Backup selesai!', response.data.file_size);
                        showAlert('Backup berhasil diunduh', 'success');

                        // Reset UI setelah 3 detik
                        setTimeout(() => {
                            resetUI();
                            window.location.reload();
                        }, 3000);
                    } else {
                        throw new Error(response.data.message || 'Gagal mendapatkan URL download');
                    }
                } catch (error) {
                    console.error('Error details:', error);

                    let errorMessage = 'Terjadi kesalahan saat membuat backup';

                    if (axios.isCancel(error)) {
                        errorMessage = 'Backup dibatalkan';
                        console.log('Request canceled:', error.message);
                    } else if (error.response) {
                        // Server responded with error status
                        if (error.response.data && typeof error.response.data === 'object') {
                            errorMessage = error.response.data.message || errorMessage;
                        } else if (typeof error.response.data === 'string') {
                            errorMessage = error.response.data;
                        }

                        // Handle specific status codes
                        if (error.response.status === 404) {
                            errorMessage = 'File backup tidak ditemukan di server';
                        } else if (error.response.status === 413) {
                            errorMessage = 'File backup terlalu besar';
                        } else if (error.response.status === 500) {
                            errorMessage = 'Server mengalami masalah';
                        } else if (error.response.status === 503) {
                            errorMessage = 'Server sibuk, coba lagi nanti';
                        }
                    } else if (error.request) {
                        // Request dibuat tapi tidak ada response
                        errorMessage = 'Tidak ada respons dari server. Periksa koneksi Anda.';
                    } else if (error.message) {
                        errorMessage = error.message;
                    }

                    if (error.code === 'ECONNABORTED') {
                        errorMessage = 'Timeout: Proses backup terlalu lama';
                    }

                    showAlert(errorMessage, 'danger');
                    progressStatus.textContent = 'Gagal membuat backup';
                    progressBar.style.backgroundColor = '#dc3545';

                    // Reset UI lebih cepat jika error
                    setTimeout(() => {
                        resetUI();
                    }, 3000);
                } finally {
                    cancelRequest = null;
                }
            });
        });
    </script>
    @endsection