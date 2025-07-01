<?php

// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController; // Kita akan membuat Controller ini
use App\Http\Controllers\SensorApiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', [SensorController::class, 'index']);


// Tambahkan route ini
// Route::get('/data-sensor', [SensorController::class, 'index'])->name('sensor.data');

// routes/api.php
Route::get('/api/sensor-data', [SensorApiController::class, 'fetchData']);