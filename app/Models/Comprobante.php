<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobante extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'descarga_masiva_id',
        'ruc_emisor',
        'razon_social_emisor',
        'ruc_receptor',
        'tipo_comprobante',
        'serie',
        'correlativo',
        'fecha_emision',
        'moneda',
        'importe_total',
        'datos_adicionales',
        'estado_consulta',
        'estado_validez',
        'fecha_ultima_consulta',
        'mensaje_error',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'importe_total' => 'decimal:2',
            'datos_adicionales' => 'array',
            'fecha_ultima_consulta' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function descargaMasiva(): BelongsTo
    {
        return $this->belongsTo(DescargaMasiva::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    public function consultasSunat(): HasMany
    {
        return $this->hasMany(ConsultaSunat::class);
    }

    public function numeroComprobante(): string
    {
        return "{$this->serie}-{$this->correlativo}";
    }

    public function tieneDocumento(string $tipo): bool
    {
        return $this->documentos()->where('tipo', $tipo)->where('estado', 'disponible')->exists();
    }
}
