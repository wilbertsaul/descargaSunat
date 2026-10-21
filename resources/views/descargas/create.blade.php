<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nueva Descarga Masiva') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-6 text-sm text-gray-600">
                        Se descargarán los comprobantes de <strong>compras (RCE)</strong> del período indicado.
                        Por cada comprobante se generan tres archivos: <strong>XML</strong>, <strong>CDR</strong> (cuando SUNAT lo incluye)
                        y <strong>PDF</strong>. Al finalizar podrás descargar un único ZIP del período.
                    </p>

                    <form method="POST" action="{{ route('descargas.store') }}">
                        @csrf

                        <div>
                            <x-input-label for="periodo" :value="__('Período (AAAA-MM)')" />
                            <x-text-input id="periodo" name="periodo" type="month" class="mt-1 block w-full"
                                :value="old('periodo')" required />
                            <x-input-error :messages="$errors->get('periodo')" class="mt-2" />
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <x-primary-button>{{ __('Iniciar Descarga') }}</x-primary-button>
                            <a href="{{ route('descargas.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
