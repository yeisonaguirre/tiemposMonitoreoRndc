<?php

namespace App\Imports;

use App\Services\RndcService;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class RndcManifiestosImport implements ToCollection, WithHeadingRow
{
    private int $ok = 0;
    private int $fail = 0;
    private int $total = 0;

    public function __construct(private readonly RndcService $service) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->total++;

            // Soporta encabezados: manifiesto/autorizacion
            // Si tu excel viene sin encabezados, dime y lo adapto a índices [0],[1]
            $manifiesto   = trim((string)($row['manifiesto'] ?? $row['nro_manifiesto'] ?? $row['nummanifiestocarga'] ?? ''));
            $autorizacion = trim((string)($row['autorizacion'] ?? $row['ingresoidmanifiesto'] ?? ''));
            
            if ($manifiesto === '' || $autorizacion === '') {
                $this->fail++;
                continue;
            }

            // Normalización (opcional)
            $manifiesto   = preg_replace('/\s+/', '', $manifiesto);
            $autorizacion = preg_replace('/\s+/', '', $autorizacion);

            try {
                $count = $this->service->syncManifiestosDesdeWebService($autorizacion);
                if ($count > 0) $this->ok++;
                else $this->fail++;
            } catch (\Throwable $e) {
                $this->fail++;
                // Si quieres log por fila:
                logger()->warning('Import RNDC fila fallida', [
                    'manifiesto' => $manifiesto,
                    'autorizacion' => $autorizacion,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function result(): array
    {
        return [
            'ok' => $this->ok,
            'fail' => $this->fail,
            'total' => $this->total,
        ];
    }
}
