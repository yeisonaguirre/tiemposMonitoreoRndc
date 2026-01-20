<?php

namespace App\Imports;

use App\Models\RndcManifiesto;
use App\Services\RndcService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RndcManifiestosImport implements ToCollection, WithHeadingRow
{
    private int $ok = 0;
    private int $fail = 0;
    private int $total = 0;
    private int $skipped = 0; // ya existían

    public function __construct(private readonly RndcService $service) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->total++;

            $manifiesto = trim((string)($row['manifiesto'] ?? $row['nro_manifiesto'] ?? $row['nummanifiestocarga'] ?? ''));
            $autorizacion = trim((string)($row['autorizacion'] ?? $row['ingresoidmanifiesto'] ?? ''));

            if ($manifiesto === '' || $autorizacion === '') {
                $this->fail++;
                continue;
            }

            // Normaliza (por si vienen con espacios)
            $manifiesto   = preg_replace('/\s+/', '', $manifiesto);
            $autorizacion = preg_replace('/\s+/', '', $autorizacion);

            // ✅ VALIDAR SI YA EXISTE (manifiesto + autorización)
            $yaExiste = RndcManifiesto::query()
                ->where('nummanifiestocarga', $manifiesto)
                ->where('ingresoidmanifiesto', $autorizacion)
                ->exists();

            if ($yaExiste) {
                $this->skipped++;
                continue;
            }

            try {
                $count = $this->service->syncManifiestosDesdeWebService($autorizacion);

                if ($count > 0) $this->ok++;
                else $this->fail++;

            } catch (\Throwable $e) {
                $this->fail++;

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
            'skipped' => $this->skipped,
            'total' => $this->total,
        ];
    }
}
