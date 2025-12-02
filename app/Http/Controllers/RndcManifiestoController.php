<?php

namespace App\Http\Controllers;

use App\Models\RndcManifiesto;
use Illuminate\Http\Request;
use App\Models\RndcPuntoControl;
use App\Services\RndcService;
use Carbon\Carbon;

class RndcManifiestoController extends Controller
{
    public function index(Request $request)
    {
        // Puedes agregar filtros por fecha, placa, etc.
        $manifiestos = RndcManifiesto::query()
            ->select('rndc_manifiestos.*')
            ->leftJoin('rndc_puntos_control AS pc', function ($join) {
                $join->on('pc.rndc_manifiesto_id', '=', 'rndc_manifiestos.id')
                    ->where('pc.finalizado', false);
            })
            ->with(['puntosControl' => function ($q) {
                $q->where('finalizado', false)
                ->orderBy('fechacita'); // <-- orden interno de puntos de control
            }])
            ->whereExists(function ($q) {
                $q->selectRaw(1)
                    ->from('rndc_puntos_control')
                    ->whereColumn('rndc_manifiestos.id', 'rndc_puntos_control.rndc_manifiesto_id')
                    ->where('rndc_puntos_control.finalizado', false);
            })
            ->orderBy('pc.fechacita')   // <-- orden principal
            ->orderByDesc('rndc_manifiestos.fechaexpedicionmanifiesto')
            ->orderByDesc('rndc_manifiestos.id')
            ->paginate(20);

        return view('rndc.manifiestos.index', compact('manifiestos'));
    }

    public function show(RndcManifiesto $manifiesto)
    {
        $manifiesto->load('puntosControl');

        return view('rndc.manifiestos.show', compact('manifiesto'));
    }

    public function sync(RndcService $service)
    {
        try {
            $count = $service->syncManifiestosDesdeWebService();
        } catch (\Exception $e) {
            return back()->with('error', 'Error al sincronizar manifiestos: '.$e->getMessage());
        }
        
        if ($count > 0) {
            return back()->with('success', "Se actualizaron {$count} manifiestos.");
        }

        return back()->with('warning', 'No se encontraron nuevos manifiestos o hubo un error en la consulta.');
    }

    public function crearEvento(RndcManifiesto $manifiesto, RndcPuntoControl $punto)
    {
        // (Opcional) validar que el punto pertenece al manifiesto
        if ($punto->rndc_manifiesto_id !== $manifiesto->id) {
            abort(404);
        }

        return view('rndc.puntos.evento', [
            'manifiesto' => $manifiesto,
            'punto'      => $punto,
        ]);
    }

    public function enviarEvento(Request $request, RndcManifiesto $manifiesto, RndcPuntoControl $punto, RndcService $service) {
        if ($punto->rndc_manifiesto_id !== $manifiesto->id) {
            abort(404);
        }

        $data = $request->validate([
            'latitud'       => 'required|numeric',
            'longitud'      => 'required|numeric',
            'fechallegada'  => 'required|date_format:d/m/Y',
            'horallegada'   => 'required|date_format:H:i',
            'fechasalida'   => 'required|date_format:d/m/Y',
            'horasalida'    => 'required|date_format:H:i',
        ]);

        $payload = [
            'ingresoidmanifiesto' => $manifiesto->ingresoidmanifiesto,
            'numplaca'            => $manifiesto->numplaca,
            'codpuntocontrol'     => $punto->codpuntocontrol,
            'latitud'             => $data['latitud'],
            'longitud'            => $data['longitud'],
            'fechallegada'        => $data['fechallegada'],
            'horallegada'         => $data['horallegada'],
            'fechasalida'         => $data['fechasalida'],
            'horasalida'          => $data['horasalida'],
        ];

        try {
            $result = $service->enviarEventoPuntoControl($payload);
        } catch (\Exception $e) {
            return redirect()
                ->route('rndc.manifiestos.show', $manifiesto)
                ->with('error', 'Error al enviar el evento al webservice: ' . $e->getMessage());
        }

        // Normalizamos resultado
        $ok        = $result['ok']        ?? false;
        $ingresoid = $result['ingresoid'] ?? null;

        // Datos base que SIEMPRE se actualizan
        $updateData = [
            'latitud'       => $data['latitud'],
            'longitud'      => $data['longitud'],
            'fecha_llegada' => Carbon::createFromFormat('d/m/Y', $data['fechallegada'])->format('Y-m-d'),
            'hora_llegada'  => $data['horallegada'],
            'fecha_salida'  => Carbon::createFromFormat('d/m/Y', $data['fechasalida'])->format('Y-m-d'),
            'hora_salida'   => $data['horasalida'],
            'xml_solicitud' => $result['xml_request']  ?? null,
            'xml_respuesta' => $result['xml_response'] ?? null,
        ];

        // Solo si RNDC respondió OK y con ingresoid válido marcamos como enviado/finalizado
        if ($ok && $ingresoid) {
            $updateData['evento_enviado_at']   = now();
            $updateData['numero_autorizacion'] = $ingresoid; // aquí guardas el ingresoid
            $updateData['finalizado']          = true;
        }

        $punto->update($updateData);

        $msgType = $ok && $ingresoid ? 'success' : 'error';
        $msgText = $ok && $ingresoid
            ? 'Evento enviado correctamente — ID de ingreso: ' . $ingresoid
            : 'Hubo un problema al enviar el evento: RNDC no devolvió un ID de ingreso válido.';

        return redirect()
            ->route('rndc.manifiestos.show', $manifiesto)
            ->with($msgType, $msgText);
    }
}
