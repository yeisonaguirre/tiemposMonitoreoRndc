<?php

namespace App\Console\Commands;

use App\Models\RndcManifiesto;
use App\Models\RndcPuntoControl;
use App\Services\RndcService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FetchRndcManifiestos extends Command
{
    protected $signature = 'rndc:fetch-manifiestos';

    protected $description = 'Consulta el web service RNDC y guarda los manifiestos y puntos de control';

    public function handle(RndcService $service): int
    {
        $this->info('Iniciando consulta RNDC...');

        try {
            $xml = $service->consultarManifiestos();
        } catch (\Throwable $e) {
            // 📌 Aquí atrapamos errores tipo "I/O error 103", timeouts, etc.
            $this->error('Error al consultar RNDC: ' . $e->getMessage());

            logger()->error('CRON rndc:fetch-manifiestos falló al consultar RNDC', [
                'exception' => $e,
            ]);

            // Si quieres que el cron se marque como fallo:
            return self::FAILURE;

            // Si prefieres que el cron "no falle" pero quede log:
            // return self::SUCCESS;
        }

        if (!$xml || !isset($xml->documento)) {
            $this->error('No se recibió información válida desde RNDC');
            return self::FAILURE;
        }

        $procesados = 0;

        foreach ($xml->documento as $doc) {
            $ingresoId = (string) $doc->ingresoidmanifiesto;

            // Crear o actualizar manifiesto
            $manifiesto = RndcManifiesto::updateOrCreate(
                ['ingresoidmanifiesto' => $ingresoId],
                [
                    'numnitempresatransporte'   => (string) $doc->numnitempresatransporte,
                    'fechaexpedicionmanifiesto' => $this->parseDate((string) $doc->fechaexpedicionmanifiesto),
                    'codigoempresa'             => (string) $doc->codigoempresa,
                    'nummanifiestocarga'        => (string) $doc->nummanifiestocarga,
                    'numplaca'                  => (string) $doc->numplaca,
                ]
            );

            // Limpiar puntos de control anteriores
            $manifiesto->puntosControl()->delete();

            if (isset($doc->puntoscontrol->puntocontrol)) {
                foreach ($doc->puntoscontrol->puntocontrol as $pc) {
                    RndcPuntoControl::create([
                        'rndc_manifiesto_id' => $manifiesto->id,
                        'codpuntocontrol'    => (int) $pc->codpuntocontrol,
                        'codmunicipio'       => (string) $pc->codmunicipio,
                        'direccion'          => (string) $pc->direccion,
                        'fechacita'          => $this->parseDate((string) $pc->fechacita),
                        'horacita'           => (string) $pc->horacita,
                        'latitud'            => (string) $pc->latitud !== '' ? (float) $pc->latitud : null,
                        'longitud'           => (string) $pc->longitud !== '' ? (float) $pc->longitud : null,
                        'tiempopactado'      => (int) $pc->tiempopactado,
                    ]);
                }
            }

            $procesados++;
        }

        $this->info("Manifiestos RNDC actualizados correctamente. Procesados: {$procesados}");

        return self::SUCCESS;
    }

    private function parseDate(string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
