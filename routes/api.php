<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

Route::post('/rndc/import-historico', function () {
    try {
        if (!Storage::exists('rndc_respuesta.txt')) {
            throw new \Exception('El archivo rndc_respuesta.txt no existe.');
        }

        $xmlString = Storage::get('rndc_respuesta.txt');

        if (empty($xmlString)) {
            throw new \Exception('El archivo rndc_respuesta.txt está vacío o no existe.');
        }

        $xmlUtf8 = mb_convert_encoding($xmlString, 'UTF-8', 'ISO-8859-1');
        $xml = simplexml_load_string($xmlUtf8);

        $count = app(\App\Services\RndcService::class)->syncFromXml($xml);

        return ['ok' => true, 'imported' => $count];
    } catch (\Exception $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
});

