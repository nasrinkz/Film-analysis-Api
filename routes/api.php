<?php

use App\Http\Controllers\api\v1\FilmController;
use App\Http\Controllers\api\v1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1')->group(function() {
    Route::post('/login',[UserController::class,'login']);
    Route::post('/codeValidation',[UserController::class,'validateCode']);
    Route::post('/register',[UserController::class,'register']);
    Route::post('/updateProfile',[UserController::class,'update']);
    Route::post('/indexFilms',[FilmController::class,'index']);
    Route::post('/filterFilms',[FilmController::class,'filter']);
});
