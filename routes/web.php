<?php

use App\Http\Controllers\ParachuteInspectionController;
use App\Http\Controllers\web\AuthController;
use App\Http\Controllers\web\BackupController;
use App\Http\Controllers\web\DashboardController;
use App\Http\Controllers\web\UserController;
use App\Http\Controllers\web\ParachuteController;
use App\Http\Controllers\web\UserGroupController;
use Illuminate\Support\Facades\Route;
use Yaza\LaravelGoogleDriveStorage\Gdrive;



Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/test-gdrive', function () {
        try {
            Gdrive::put('test.txt', 'Hello Google Drive');
            return 'File uploaded successfully!';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    });

    Route::controller(DashboardController::class)->prefix('/')->group(function () {
        Route::get('/', 'index')->name('dashboard');
    });

    Route::controller(ParachuteController::class)->prefix('/parachute')->group(function () {
        Route::get('/datatables', 'indexData')->name('parachute.indexData');
        Route::get('/', 'index')->name('parachute.index');
        Route::get('/import', 'showImportForm')->name('parachute.page-import');
        Route::post('/import', 'import')->name('parachute.import');
        Route::post('/', 'store')->name('parachute.post');
        Route::patch('/{id}', 'update')->name('parachute.update');
        Route::delete('/{id}', 'destroy')->name('parachute.destroy');
        Route::post('/delete-multiple', 'deleteMultiple')->name('parachute.delete-multiple');
    });

    Route::controller(ParachuteInspectionController::class)->prefix('/parachute-inspection')->group(function () {
        Route::get('/datatables', 'indexData')->name('parachute-inspection.indexData');
        Route::get('/', 'index')->name('parachute-inspection.index');
        Route::get('/generate-code', 'generateCode')->name('parachute-inspection.generateCode');
        Route::post('/', 'store')->name('parachute-inspection.post');
        Route::get('/edit/{id}', 'edit')->name('parachute-inspection.edit');
        Route::post('/{id}', 'update')->name('parachute-inspection.update');
        Route::delete('/{id}', 'destroy')->name('parachute-inspection.destroy');
        Route::get('/report/preview', 'reportPreview')->name('parachute-inspection.reportPreview');
        Route::get('/report/generate-pdf', 'reportPdf')->name('parachute-inspection.reportPdf');
        Route::post('/report/generate-word', 'reportWord')->name('parachute-inspection.reportWord');
        Route::get('/report/generate-excel', 'reportExcel')->name('parachute-inspection.reportExcel');

        Route::get('/report-attachment/preview', 'reportAttachmentPreview')->name('parachute-inspection.reportAttachmentPreview');
        Route::get('/report-attachment/generate-pdf', 'reportAttachmentPdf')->name('parachute-inspection.reportAttachmentPdf');
        Route::post('/report-attachment/generate-word', 'reportAttachmentWord')->name('parachute-inspection.reportAttachmentWord');
        Route::get('/report/unserviceable', 'reportUnserviceable')->name('parachute-inspection.reportUnserviceable');
        Route::get('/print-tag/{id}', 'printTag')->name('parachute-inspection.printTag');
    });

    Route::controller(UserGroupController::class)->prefix('/group')->group(function () {
        Route::get('/datatables', 'indexData')->name('group.indexData');
        Route::get('/', 'index')->name('group.index');
        Route::get('/create', 'create')->name('group.create');
        Route::get('/edit/{id}', 'edit')->name('group.edit');
        Route::post('/', 'store')->name('group.post');
        Route::patch('/{id}', 'update')->name('group.update');
        Route::delete('/{id}', 'destroy')->name('group.destroy');
    });

    Route::controller(UserController::class)->prefix('/user')->group(function () {
        Route::get('/', 'index')->name('user.index');
        Route::get('/data', 'data')->name('user.data');
        Route::get('/create', 'create')->name('user.create');
        Route::get('/edit/{id}', 'edit')->name('user.edit');
        Route::post('/', 'store')->name('user.post');
        Route::patch('/{id}', 'update')->name('user.update');
        Route::delete('/{id}', 'destroy')->name('user.destroy');
    });
    Route::controller(BackupController::class)->prefix('/backup')->group(function () {
        Route::get('/', 'index')->name('backup.index');
        Route::post('/backup-data', 'backupDatabase')->name('backup.database');
    });

    Route::get('/backup/download/{filename}', [BackupController::class, 'downloadBackup'])
        ->name('backup.download');
});
