<?php

use App\Http\Controllers\ApiGatewayController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use MongoDB\Client;

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

Route::prefix('v1')->middleware(['api','jwt.verify'])->group(function(){
    Route::prefix('products')->group(function(){
        Route::get('/', [ApiGatewayController::class,'products']);
    });
    Route::get('/token', [ApiGatewayController::class,'checkToken']);
});

Route::prefix('v1/auth')->group(function(){
    Route::post('/login', [ApiGatewayController::class,'login']);
});


