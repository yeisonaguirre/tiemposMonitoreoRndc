<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\RndcManifiestoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/rndc/manifiestos', [RndcManifiestoController::class, 'index'])
    ->name('rndc.manifiestos.index');

Route::get('/rndc/manifiestos/{manifiesto}', [RndcManifiestoController::class, 'show'])
    ->name('rndc.manifiestos.show');

Route::get('/rndc/manifiestos/{manifiesto}/puntos/{punto}/evento', [RndcManifiestoController::class, 'crearEvento'])
    ->name('rndc.puntos.evento.create');

Route::post('/rndc/manifiestos/{manifiesto}/puntos/{punto}/evento', [RndcManifiestoController::class, 'enviarEvento'])
    ->name('rndc.puntos.evento.store');

Route::post('/rndc/manifiestos/sync', [RndcManifiestoController::class, 'sync'])
    ->name('rndc.manifiestos.sync');

Route::get('/rndc/debug/last-response', function () {
    return response(Cache::get('rndc:last_response_xml', 'Sin datos'), 200, [
        'Content-Type' => 'application/xml',
    ]);
});
