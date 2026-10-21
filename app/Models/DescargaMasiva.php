<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DescargaMasiva extends Model
{
    use HasFactory;

    protected $table = 'descargas_masivas';

    protected $fillable = [
        'user_id',
        'ruc',
        'periodo_ini',
        'periodo_fin',
        'estado',
        'total_comprobantes',
        'procesados',
        'con_error',
        'ruta_zip',
        'mensaje_error',
        'iniciado_en',
        'finalizado_en',
    ];

    protected function casts(): array
    {
        return [
            'procesados' => 'integer',
            'con_error' => 'integer',
            'total_comprobantes' => 'integer',
            'iniciado_en' => 'datetime',
            'finalizado_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class);
    }

    public function enProceso(): bool
    {
        return in_array($this->estado, ['pendiente', 'listando', 'descargando', 'empaquetando'], true);
    }

    public function completado(): bool
    {
        return $this->estado === 'completado';
    }

    public function porcentaje(): int
    {
        if ($this->total_comprobantes <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->procesados + $this->con_error) / $this->total_comprobantes * 100));
    }
}
