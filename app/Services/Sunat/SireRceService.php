<?php

namespace App\Services\Sunat;

use Illuminate\Support\Facades\Http;

class SireRceService
{
    private string $baseUrl;

    private SunatAuthService $authService;

    public function __construct(SunatAuthService $authService)
    {
        $this->baseUrl = rtrim(config('services.sunat.sire_base_url', 'https://api-sire.sunat.gob.pe'), '/');
        $this->authService = $authService;
    }

    public function getPeriodos(): array
    {
        $token = $this->authService->getTokenSire();

        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(60)
            ->get("{$this->baseUrl}/v1/contribuyente/migeigv/libros/rvierce/padron/web/omisos/080000/periodos");

        if ($response->failed()) {
            throw new \RuntimeException(
                'Error consultando períodos SUNAT: HTTP '.$response->status().' - '.$response->body()
            );
        }

        return $response->json();
    }

    /**
     * Parsea el TXT pipe-delimited de propuesta RCE y devuelve un array de
     * comprobantes normalizados. La primera línea es el encabezado.
     *
     * @return array<int, array{ruc:string,tipo:string,serie:string,numero:string,fecha:string,total:string}>
     */
    public function parsePropuestaTxt(string $contenido): array
    {
        $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
        $comprobantes = [];

        foreach ($lineas as $indice => $linea) {
            if ($indice === 0 || trim($linea) === '') {
                continue;
            }

            $campos = explode('|', $linea);

            if (count($campos) < 13) {
                continue;
            }

            $comprobantes[] = [
                'ruc_receptor' => trim($campos[0]),
                'ruc' => trim($campos[12]),
                'tipo' => trim($campos[6]),
                'serie' => trim($campos[7]),
                'numero' => trim($campos[9]),
                'fecha' => trim($campos[4]),
                'total' => trim($campos[24] ?? ''),
            ];
        }

        return $comprobantes;
    }

    public function exportarPropuesta(string $periodo): array
    {
        $token = $this->authService->getTokenSire();

        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(120)
            ->get("{$this->baseUrl}/v1/contribuyente/migeigv/libros/rce/propuesta/web/propuesta/{$periodo}/exportacioncomprobantepropuesta", [
                'codTipoArchivo' => 0,
                'codOrigenEnvio' => 2,
            ]);

        if ($response->failed()) {
            $body = $response->json();
            $errorMsg = $body['msg'] ?? $body['message'] ?? 'HTTP '.$response->status();
            throw new \RuntimeException(
                "Error exportando propuesta (período {$periodo}): {$errorMsg}"
            );
        }

        return $response->json();
    }

    /**
     * Obtiene y parsea la propuesta RCE del período.
     *
     * @return array<int, array{ruc:string,tipo:string,serie:string,numero:string,fecha:string,total:string}>
     */
    public function getPropuestaComprobantes(string $periodo): array
    {
        return $this->parsePropuestaTxt($this->obtenerPropuestaTxt($periodo));
    }

    /**
     * Flujo completo: solicita la exportación de la propuesta del período,
     * espera el ticket y descarga el TXT generado.
     */
    public function obtenerPropuestaTxt(string $periodo): string
    {
        $exportacion = $this->exportarPropuesta($periodo);
        $numTicket = $exportacion['numTicket'] ?? '';

        if (empty($numTicket)) {
            throw new \RuntimeException(
                "SUNAT no devolvió numTicket para el período {$periodo}: ".json_encode($exportacion)
            );
        }

        $ticket = $this->waitForTicket($periodo, (string) $numTicket);

        if (empty($ticket['nomArchivoReporte'])) {
            throw new \RuntimeException(
                "SUNAT no devolvió el archivo de propuesta para el período {$periodo}: ".json_encode($ticket)
            );
        }

        return $this->descargarArchivo(
            $periodo,
            (string) $numTicket,
            $ticket['nomArchivoReporte'],
            $ticket['codTipoArchivoReporte'],
            $ticket['codProceso'],
        );
    }

    public function getTicketStatus(string $periodo, string $numTicket, int $pagina = 1, int $porPagina = 20): array
    {
        $token = $this->authService->getTokenSire();

        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(60)
            ->get("{$this->baseUrl}/v1/contribuyente/migeigv/libros/rvierce/gestionprocesosmasivos/web/masivo/consultaestadotickets", [
                'perIni' => $periodo,
                'perFin' => $periodo,
                'page' => $pagina,
                'perPage' => $porPagina,
                'numTicket' => $numTicket,
            ]);

        if ($response->failed()) {
            $body = $response->json();
            $errorMsg = $body['msg'] ?? $body['message'] ?? 'HTTP '.$response->status();
            throw new \RuntimeException(
                "Error consultando ticket {$numTicket}: {$errorMsg}"
            );
        }

        return $response->json();
    }

    /**
     * Espera a que el ticket finalice y devuelve el primer registro con el
     * archivo de reporte generado (nomArchivoReporte / codTipoArchivoReporte).
     */
    public function waitForTicket(string $periodo, string $numTicket, int $maxAttempts = 30, int $intervalSeconds = 5): array
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $data = $this->getTicketStatus($periodo, $numTicket);
            $registros = $data['registros'] ?? [];

            foreach ($registros as $registro) {
                $detalle = $registro['detalleTicket'][0] ?? [];
                $estado = $detalle['desEstadoEnvio'] ?? $registro['desEstadoProceso'] ?? '';
                $codEstado = $detalle['codEstadoEnvio'] ?? $registro['codEstadoProceso'] ?? '';

                if (in_array($estado, ['Terminado', 'terminado'], true)) {
                    $archivo = $registro['archivoReporte'][0] ?? [];

                    return [
                        'numTicket' => $numTicket,
                        'nomArchivoReporte' => $archivo['nomArchivoReporte'] ?? '',
                        'codTipoArchivoReporte' => $archivo['codTipoAchivoReporte'] ?? null,
                        'codProceso' => $registro['codProceso'] ?? null,
                    ];
                }

                if (in_array($estado, ['Error', 'error'], true) || in_array($codEstado, [3, 4], true)) {
                    throw new \RuntimeException(
                        "El ticket {$numTicket} terminó con error: ".json_encode($registro)
                    );
                }
            }

            sleep($intervalSeconds);
            $attempts++;
        }

        throw new \RuntimeException(
            "El ticket {$numTicket} no terminó después de {$maxAttempts} intentos."
        );
    }

    public function descargarArchivo(string $periodo, string $numTicket, string $nombreArchivo, ?string $codTipoArchivoReporte, ?string $codProceso): string
    {
        $token = $this->authService->getTokenSire();

        $params = [
            'nomArchivoReporte' => $nombreArchivo,
            'codTipoArchivoReporte' => $codTipoArchivoReporte,
            'perTributario' => $periodo,
            'codProceso' => $codProceso,
            'numTicket' => $numTicket,
            'codLibro' => '080000',
        ];

        $response = Http::withToken($token)
            ->timeout(120)
            ->get("{$this->baseUrl}/v1/contribuyente/migeigv/libros/rvierce/gestionprocesosmasivos/web/masivo/archivoreporte", $params);

        if ($response->failed()) {
            $body = $response->json();
            $errorMsg = $body['msg'] ?? $body['message'] ?? 'HTTP '.$response->status();
            throw new \RuntimeException(
                "Error descargando archivo {$nombreArchivo}: {$errorMsg}"
            );
        }

        return $this->extraerReporte($response->body());
    }

    /**
     * El endpoint devuelve un ZIP con el TXT de propuesta dentro. Si no es ZIP,
     * devuelve el cuerpo tal cual (TXT plano).
     */
    private function extraerReporte(string $contenido): string
    {
        if (strncmp($contenido, 'PK', 2) !== 0) {
            return $contenido;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'sire_');
        file_put_contents($tmp, $contenido);

        $zip = new \ZipArchive;

        if ($zip->open($tmp) !== true) {
            @unlink($tmp);

            return $contenido;
        }

        $txt = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = $zip->getNameIndex($i);

            if ($nombre !== false && preg_match('/\.txt$/i', $nombre)) {
                $txt = $zip->getFromIndex($i);
                break;
            }
        }

        $zip->close();
        @unlink($tmp);

        if ($txt === false || $txt === null) {
            throw new \RuntimeException('El archivo de propuesta descargado no contiene un TXT.');
        }

        return $txt;
    }
}
