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
        Schema::create('consultas_sunat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('servicio');
            $table->json('parametros')->nullable();
            $table->json('respuesta')->nullable();
            $table->enum('estado', ['exito', 'fallo', 'timeout']);
            $table->text('mensaje_error')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultas_sunat');
    }
};
