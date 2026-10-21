<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultaSunat extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'comprobante_id',
        'servicio',
        'parametros',
        'respuesta',
        'estado',
        'mensaje_error',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'parametros' => 'array',
            'respuesta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class);
    }
}
