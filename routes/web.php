<?php

use App\Http\Controllers\web\ParachuteController;
use App\Http\Controllers\web\UserGroupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('web.dashboard.index');
});

Route::controller(ParachuteController::class)->prefix('/parachute')->group(function () {
    Route::get('/datatables', 'indexData')->name('parachute.indexData');
    Route::get('/', 'index')->name('parachute.index');
    Route::post('/', 'store')->name('parachute.post');
    Route::patch('/{id}', 'update')->name('parachute.update');
    Route::delete('/{id}', 'destroy')->name('parachute.destroy');
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
