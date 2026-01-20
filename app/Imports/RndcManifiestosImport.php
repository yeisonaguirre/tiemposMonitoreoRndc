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
    private int $skipped = 0;

    private array $details = [];   // 👈 detalle por fila
    private int $maxDetailsInMemory = 2000; // límite razonable

    public function __construct(private readonly RndcService $service) {}

    public function collection(Collection $rows)
    {
        // WithHeadingRow empieza en fila 2 (fila 1 es header)
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            $this->total++;

            $manifiesto = trim((string)($row['manifiesto'] ?? $row['nro_manifiesto'] ?? $row['nummanifiestocarga'] ?? ''));
            $autorizacion = trim((string)($row['autorizacion'] ?? $row['ingresoidmanifiesto'] ?? ''));

            if ($manifiesto === '' || $autorizacion === '') {
                $this->fail++;
                $this->addDetail($rowNumber, $manifiesto, $autorizacion, 'Faltan datos (manifiesto o autorización vacíos)');
                continue;
            }

            $manifiesto   = preg_replace('/\s+/', '', $manifiesto);
            $autorizacion = preg_replace('/\s+/', '', $autorizacion);

            $yaExiste = RndcManifiesto::query()
                ->where('nummanifiestocarga', $manifiesto)
                ->where('ingresoidmanifiesto', $autorizacion)
                ->exists();

            if ($yaExiste) {
                $this->skipped++;
                $this->addDetail($rowNumber, $manifiesto, $autorizacion, 'Omitido: ya existe en BD', 'skipped');
                continue;
            }

            try {
                $count = $this->service->syncManifiestosDesdeWebService($autorizacion);

                if ($count > 0) {
                    $this->ok++;
                    $this->addDetail($rowNumber, $manifiesto, $autorizacion, "OK: actualizado ({$count})", 'ok');
                } else {
                    $this->fail++;
                    $this->addDetail($rowNumber, $manifiesto, $autorizacion, 'RNDC no devolvió cambios / no encontrado');
                }

            } catch (\Throwable $e) {
                $this->fail++;
                $this->addDetail($rowNumber, $manifiesto, $autorizacion, 'Error: ' . $e->getMessage());
            }
        }
    }

    private function addDetail(int $fila, string $manifiesto, string $autorizacion, string $mensaje, string $estado = 'fail'): void
    {
        // Evita reventar memoria si viene enorme
        if (count($this->details) >= $this->maxDetailsInMemory) return;

        $this->details[] = [
            'fila' => $fila,
            'manifiesto' => $manifiesto,
            'autorizacion' => $autorizacion,
            'estado' => $estado, // ok|fail|skipped
            'mensaje' => $mensaje,
        ];
    }

    public function result(): array
    {
        return [
            'ok' => $this->ok,
            'fail' => $this->fail,
            'skipped' => $this->skipped,
            'total' => $this->total,
            'details' => $this->details,
        ];
    }
}
