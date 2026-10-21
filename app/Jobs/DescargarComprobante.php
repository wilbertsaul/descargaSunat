<?php

namespace App\Jobs;

use App\Models\Comprobante;
use App\Models\DescargaMasiva;
use App\Models\Documento;
use App\Services\JsonPe\JsonPeClientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DescargarComprobante implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $comprobanteId) {}

    public function handle(JsonPeClientService $jsonpe): void
    {
        $comprobante = Comprobante::with('descargaMasiva')->find($this->comprobanteId);

        if ($comprobante === null) {
            return;
        }

        $descarga = $comprobante->descargaMasiva;

        if ($descarga === null) {
            return;
        }

        try {
            $this->procesar($comprobante, $descarga, $jsonpe);

            $comprobante->update(['estado_consulta' => 'consultado']);
            $this->incrementar($descarga, 'procesados');
        } catch (Throwable $e) {
            $comprobante->update([
                'estado_consulta' => 'error',
                'mensaje_error' => $e->getMessage(),
            ]);
            $this->incrementar($descarga, 'con_error');
        }

        $this->empaquetarSiCorresponde($descarga);
    }

    private function procesar(Comprobante $comprobante, DescargaMasiva $descarga, JsonPeClientService $jsonpe): void
    {
        $base = $this->rutaBase($descarga, $comprobante);

        $xml = $jsonpe->descargarXml(
            $descarga->ruc,
            $comprobante->ruc_emisor,
            $comprobante->tipo_comprobante,
            $comprobante->serie,
            $comprobante->correlativo,
        );

        $this->guardar($comprobante, 'xml', "{$base}/{$comprobante->serie}-{$comprobante->correlativo}.xml", $xml['xml']);

        if (! empty($xml['cdr'])) {
            $this->guardar($comprobante, 'cdr', "{$base}/R-{$comprobante->serie}-{$comprobante->correlativo}.xml", $xml['cdr']);
        }

        $pdf = $jsonpe->descargarPdf(
            $descarga->ruc,
            $comprobante->ruc_emisor,
            $comprobante->tipo_comprobante,
            $comprobante->serie,
            $comprobante->correlativo,
        );

        $this->guardar($comprobante, 'pdf', "{$base}/{$comprobante->serie}-{$comprobante->correlativo}.pdf", $pdf);
    }

    private function guardar(Comprobante $comprobante, string $tipo, string $ruta, string $contenido): void
    {
        Storage::disk('local')->put($ruta, $contenido);

        Documento::create([
            'comprobante_id' => $comprobante->id,
            'tipo' => $tipo,
            'ruta' => $ruta,
            'nombre_original' => basename($ruta),
            'tamano' => strlen($contenido),
            'hash_sha256' => hash('sha256', $contenido),
            'origen' => 'servicio_oficial',
            'estado' => 'disponible',
            'fecha_obtencion' => now(),
        ]);
    }

    private function rutaBase(DescargaMasiva $descarga, Comprobante $comprobante): string
    {
        $numero = ltrim($comprobante->correlativo, '0') ?: '0';

        return "descargas/{$descarga->id}/{$comprobante->ruc_emisor}/{$comprobante->serie}-{$numero}";
    }

    private function incrementar(DescargaMasiva $descarga, string $campo): void
    {
        $descarga->increment($campo);
    }

    private function empaquetarSiCorresponde(DescargaMasiva $descarga): void
    {
        $descarga->refresh();

        if ($descarga->total_comprobantes <= 0) {
            return;
        }

        if ($descarga->procesados + $descarga->con_error < $descarga->total_comprobantes) {
            return;
        }

        $lock = Cache::lock("empaquetar_descarga_{$descarga->id}", 300);

        if ($lock->get()) {
            EmpaquetarPeriodo::dispatch($descarga->id);
        }
    }
}
