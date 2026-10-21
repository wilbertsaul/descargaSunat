<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas_sunat', function (Blueprint $table) {
            $table->dropForeign(['comprobante_id']);
            $table->foreignId('comprobante_id')->nullable()->change();
            $table->foreign('comprobante_id')->references('id')->on('comprobantes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultas_sunat', function (Blueprint $table) {
            $table->dropForeign(['comprobante_id']);
            $table->foreignId('comprobante_id')->nullable(false)->change();
            $table->foreign('comprobante_id')->references('id')->on('comprobantes')->cascadeOnDelete();
        });
    }
};
