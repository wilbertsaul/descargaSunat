<?php

namespace App\Jobs;

use App\Models\DescargaMasiva;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class EmpaquetarPeriodo implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $descargaMasivaId) {}

    public function handle(): void
    {
        $descarga = DescargaMasiva::findOrFail($this->descargaMasivaId);

        $descarga->update(['estado' => 'empaquetando']);

        try {
            $zipRelativo = "descargas/periodo_{$descarga->periodo_ini}_{$descarga->id}.zip";
            $zipRuta = Storage::disk('local')->path($zipRelativo);

            if (! is_dir(dirname($zipRuta))) {
                mkdir(dirname($zipRuta), 0775, true);
            }

            $origen = Storage::disk('local')->path("descargas/{$descarga->id}");

            $zip = new ZipArchive;
            $zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if (is_dir($origen)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($origen, RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $archivo) {
                    $rutaLocal = $archivo->getPathname();
                    $relativa = str_replace($origen.DIRECTORY_SEPARATOR, '', $rutaLocal);
                    $relativa = str_replace('\\', '/', $relativa);

                    $zip->addFile($rutaLocal, $relativa);
                }
            }

            $zip->close();

            $descarga->update([
                'estado' => 'completado',
                'ruta_zip' => $zipRelativo,
                'finalizado_en' => now(),
            ]);
        } catch (Throwable $e) {
            $descarga->update([
                'estado' => 'error',
                'mensaje_error' => $e->getMessage(),
                'finalizado_en' => now(),
            ]);

            throw $e;
        }
    }
}
