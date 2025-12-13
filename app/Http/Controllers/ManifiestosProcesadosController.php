<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RndcManifiesto;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManifiestosProcesadosController extends Controller
{
    public function index(Request $request)
    {
        $query = RndcManifiesto::query()
            ->select('rndc_manifiestos.*')
            // fecha de “último evento enviado” o “último punto finalizado” para ordenar
            ->addSelect([
                'ultima_fecha_evento' => DB::table('rndc_puntos_control')
                    ->selectRaw('MAX(evento_enviado_at)')
                    ->whereColumn('rndc_puntos_control.rndc_manifiesto_id', 'rndc_manifiestos.id')
                    ->where('finalizado', true),
            ])
            // solo los que tienen al menos 1 punto finalizado (procesado)
            ->whereExists(function ($q) {
                $q->selectRaw(1)
                    ->from('rndc_puntos_control')
                    ->whereColumn('rndc_manifiestos.id', 'rndc_puntos_control.rndc_manifiesto_id')
                    ->where('rndc_puntos_control.finalizado', true);
            })
            // cargar puntos finalizados (histórico)
            ->with(['puntosControl' => function ($q) {
                $q->where('finalizado', true)
                  ->orderByDesc('evento_enviado_at')
                  ->orderByDesc('id');
            }]);

        // filtros (reutilizas los mismos)
        $query->when($request->filled('ingresoidmanifiesto'), fn($q) =>
            $q->where('rndc_manifiestos.ingresoidmanifiesto', 'like', '%'.$request->ingresoidmanifiesto.'%')
        );

        $query->when($request->filled('nummanifiestocarga'), fn($q) =>
            $q->where('rndc_manifiestos.nummanifiestocarga', 'like', '%'.$request->nummanifiestocarga.'%')
        );

        $query->when($request->filled('numplaca'), fn($q) =>
            $q->where('rndc_manifiestos.numplaca', 'like', '%'.$request->numplaca.'%')
        );

        $query->when($request->filled('codigoempresa'), fn($q) =>
            $q->where('rndc_manifiestos.codigoempresa', 'like', '%'.$request->codigoempresa.'%')
        );

        // filtro por “enviado desde / hasta” (basado en evento_enviado_at)
        $query->when($request->filled('enviado_desde'), function ($q) use ($request) {
            try {
                $desde = Carbon::createFromFormat('d/m/Y', $request->enviado_desde)->startOfDay();
                $q->whereHas('puntosControl', fn($pc) => $pc->where('finalizado', true)->where('evento_enviado_at', '>=', $desde));
            } catch (\Exception $e) {}
        });

        $query->when($request->filled('enviado_hasta'), function ($q) use ($request) {
            try {
                $hasta = Carbon::createFromFormat('d/m/Y', $request->enviado_hasta)->endOfDay();
                $q->whereHas('puntosControl', fn($pc) => $pc->where('finalizado', true)->where('evento_enviado_at', '<=', $hasta));
            } catch (\Exception $e) {}
        });

        $query->orderByDesc('ultima_fecha_evento')
              ->orderByDesc('rndc_manifiestos.id');

        $manifiestos = $query->paginate(20)->appends($request->query());

        return view('rndc.manifiestos.procesados.index', compact('manifiestos'));
    }
}
