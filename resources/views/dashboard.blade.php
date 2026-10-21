<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel Principal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Descargas Realizadas</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $totalDescargas }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Completadas</div>
                        <div class="mt-2 text-3xl font-bold text-green-600">{{ $descargasCompletadas }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">En Proceso</div>
                        <div class="mt-2 text-3xl font-bold text-yellow-600">{{ $descargasEnProceso }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Total Comprobantes</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $totalComprobantes }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Documentos Disponibles</div>
                        <div class="mt-2 text-3xl font-bold text-blue-600">{{ $documentosDisponibles }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Con Errores</div>
                        <div class="mt-2 text-3xl font-bold text-red-600">{{ $comprobantesConError }}</div>
                    </div>
                </div>
            </div>

            @if ($ultimasDescargas->isNotEmpty())
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Últimas Descargas</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Progreso</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($ultimasDescargas as $descarga)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $descarga->periodo_ini }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 capitalize">{{ $descarga->estado }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            {{ $descarga->procesados + $descarga->con_error }} / {{ $descarga->total_comprobantes }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm">
                                            <a href="{{ route('descargas.show', $descarga) }}" class="text-indigo-600 hover:text-indigo-900">Ver</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mt-8 flex gap-4">
                <a href="{{ route('descargas.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Nueva Descarga
                </a>
                <a href="{{ route('descargas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Ver Descargas
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
