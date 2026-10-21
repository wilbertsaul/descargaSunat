<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descargas_masivas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ruc', 11);
            $table->string('periodo_ini', 6);
            $table->string('periodo_fin', 6);
            $table->enum('estado', ['pendiente', 'listando', 'descargando', 'empaquetando', 'completado', 'error'])
                ->default('pendiente');
            $table->unsignedInteger('total_comprobantes')->default(0);
            $table->unsignedInteger('procesados')->default(0);
            $table->unsignedInteger('con_error')->default(0);
            $table->string('ruta_zip')->nullable();
            $table->text('mensaje_error')->nullable();
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'estado']);
        });

        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropUnique('comprobantes_unique_identity');

            $table->foreignId('descarga_masiva_id')
                ->nullable()
                ->after('user_id')
                ->constrained('descargas_masivas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropForeign(['descarga_masiva_id']);
            $table->dropColumn('descarga_masiva_id');

            $table->unique([
                'ruc_emisor',
                'tipo_comprobante',
                'serie',
                'correlativo',
            ], 'comprobantes_unique_identity');
        });

        Schema::dropIfExists('descargas_masivas');
    }
};
