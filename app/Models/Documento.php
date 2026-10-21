<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    use HasFactory;

    protected $fillable = [
        'comprobante_id',
        'tipo',
        'ruta',
        'nombre_original',
        'tamano',
        'hash_sha256',
        'origen',
        'estado',
        'fecha_obtencion',
    ];

    protected function casts(): array
    {
        return [
            'tamano' => 'integer',
            'fecha_obtencion' => 'datetime',
        ];
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class);
    }

    public function esDisponible(): bool
    {
        return $this->estado === 'disponible';
    }

    public function getRutaCompletaAttribute(): string
    {
        return storage_path('app/private/'.$this->ruta);
    }
}
