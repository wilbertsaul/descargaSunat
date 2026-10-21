<?php

namespace App\Http\Controllers;

use App\Jobs\ListarPropuestaRce;
use App\Models\DescargaMasiva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DescargaMasivaController extends Controller
{
    public function index(Request $request): View
    {
        $descargas = DescargaMasiva::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('descargas.index', compact('descargas'));
    }

    public function create(): View
    {
        return view('descargas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'periodo' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ], [
            'periodo.required' => 'Debes indicar el período.',
            'periodo.regex' => 'El período debe tener el formato AAAA-MM (ej. 2026-09).',
        ]);

        $periodo = str_replace('-', '', $data['periodo']);

        if ($periodo > now()->format('Ym')) {
            throw ValidationException::withMessages([
                'periodo' => 'El período no puede ser futuro.',
            ]);
        }

        $descarga = DescargaMasiva::create([
            'user_id' => $request->user()->id,
            'ruc' => (string) config('services.sunat.ruc'),
            'periodo_ini' => $periodo,
            'periodo_fin' => $periodo,
            'estado' => 'pendiente',
        ]);

        ListarPropuestaRce::dispatch($descarga->id);

        return redirect()
            ->route('descargas.show', $descarga)
            ->with('status', 'Descarga iniciada. La propuesta se está procesando.');
    }

    public function show(Request $request, DescargaMasiva $descarga): View
    {
        abort_unless($descarga->user_id === $request->user()->id, 403);

        $comprobantes = $descarga->comprobantes()
            ->with('documentos')
            ->orderBy('ruc_emisor')
            ->orderBy('serie')
            ->orderBy('correlativo')
            ->paginate(50);

        return view('descargas.show', compact('descarga', 'comprobantes'));
    }

    public function estado(Request $request, DescargaMasiva $descarga): JsonResponse
    {
        abort_unless($descarga->user_id === $request->user()->id, 403);

        return response()->json([
            'estado' => $descarga->estado,
            'total_comprobantes' => $descarga->total_comprobantes,
            'procesados' => $descarga->procesados,
            'con_error' => $descarga->con_error,
            'porcentaje' => $descarga->porcentaje(),
            'zip_disponible' => $descarga->completado() && ! empty($descarga->ruta_zip),
        ]);
    }

    public function descargarZip(Request $request, DescargaMasiva $descarga): StreamedResponse
    {
        abort_unless($descarga->user_id === $request->user()->id, 403);
        abort_unless($descarga->completado() && $descarga->ruta_zip, 404);

        $ruta = Storage::disk('local')->path($descarga->ruta_zip);

        abort_unless(is_file($ruta), 404);

        return response()->streamDownload(function () use ($ruta) {
            readfile($ruta);
        }, "comprobantes-{$descarga->periodo_ini}.zip", [
            'Content-Type' => 'application/zip',
        ]);
    }
}
