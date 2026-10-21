<?php

namespace App\Jobs;

use App\Models\Comprobante;
use App\Models\DescargaMasiva;
use App\Services\Sunat\SireRceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ListarPropuestaRce implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $descargaMasivaId) {}

    public function handle(SireRceService $sire): void
    {
        $descarga = DescargaMasiva::findOrFail($this->descargaMasivaId);

        $descarga->update([
            'estado' => 'listando',
            'iniciado_en' => $descarga->iniciado_en ?? now(),
        ]);

        try {
            $comprobantes = $sire->getPropuestaComprobantes($descarga->periodo_ini);

            $descarga->update(['total_comprobantes' => count($comprobantes)]);

            foreach ($comprobantes as $item) {
                $comprobante = Comprobante::create([
                    'user_id' => $descarga->user_id,
                    'descarga_masiva_id' => $descarga->id,
                    'ruc_emisor' => $item['ruc'],
                    'ruc_receptor' => $descarga->ruc,
                    'tipo_comprobante' => $item['tipo'],
                    'serie' => $item['serie'],
                    'correlativo' => $item['numero'],
                    'fecha_emision' => $this->parseFecha($item['fecha']),
                    'moneda' => 'PEN',
                    'importe_total' => (float) str_replace(',', '', $item['total']),
                    'estado_consulta' => 'pendiente',
                ]);

                DescargarComprobante::dispatch($comprobante->id);
            }

            $descarga->update(['estado' => 'descargando']);
        } catch (Throwable $e) {
            $descarga->update([
                'estado' => 'error',
                'mensaje_error' => $e->getMessage(),
                'finalizado_en' => now(),
            ]);

            throw $e;
        }
    }

    private function parseFecha(string $fecha): ?string
    {
        $fecha = trim($fecha);

        if ($fecha === '') {
            return null;
        }

        $dt = \DateTime::createFromFormat('d/m/Y', $fecha);

        return $dt !== false ? $dt->format('Y-m-d') : null;
    }
}
