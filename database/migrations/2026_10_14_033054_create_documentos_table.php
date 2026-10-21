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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['xml', 'cdr', 'pdf']);
            $table->string('ruta');
            $table->string('nombre_original');
            $table->unsignedBigInteger('tamano')->nullable();
            $table->string('hash_sha256', 64)->nullable();
            $table->enum('origen', ['servicio_oficial', 'generado', 'carga_manual']);
            $table->enum('estado', ['disponible', 'pendiente', 'error'])->default('pendiente');
            $table->timestamp('fecha_obtencion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
