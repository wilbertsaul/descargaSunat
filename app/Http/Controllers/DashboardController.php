<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\DescargaMasiva;
use App\Models\Documento;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $totalDescargas = DescargaMasiva::where('user_id', $userId)->count();
        $descargasCompletadas = DescargaMasiva::where('user_id', $userId)
            ->where('estado', 'completado')
            ->count();
        $descargasEnProceso = DescargaMasiva::where('user_id', $userId)
            ->whereIn('estado', ['pendiente', 'listando', 'descargando', 'empaquetando'])
            ->count();
        $totalComprobantes = Comprobante::where('user_id', $userId)->count();
        $documentosDisponibles = Documento::whereHas('comprobante', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('estado', 'disponible')->count();
        $comprobantesConError = Comprobante::where('user_id', $userId)
            ->where('estado_consulta', 'error')
            ->count();

        $ultimasDescargas = DescargaMasiva::where('user_id', $userId)
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalDescargas',
            'descargasCompletadas',
            'descargasEnProceso',
            'totalComprobantes',
            'documentosDisponibles',
            'comprobantesConError',
            'ultimasDescargas'
        ));
    }
}
