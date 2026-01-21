<?php

namespace App\Console\Commands;

use App\Models\RndcManifiesto;
use App\Models\RndcPuntoControl;
use App\Services\RndcService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FetchRndcTodosManifiestos extends Command
{
    protected $signature = 'rndc:fetch-manifiestos-todos';

    protected $description = 'Consulta TODOS los manifiestos RNDC y los sincroniza';

    public function handle(RndcService $service): int
    {
        $this->info('Iniciando consulta RNDC (TODOS)...');

        try {
            $xml = $service->consultarTodosManifiestos();
        } catch (\Throwable $e) {
            $this->error('Error RNDC TODOS: ' . $e->getMessage());
            logger()->error('CRON rndc:fetch-manifiestos-todos falló', ['exception' => $e]);
            return self::FAILURE;
        }

        if (!$xml || !isset($xml->documento)) {
            $this->error('RNDC no devolvió documentos (TODOS)');
            return self::FAILURE;
        }

        $procesados = 0;

        foreach ($xml->documento as $doc) {
            $ingresoId = (string) $doc->ingresoidmanifiesto;

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

            // aquí puedes usar tu lógica de hash si quieres
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

        $this->info("TODOS sincronizados. Total: {$procesados}");

        return self::SUCCESS;
    }

    private function parseDate(string $value): ?string
    {
        try {
            return $value ? Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d') : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
