<?php

namespace App\Services\JsonPe;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JsonPeClientService
{
    private string $baseUrl;

    private string $token;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.jsonpe.base_url', 'https://api.json.pe/api'), '/');
        $this->token = (string) config('services.jsonpe.token', '');
        $this->timeout = (int) config('services.jsonpe.timeout', 120);
    }

    /**
     * Descarga el ZIP (Base64) que contiene el XML y, cuando SUNAT lo incluye,
     * el CDR. El CDR es opcional: algunos comprobantes no lo traen en el ZIP.
     *
     * @return array{xml: string, cdr: ?string, xml_nombre: string, cdr_nombre: ?string}
     */
    public function descargarXml(string $ruc, string $proveedor, string $tipoDoc, string $serie, string $correlativo): array
    {
        $response = $this->post('/sunat/xml', [
            'ruc' => $ruc,
            'usuario' => (string) config('services.sunat.sol_principal_user'),
            'password' => (string) config('services.sunat.sol_principal_password'),
            'proveedor' => $proveedor,
            'tipo_doc' => $tipoDoc,
            'serie' => $serie,
            'correlativo' => $correlativo,
        ]);

        $base64 = $response->json('data.xml_base64');

        if (empty($base64)) {
            throw new RuntimeException('json.pe no devolvió el XML del comprobante.');
        }

        return $this->extraerZip($base64);
    }

    /**
     * Descarga el PDF oficial del comprobante (mismo cuerpo que descargarXml).
     */
    public function descargarPdf(string $ruc, string $proveedor, string $tipoDoc, string $serie, string $correlativo): string
    {
        $response = $this->post('/sunat/pdf', [
            'ruc' => $ruc,
            'usuario' => (string) config('services.sunat.sol_principal_user'),
            'password' => (string) config('services.sunat.sol_principal_password'),
            'proveedor' => $proveedor,
            'tipo_doc' => $tipoDoc,
            'serie' => $serie,
            'correlativo' => $correlativo,
        ]);

        $base64 = $response->json('data.pdf_base64');

        if (empty($base64)) {
            throw new RuntimeException('json.pe no devolvió el PDF del comprobante.');
        }

        return base64_decode($base64);
    }

    /**
     * Genera el PDF a partir de un XML (Base64) usando json.pe.
     */
    public function xmlToPdf(string $xmlContent): string
    {
        $response = $this->post('/sunat/xml-to-pdf', [
            'file_base64' => base64_encode($xmlContent),
        ]);

        $base64 = $response->json('data.pdf_base64');

        if (empty($base64)) {
            throw new RuntimeException('json.pe no devolvió el PDF del comprobante.');
        }

        return base64_decode($base64);
    }

    private function post(string $path, array $payload): Response
    {
        $response = Http::withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}{$path}", $payload);

        if ($response->failed()) {
            $message = $response->json('message')
                ?? $response->body()
                ?? 'HTTP '.$response->status();

            throw new RuntimeException("Error json.pe ({$path}): {$message}");
        }

        return $response;
    }

    /**
     * Extrae el XML (documento) y el CDR de un ZIP codificado en Base64.
     *
     * @return array{xml: string, cdr: ?string, xml_nombre: string, cdr_nombre: ?string}
     */
    private function extraerZip(string $base64): array
    {
        $bytes = base64_decode($base64, true);

        if ($bytes === false) {
            throw new RuntimeException('El contenido devuelto por json.pe no es Base64 válido.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'jsonpe_');
        file_put_contents($tmp, $bytes);

        $zip = new \ZipArchive;

        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            throw new RuntimeException('El contenido devuelto por json.pe no es un ZIP válido.');
        }

        $resultado = [
            'xml' => null,
            'cdr' => null,
            'xml_nombre' => null,
            'cdr_nombre' => null,
        ];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = $zip->getNameIndex($i);

            if ($nombre === false || ! preg_match('/\.xml$/i', $nombre)) {
                continue;
            }

            $contenido = $zip->getFromIndex($i);

            if ($contenido === false) {
                continue;
            }

            // El CDR empieza con "R-" (constancia de recepción).
            if (preg_match('/^R-/i', basename($nombre))) {
                $resultado['cdr'] = $contenido;
                $resultado['cdr_nombre'] = basename($nombre);
            } else {
                $resultado['xml'] = $contenido;
                $resultado['xml_nombre'] = basename($nombre);
            }
        }

        $zip->close();
        @unlink($tmp);

        if ($resultado['xml'] === null) {
            throw new RuntimeException('El ZIP devuelto por json.pe no contiene el XML.');
        }

        return $resultado;
    }
}
