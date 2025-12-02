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
        $wsdl   = config('services.rndc.wsdl');
        $user   = config('services.rndc.user');
        $pass   = config('services.rndc.pass');
        $nitgps = config('services.rndc.nitgps');

        // 1. Armar XML EXACTAMENTE como te piden
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
    <manifiestos>nuevos</manifiestos>
  </documento>
</root>
XML;

        // Opcional para debug:
        // logger()->info('RNDC XML REQUEST', ['xml' => $xmlRequest]);

        try {
            // 2. Crear SoapClient apuntando al WSDL
            $client = new \SoapClient($wsdl, [
                'trace'      => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                // timeouts opcionales:
                'connection_timeout' => 10,
            ]);

            // 3. Llamar al método AtenderMensajeRNDC
            //    El WSDL dice que el parámetro se llama "Request" y es xs:string
            $sendSoap = $client->AtenderMensajeRNDC($xmlRequest);

            // Puede venir:
            //  - como string directo
            //  - o como objeto con propiedad ->return
            if (is_string($sendSoap)) {
                $rawResponse = $sendSoap;
            } elseif (is_object($sendSoap) && isset($sendSoap->return)) {
                $rawResponse = $sendSoap->return;
            } else {
                // Forma inesperada
                // logger()->error('Respuesta RNDC inesperada', ['resp' => $sendSoap]);
                return null;
            }

            // 4. Parsear la respuesta a XML seguro
            $xml = $this->xmlSafeParse($rawResponse);

            if ($xml === false) {
                return null;
            }

            return $xml;

        } catch (\SoapFault $e) {
            // Loguear el error SOAP
            logger()->error('Error SOAP AtenderMensajeRNDC', [
                'code'    => $e->faultcode ?? null,
                'string'  => $e->faultstring ?? $e->getMessage(),
            ]);

            return null;
        }
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
        $wsdl   = config('services.rndc.wsdl');
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

    dd($xmlRequest);die;

        try {
            $client = new \SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);

            $sendSoap = $client->AtenderMensajeRNDC($xmlRequest);

            $rawResponse = is_string($sendSoap)
                ? $sendSoap
                : ($sendSoap->return ?? '');

            $xml = $this->xmlSafeParse($rawResponse);

            // Aquí ya puedes extraer el nro de autorización de $xml
            $numeroAut = null;
            if ($xml && isset($xml->documento->nroautorizacion)) {
                $numeroAut = (string) $xml->documento->nroautorizacion;
            }

            return [
                'ok'                 => $xml !== false,
                'numero_autorizacion'=> $numeroAut,
                'xml_request'        => $xmlRequest,
                'xml_response'       => $rawResponse,
            ];

        } catch (\SoapFault $e) {
            logger()->error('Error SOAP evento RNDC', [
                'code'   => $e->faultcode ?? null,
                'string' => $e->faultstring ?? $e->getMessage(),
            ]);

            return [
                'ok'                 => false,
                'numero_autorizacion'=> null,
                'xml_request'        => $xmlRequest,
                'xml_response'       => null,
            ];
        }
    }

    /**
     * Helper para usar el WS real y guardar todo en BD en un solo paso
     */
    public function syncManifiestosDesdeWebService(): int
    {
        $xml = $this->consultarManifiestos(); // ahora viene desde SOAP

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
