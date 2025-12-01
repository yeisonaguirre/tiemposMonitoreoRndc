<?php

namespace App\Console\Commands;

use App\Models\RndcManifiesto;
use App\Models\RndcPuntoControl;
use App\Services\RndcService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FetchRndcManifiestos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rndc:fetch-manifiestos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta el web service RNDC y guarda los manifiestos y puntos de control';

    /**
     * Execute the console command.
     */
    public function handle(RndcService $service): int
    {
        $xml = $service->consultarManifiestos();

        if (!$xml || !isset($xml->documento)) {
            $this->error('No se recibió información válida desde RNDC');
            return self::FAILURE;
        }

        foreach ($xml->documento as $doc) {
            $ingresoId = (string) $doc->ingresoidmanifiesto;

            // Crear o actualizar manifiesto
            $manifiesto = RndcManifiesto::updateOrCreate(
                ['ingresoidmanifiesto' => $ingresoId],
                [
                    'numnitempresatransporte'  => (string) $doc->numnitempresatransporte,
                    'fechaexpedicionmanifiesto'=> $this->parseDate((string) $doc->fechaexpedicionmanifiesto),
                    'codigoempresa'            => (string) $doc->codigoempresa,
                    'nummanifiestocarga'       => (string) $doc->nummanifiestocarga,
                    'numplaca'                 => (string) $doc->numplaca,
                ]
            );

            // Limpiar puntos de control anteriores y volver a crear (opcional)
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
        }

        $this->info('Manifiestos RNDC actualizados correctamente.');
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
