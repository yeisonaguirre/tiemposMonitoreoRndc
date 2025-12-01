<?php

namespace App\Services;

use App\Models\RndcManifiesto;
use App\Models\RndcPuntoControl;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class RndcService
{
    /**
     * Consulta el webservice real y devuelve el XML parseado
     */
    public function consultarManifiestos(): ?SimpleXMLElement
    {
        $url    = config('services.rndc.url');
        $user   = config('services.rndc.user');
        $pass   = config('services.rndc.pass');
        $nitgps = config('services.rndc.nitgps');

        // 🔹 OPCIONAL: si quieres simular con archivo en local, descomenta este bloque:
        /*
        if (app()->environment('local') && Storage::exists('rndc/historico_inicial.txt')) {
            $fake = Storage::get('rndc/historico_inicial.txt');
            $fakeUtf8 = mb_convert_encoding($fake, 'UTF-8', 'ISO-8859-1');
            return simplexml_load_string($fakeUtf8) ?: null;
        }
        */

        // Construir el XML de solicitud (sin sangría rara)
        $xml = <<<XML
<?xml version='1.0' encoding='ISO-8859-1'?>
<root>
  <acceso>
    <username>{$user}</username>
    <password>{$pass}</password>
  </acceso>
  <solicitud>
    <tipo>9</tipo>
    <procesoid>4</procesoid>
  </solicitud>
  <documento>
    <numidgps>{$nitgps}</numidgps>
    <manifiestos>TODOS</manifiestos>
  </documento>
</root>
XML;

        // Consumir el servicio via POST
        $response = Http::withHeaders([
                'Content-Type' => 'application/xml; charset=ISO-8859-1',
            ])
            ->withBody($xml, 'application/xml')
            ->post($url);

        if (!$response->successful()) {
            logger()->error('Error al consultar RNDC', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        $body = $response->body();

        // Guardar la respuesta cruda en cache por 15 minutos (Redis si tienes CACHE_DRIVER=redis)
        Cache::put('rndc:last_response_xml', $body, now()->addMinutes(15));

        // Convertir de ISO-8859-1 a UTF-8 (para evitar problemas)
        $bodyUtf8  = mb_convert_encoding($body, 'UTF-8', 'ISO-8859-1');
        $xmlObject = simplexml_load_string($bodyUtf8);

        return $xmlObject ?: null;
    }

    /**
     * Sincroniza BD a partir de un XML (venga del WS o de un archivo histórico)
     */
    public function syncFromXml(SimpleXMLElement $xml): int
    {
        if (!isset($xml->documento)) {
            return 0;
        }

        $count = 0;

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

            // Borrar puntos de control previos (si actualizas históricos o quieres siempre la última versión)
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
                        'latitud'            => ($pc->latitud != '') ? (float) $pc->latitud : null,
                        'longitud'           => ($pc->longitud != '') ? (float) $pc->longitud : null,
                        'tiempopactado'      => (int) $pc->tiempopactado,
                    ]);
                }
            }

            $count++;
        }

        return $count;
    }

    public function enviarEventoPuntoControl(array $data): array
    {
        $url    = config('services.rndc.url');
        $user   = config('services.rndc.user');
        $pass   = config('services.rndc.pass');
        $nitgps = config('services.rndc.nitgps');

        $xml = <<<XML
            <?xml version='1.0' encoding='iso-8859-1'?>
            <root>
            <acceso>
                <username>{$user}</username>
                <password>{$pass}</password>
            </acceso>
            <solicitud>
                <tipo>1</tipo>
                <procesoid>60</procesoid>
            </solicitud>
            <variables>
                <numidgps>{$nitgps}</numidgps>
                <ingresoidmanifiesto>{$data['ingresoidmanifiesto']}</ingresoidmanifiesto>
                <numplaca>{$data['numplaca']}</numplaca>
                <codpuntocontrol>{$data['codpuntocontrol']}</codpuntocontrol>
                <latitud>{$data['latitud']}</latitud>
                <longitud>{$data['longitud']}</longitud>
                <fechallegada>{$data['fechallegada']}</fechallegada>
                <horallegada>{$data['horallegada']}</horallegada>
                <fechasalida>{$data['fechasalida']}</fechasalida>
                <horasalida>{$data['horasalida']}</horasalida>
            </variables>
            </root>
            XML;

        $response = Http::withHeaders([
                'Content-Type' => 'application/xml; charset=ISO-8859-1',
            ])
            ->withBody($xml, 'application/xml')
            ->post($url);

        $ok   = $response->successful();
        $body = $response->body();

        Cache::put('rndc:last_event_response_xml', $body, now()->addMinutes(15));

        $numeroAut = null;

        $bodyUtf8 = mb_convert_encoding($body, 'UTF-8', 'ISO-8859-1');
        $xmlResp  = @simplexml_load_string($bodyUtf8);

        if ($xmlResp) {
            // intentamos varios nombres posibles
            foreach (['ingresoid', 'nroautorizacion', 'autorizacion', 'numero_autorizacion'] as $tag) {
                if (isset($xmlResp->$tag)) {
                    $numeroAut = (string) $xmlResp->$tag;
                    break;
                }
            }
        }

        if (! $ok) {
            logger()->error('Error al enviar evento punto de control RNDC', [
                'status' => $response->status(),
                'body'   => $body,
            ]);
        }

        return [
            'ok'                 => $ok,
            'numero_autorizacion'=> $numeroAut,
            'xml_request'        => $xml,
            'xml_response'       => $body,
        ];
    }

    /**
     * Helper para usar el WS real y guardar todo en BD en un solo paso
     */
    public function syncManifiestosDesdeWebService(): int
    {
        $xml = $this->consultarManifiestos();

        if (!$xml) {
            return 0;
        }

        return $this->syncFromXml($xml);
    }

    private function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
