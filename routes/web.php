<?php

use App\Http\Controllers\web\ParachuteController;
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
