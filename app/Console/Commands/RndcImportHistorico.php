<?php

namespace App\Console\Commands;

use App\Services\RndcService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RndcImportHistorico extends Command
{
    protected $signature = 'rndc:import-historico {file}';
    protected $description = 'Importa datos históricos de RNDC desde un archivo XML/TXT';

    public function handle(RndcService $service)
    {
        $file = $this->argument('file'); // ejemplo: rndc/historico_2025_11_27.txt

        if (!Storage::exists($file)) {
            $this->error("El archivo {$file} no existe en storage/app/");
            return self::FAILURE;
        }

        $content = Storage::get($file);

        // Convertir de ISO-8859-1 a UTF-8
        $contentUtf8 = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');

        $xml = simplexml_load_string($contentUtf8);

        if (!$xml) {
            $this->error('El archivo no contiene XML válido.');
            return self::FAILURE;
        }

        $count = $service->syncFromXml($xml);

        $this->info("Importación finalizada. Manifiestos procesados: {$count}");

        return self::SUCCESS;
    }
}
