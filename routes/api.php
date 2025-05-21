<?php

use App\Http\Controllers\api\AuthApiController;
use App\Http\Controllers\api\GeneralApiController;
use App\Http\Controllers\api\ParachuteApiController;
use App\Http\Controllers\api\ParachuteInspectionApiController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Route::get('/seed-parachutes', function () {
//     $parachutes = [];
//     for ($i = 3; $i < 50; $i++) {
//         $parachutes[] = [
//             'serial_number'  => sprintf('%06d', $i),
//             'part_number'  => 'PART-' . sprintf('%03d', $i),
//             'type'  => 'TYPE-' . sprintf('%03d', $i),
//             'category'  => $i % 2 == 0 ? "Parasut Orang (PUO)" : "PARASUT BARANG (PUB)",
//             'created_by' => 1,
//             'created_at' => Carbon::now()->toDateTimeString(),
//             'updated_at' => Carbon::now()->toDateTimeString(),
//         ];
//     }

//     DB::table('parachutes')->insert($parachutes);
// });

Route::prefix('auth')->group(function () {
    Route::get('/login', [AuthApiController::class, 'login']);
});

Route::prefix('general')->group(function () {
    Route::get('/test-connection', [GeneralApiController::class, 'testConnection']);
});

Route::prefix('parachutes')->group(function () {
    Route::get('/', [ParachuteApiController::class, 'getAll']);
    Route::post('/', [ParachuteApiController::class, 'store']);
});

Route::prefix('parachute-inspections')->group(function () {
    Route::get('/', [ParachuteInspectionApiController::class, 'getAll']);
    Route::get('/{parachuteInspectionId}', [ParachuteInspectionApiController::class, 'getOne']);
    Route::post('/', [ParachuteInspectionApiController::class, 'store']);
    Route::post('/{parachuteInspectionId}/update', [ParachuteInspectionApiController::class, 'update']);
    Route::delete('/{parachuteInspectionId}', [ParachuteInspectionApiController::class, 'destroy']);
});
