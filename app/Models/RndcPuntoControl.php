<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RndcPuntoControl extends Model
{
    protected $table = 'rndc_puntos_control';

    protected $fillable = [
        'rndc_manifiesto_id',
        'codpuntocontrol',
        'codmunicipio',
        'direccion',
        'fechacita',
        'horacita',
        'latitud',
        'longitud',
        'tiempopactado',

        'fecha_llegada',
        'hora_llegada',
        'fecha_salida',
        'hora_salida',
        'evento_enviado_at',
        'numero_autorizacion',
        'finalizado',
        'xml_solicitud',
        'xml_respuesta',
    ];

    protected $casts = [
        'fechacita'        => 'date',
        'fecha_llegada'    => 'date',
        'fecha_salida'     => 'date',
        'evento_enviado_at'=> 'datetime',
        'latitud'          => 'float',
        'longitud'         => 'float',
        'finalizado'       => 'boolean',
    ];

    public function manifiesto(): BelongsTo
    {
        return $this->belongsTo(RndcManifiesto::class, 'rndc_manifiesto_id');
    }
}
