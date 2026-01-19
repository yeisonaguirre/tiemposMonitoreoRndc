<?php

namespace App\Services;

use App\Models\RndcManifiesto;
use App\Models\RndcPuntoControl;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;
use Illuminate\Support\Facades\Cache;

class RndcService
{
    /**
     * Consulta el webservice real y devuelve el XML parseado
     */
    public function consultarManifiestos(?string $ingresoIdManifiesto = null): ?SimpleXMLElement
    {
        $url    = config('services.rndc.url');
        $user   = config('services.rndc.user');
        $pass   = config('services.rndc.pass');
        $nitgps = config('services.rndc.nitgps');

        $ingresoIdManifiesto = $ingresoIdManifiesto ? trim($ingresoIdManifiesto) : null;
        $documentoNodo = $ingresoIdManifiesto
            ? "<ingresoidmanifiesto>{$this->xmlEscape($ingresoIdManifiesto)}</ingresoidmanifiesto>"
            : "<manifiestos>nuevos</manifiestos>";

        $xmlRequest = <<<XML
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
                {$documentoNodo}
            </documento>
            </root>
            XML;

        try {
            $client = new \SoapClient($url, [
                'trace'              => true,
                'exceptions'         => true,
                'cache_wsdl'         => WSDL_CACHE_NONE,
                'connection_timeout' => 30, // antes 10
                'stream_context'     => stream_context_create([
                    'http' => [
                        'timeout' => 60,   // tiempo máximo para leer respuesta
                    ],
                ]),
            ]);

            $sendSoap = $client->AtenderMensajeRNDC($xmlRequest);

            if (is_string($sendSoap)) {
                $rawResponse = $sendSoap;
            } elseif (is_object($sendSoap) && isset($sendSoap->return)) {
                $rawResponse = $sendSoap->return;
            } else {
                logger()->error('Respuesta RNDC inesperada', ['resp' => $sendSoap]);
                return null;
            }

            // 👉 Guardar SIEMPRE la última respuesta en cache
            Cache::put(
                'rndc:last_response_xml',
                trim($rawResponse) !== '' ? $rawResponse : '<root><Error>No hay XML válido</Error></root>',
                now()->addMinutes(9)
            );

            // 4. Parsear la respuesta a XML seguro
            $xml = $this->xmlSafeParse($rawResponse);

            if ($xml === false) {
                logger()->error('RNDC: XML inválido en respuesta', ['raw' => $rawResponse]);
                throw new \Exception('La respuesta del RNDC no es un XML válido.');
            }

            if (isset($xml->ErrorMSG)) {
                $mensaje = trim((string) $xml->ErrorMSG);

                logger()->error('RNDC: Error recibido', [
                    'error' => $mensaje,
                    // Opcional: también podrías guardar aparte
                    // 'xml'   => $rawResponse,
                ]);

                // Ya el XML quedó cacheado arriba
                throw new \Exception('Error RNDC: ' . $mensaje);
            }

            return $xml;

        } catch (\SoapFault $e) {
            logger()->error('Error SOAP AtenderMensajeRNDC', [
                'code'      => $e->faultcode ?? null,
                'string'    => $e->faultstring ?? $e->getMessage(),
                'request'   => isset($client) ? $client->__getLastRequest() : null,
                'response'  => isset($client) ? $client->__getLastResponse() : null,
                'headers'   => isset($client) ? $client->__getLastResponseHeaders() : null,
            ]);

            throw new \Exception(
                'Error de comunicación con RNDC: ' . ($e->faultstring ?? $e->getMessage())
            );
        }
    }

    /**
     * Escapa valores para XML (evita romper el XML si hay &, <, etc.)
     */
    private function xmlEscape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'ISO-8859-1');
    }

    /**
     * Versión "segura" de simplexml_load_string, igual a tu xml_safe_parse
     */
    private function xmlSafeParse(string $xmlString): SimpleXMLElement|false
    {
        if (trim($xmlString) === '') {
            return false;
        }

        libxml_use_internal_errors(true);

        // Normalizar a UTF-8 si viene en ISO-8859-1
        $xmlUtf8 = mb_convert_encoding($xmlString, 'UTF-8', 'ISO-8859-1,UTF-8');

        $xml = simplexml_load_string($xmlUtf8);

        if ($xml === false) {
            foreach (libxml_get_errors() as $err) {
                logger()->error('XML error RNDC: ' . $err->message);
            }
            libxml_clear_errors();
            return false;
        }

        libxml_clear_errors();
        return $xml;
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

        DB::transaction(function () use ($xml, &$count) {

            foreach ($xml->documento as $doc) {

                $ingresoId = trim((string) $doc->ingresoidmanifiesto);

                if ($ingresoId === '') {
                    // Si viene un documento sin ingresoidmanifiesto lo saltamos
                    continue;
                }

                $manifiesto = RndcManifiesto::updateOrCreate(
                    ['ingresoidmanifiesto' => $ingresoId],
                    [
                        'numnitempresatransporte'   => trim((string) $doc->numnitempresatransporte),
                        'fechaexpedicionmanifiesto' => $this->parseDate((string) $doc->fechaexpedicionmanifiesto),
                        'codigoempresa'             => trim((string) $doc->codigoempresa),
                        'nummanifiestocarga'        => trim((string) $doc->nummanifiestocarga),
                        'numplaca'                  => trim((string) $doc->numplaca),

                        // Si quieres guardar el XML del documento puntual:
                        // 'xml_ultima_respuesta'      => $doc->asXML(),
                    ]
                );

                // Limpia puntos de control previos para este manifiesto
                $manifiesto->puntosControl()->delete();

                if (isset($doc->puntoscontrol->puntocontrol)) {
                    foreach ($doc->puntoscontrol->puntocontrol as $pc) {

                        $latitud  = trim((string) $pc->latitud);
                        $longitud = trim((string) $pc->longitud);
                        $tiempo   = trim((string) $pc->tiempopactado);

                        RndcPuntoControl::create([
                            'rndc_manifiesto_id' => $manifiesto->id,
                            'codpuntocontrol'    => $this->toNullableInt($pc->codpuntocontrol),
                            'codmunicipio'       => trim((string) $pc->codmunicipio),
                            'direccion'          => trim((string) $pc->direccion),
                            'fechacita'          => $this->parseDate((string) $pc->fechacita),
                            'horacita'           => trim((string) $pc->horacita),
                            'latitud'            => ($latitud  !== '' ? (float) $latitud  : null),
                            'longitud'           => ($longitud !== '' ? (float) $longitud : null),
                            'tiempopactado'      => ($tiempo   !== '' ? (int) $tiempo   : null),
                        ]);
                    }
                }

                $count++;
            }
        });

        return $count;
    }

    /**
     * Convierte un valor SimpleXMLElement a int o null si viene vacío.
     */
    protected function toNullableInt($value): ?int
    {
        $v = trim((string) $value);
        return $v === '' ? null : (int) $v;
    }

    public function enviarEventoPuntoControl(array $data): array
    {
        $url    = config('services.rndc.url');
        $user   = config('services.rndc.user');
        $pass   = config('services.rndc.pass');
        $nitgps = config('services.rndc.nitgps');

        $xmlRequest = <<<XML
    <?xml version='1.0' encoding='iso-8859-1' ?>
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

        try {
            $client = new \SoapClient($url, [
                'trace'              => true,
                'exceptions'         => true,
                'cache_wsdl'         => WSDL_CACHE_NONE,
                'connection_timeout' => 30, // antes 10
                'stream_context'     => stream_context_create([
                    'http' => [
                        'timeout' => 60,   // tiempo máximo para leer respuesta
                    ],
                ]),
            ]);

            $sendSoap = $client->AtenderMensajeRNDC($xmlRequest);

            // La respuesta puede venir como string XML o como objeto con ->return
            $rawResponse = is_string($sendSoap)
                ? $sendSoap
                : ($sendSoap->return ?? '');

            $xml = $this->xmlSafeParse($rawResponse);

            if ($xml === false) {
                logger()->error('RNDC: No se pudo parsear XML de respuesta', [
                    'xml_response' => $rawResponse,
                ]);
                throw new \Exception('No se pudo interpretar la respuesta de RNDC');
            }

            // 1) Verificar si viene error
            if (isset($xml->ErrorMSG)) {
                $mensaje = trim((string) $xml->ErrorMSG);

                logger()->error('RNDC: Error recibido', [
                    'error'        => $mensaje,
                    'xml_response' => $rawResponse,
                ]);

                throw new \Exception('Error RNDC: ' . $mensaje);
            }

            // 2) Extraer el ingresoid del root
            $ingresoId = null;
            if (isset($xml->ingresoid)) {
                $ingresoId = (string) $xml->ingresoid;
            }

            // Opcional: si en algún escenario lo devuelven envuelto en <documento>
            if (!$ingresoId && isset($xml->documento->ingresoid)) {
                $ingresoId = (string) $xml->documento->ingresoid;
            }

            return [
                'ok'           => !empty($ingresoId),
                'ingresoid'    => $ingresoId,
                'xml_request'  => $xmlRequest,
                'xml_response' => $rawResponse,
            ];

        } catch (\SoapFault $e) {
            logger()->error('Error SOAP evento RNDC', [
                'code'      => $e->faultcode ?? null,
                'string'    => $e->faultstring ?? $e->getMessage(),
                'request'   => isset($client) ? $client->__getLastRequest() : null,
                'response'  => isset($client) ? $client->__getLastResponse() : null,
                'headers'   => isset($client) ? $client->__getLastResponseHeaders() : null,
            ]);

            throw new \Exception(
                'Error de comunicación con RNDC: ' . ($e->faultstring ?? $e->getMessage())
            );
        }
    }

    /**
     * Helper para usar el WS real y guardar todo en BD en un solo paso
     */
    public function syncManifiestosDesdeWebService(?string $ingresoIdManifiesto = null): int
    {
        try {
            $xml = $this->consultarManifiestos($ingresoIdManifiesto); // ahora viene desde SOAP

            if (!$xml) {
                return 0;
            }

            return $this->syncFromXml($xml);
        } catch (\Exception $e) {
            throw new \Exception('No se pudo consultar RNDC: ' . $e->getMessage());
        }
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
