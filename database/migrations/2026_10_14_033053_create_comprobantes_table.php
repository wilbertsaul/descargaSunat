<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ruc_emisor', 11);
            $table->string('razon_social_emisor')->nullable();
            $table->string('ruc_receptor', 11);
            $table->string('tipo_comprobante', 20);
            $table->string('serie', 10);
            $table->string('correlativo', 20);
            $table->date('fecha_emision');
            $table->string('moneda', 3)->default('PEN');
            $table->decimal('importe_total', 15, 2);
            $table->json('datos_adicionales')->nullable();
            $table->enum('estado_consulta', ['pendiente', 'consultado', 'error'])->default('pendiente');
            $table->enum('estado_validez', ['valido', 'invalido', 'no_verificado'])->default('no_verificado')->nullable();
            $table->timestamp('fecha_ultima_consulta')->nullable();
            $table->text('mensaje_error')->nullable();
            $table->timestamps();

            $table->unique([
                'ruc_emisor',
                'tipo_comprobante',
                'serie',
                'correlativo',
            ], 'comprobantes_unique_identity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
