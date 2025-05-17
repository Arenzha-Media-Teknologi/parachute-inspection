<?php

use App\Http\Controllers\ParachuteInspectionController;
use App\Http\Controllers\web\AuthController;
use App\Http\Controllers\web\UserController;
use App\Http\Controllers\web\ParachuteController;
use App\Http\Controllers\web\UserGroupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('web.dashboard.index');
})->middleware('auth');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {

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
        // Route::patch('/{id}', 'update')->name('parachute-inspection.update');
        Route::delete('/{id}', 'destroy')->name('parachute-inspection.destroy');
    });

    Route::controller(UserGroupController::class)->prefix('/user-group')->group(function () {
        Route::get('/datatables', 'indexData')->name('user-group.indexData');
        Route::get('/', 'index')->name('user-group.index');
        Route::get('/create', 'create')->name('user-group.create');
        Route::get('/edit/{id}', 'edit')->name('user-group.edit');
        Route::post('/', 'store')->name('user-group.post');
        Route::patch('/{id}', 'update')->name('user-group.update');
        Route::delete('/{id}', 'destroy')->name('user-group.destroy');
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
});
