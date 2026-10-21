<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Descargas Masivas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-4 flex justify-end">
                <a href="{{ route('descargas.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Nueva Descarga
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RUC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progreso</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creada</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($descargas as $descarga)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $descarga->periodo_ini }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $descarga->ruc }}</td>
                                <td class="px-6 py-4 text-sm capitalize">
                                    <span class="px-2 py-1 rounded text-xs
                                        @class([
                                            'bg-green-100 text-green-800' => $descarga->estado === 'completado',
                                            'bg-red-100 text-red-800' => $descarga->estado === 'error',
                                            'bg-yellow-100 text-yellow-800' => $descarga->enProceso(),
                                        ])">
                                        {{ $descarga->estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $descarga->procesados + $descarga->con_error }} / {{ $descarga->total_comprobantes }}
                                    @if ($descarga->con_error > 0)
                                        <span class="text-red-600">({{ $descarga->con_error }} errores)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $descarga->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('descargas.show', $descarga) }}" class="text-indigo-600 hover:text-indigo-900">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    No hay descargas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $descargas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
