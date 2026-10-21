<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Descarga del Período') }} {{ $descarga->periodo_ini }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"
                @if ($descarga->enProceso()) data-estado-url="{{ route('descargas.estado', $descarga) }}" @endif>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Estado</div>
                            <div id="estado-texto" class="text-lg font-semibold capitalize">{{ $descarga->estado }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Progreso</div>
                            <div id="progreso-texto" class="text-lg font-semibold">
                                {{ $descarga->procesados + $descarga->con_error }} / {{ $descarga->total_comprobantes }}
                                @if ($descarga->con_error > 0)
                                    <span class="text-red-600 text-sm">({{ $descarga->con_error }} errores)</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 w-full bg-gray-200 rounded-full h-3">
                        <div id="progreso-barra" class="bg-blue-600 h-3 rounded-full transition-all"
                            style="width: {{ $descarga->porcentaje() }}%"></div>
                    </div>

                    @if ($descarga->mensaje_error)
                        <div class="mt-4 p-3 bg-red-100 text-red-800 rounded-md text-sm">
                            {{ $descarga->mensaje_error }}
                        </div>
                    @endif

                    <div class="mt-6">
                        @if ($descarga->completado() && $descarga->ruta_zip)
                            <a href="{{ route('descargas.zip', $descarga) }}"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                Descargar ZIP del período
                            </a>
                        @else
                            <span class="text-sm text-gray-500">El ZIP estará disponible cuando finalice el proceso.</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comprobante</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documentos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($comprobantes as $comprobante)
                            <tr>
                                <td class="px-6 py-4 text-sm">
                                    <div class="text-gray-900">{{ $comprobante->razon_social_emisor ?? $comprobante->ruc_emisor }}</div>
                                    <div class="text-gray-500">{{ $comprobante->ruc_emisor }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $comprobante->tipo_comprobante }} {{ $comprobante->serie }}-{{ $comprobante->correlativo }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ optional($comprobante->fecha_emision)->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ number_format((float) $comprobante->importe_total, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @php
                                        $docs = $comprobante->documentos->keyBy('tipo');
                                    @endphp
                                    <div class="flex gap-2">
                                        @foreach (['xml', 'cdr', 'pdf'] as $tipo)
                                            @if ($docs->has($tipo) && $docs[$tipo]->esDisponible())
                                                <a href="{{ route('documentos.descargar', $docs[$tipo]) }}"
                                                    class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 hover:bg-blue-200 uppercase">
                                                    {{ $tipo }}
                                                </a>
                                            @else
                                                <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-400 uppercase"
                                                    title="{{ $comprobante->estado_consulta === 'error' ? $comprobante->mensaje_error : 'En proceso' }}">
                                                    {{ $tipo }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Aún no hay comprobantes. La propuesta puede estar procesándose en SUNAT.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $comprobantes->links() }}
            </div>
        </div>
    </div>

    @if ($descarga->enProceso())
        <script>
            (function () {
                const contenedor = document.querySelector('[data-estado-url]');
                if (!contenedor) return;
                const url = contenedor.getAttribute('data-estado-url');

                async function actualizar() {
                    try {
                        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!resp.ok) return;
                        const data = await resp.json();

                        const texto = document.getElementById('estado-texto');
                        const progresoTexto = document.getElementById('progreso-texto');
                        const barra = document.getElementById('progreso-barra');

                        if (texto) texto.textContent = data.estado;
                        if (progresoTexto) {
                            progresoTexto.textContent = (data.procesados + data.con_error) + ' / ' + data.total_comprobantes;
                        }
                        if (barra) barra.style.width = data.porcentaje + '%';

                        if (data.estado === 'completado' || data.estado === 'error') {
                            window.location.reload();
                            return;
                        }
                    } catch (e) {
                        // silencioso
                    }
                    setTimeout(actualizar, 4000);
                }

                setTimeout(actualizar, 3000);
            })();
        </script>
    @endif
</x-app-layout>
