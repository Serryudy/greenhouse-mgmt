<?php

use App\Http\Controllers\Api\ActuatorCommandController;
use App\Http\Controllers\Api\SensorDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| ESP32 device (LAN) endpoints
|--------------------------------------------------------------------------
| Authenticated by the X-Device-Key header via the 'device.auth' middleware.
*/
Route::middleware('device.auth')->group(function () {
    Route::post('/sensor-data', [SensorDataController::class, 'store'])
        ->middleware('throttle:60,1');
    Route::get('/devices/commands', [ActuatorCommandController::class, 'pending']);
    Route::post('/commands/{id}/acknowledge', [ActuatorCommandController::class, 'acknowledge']);
});
