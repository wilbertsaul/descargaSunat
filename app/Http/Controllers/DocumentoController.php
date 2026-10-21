<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoController extends Controller
{
    public function descargar(Documento $documento): StreamedResponse
    {
        $this->authorize('view', $documento->comprobante);

        abort_unless($documento->esDisponible(), 404);
        abort_unless(Storage::disk('local')->exists($documento->ruta), 404);

        return response()->streamDownload(function () use ($documento) {
            echo Storage::disk('local')->get($documento->ruta);
        }, $documento->nombre_original);
    }
}
