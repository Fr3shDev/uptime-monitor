<?php

use App\Http\Controllers\Api\MonitorController;
use App\Http\Controllers\Api\MonitorHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/monitors', [MonitorController::class, 'index']);
Route::post('/monitors', [MonitorController::class, 'store']);
Route::get('/monitors/{id}/history', MonitorHistoryController::class);
