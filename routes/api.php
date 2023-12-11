<?php

use App\Http\Controllers\PerroController;
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

// Rutas para la API de Perros
Route::get('verPerro/{id}', [PerroController::class, 'verPerro']);
Route::post('guardarPerro', [PerroController::class, 'guardarPerro']);
Route::put('actualizarPerro/{id}', [PerroController::class, 'actualizarPerro']);
Route::delete('borrarPerro/{id}', [PerroController::class, 'borrarPerro']);
Route::get('obtenerPerroRandom', [PerroController::class, 'obtenerPerroRandom']);
Route::get('obtenerPerrosInteresados/{id}', [PerroController::class, 'obtenerPerrosInteresados']);
Route::get('obtenerPerrosCandidatos/{id}', [PerroController::class, 'obtenerPerrosCandidatos']);
Route::post('guardarInteraccion/{idInteresado}/{idCandidato}', [PerroController::class, 'guardarInteraccion']);
Route::put('guardarPreferencias/{idInteresado}/{idCandidato}', [PerroController::class, 'guardarPreferencias']);
Route::get('verPerrosAceptados/{id}', [PerroController::class,'verPerrosAceptados']);
Route::get('verPerrosRechazados/{id}', [PerroController::class, 'verPerrosRechazados']);
Route::get('verPerrosGeneral', [PerroController::class, 'verPerrosGeneral']);