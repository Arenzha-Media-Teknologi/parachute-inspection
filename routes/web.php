<?php

use App\Http\Controllers\web\ParachuteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('web.dashboard.index');
});

Route::controller(ParachuteController::class)->prefix('/parachute')->group(function () {
    Route::get('/', 'index')->name('outlet-book.index');
    Route::post('/', 'store')->name('outlet-book.post');
    Route::post('/{id}/update', 'update')->name('outlet-book.update');
    Route::delete('/{id}', 'destroy')->name('outlet-book.destroy');
});
