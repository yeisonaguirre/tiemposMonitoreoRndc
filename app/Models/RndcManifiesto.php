<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RndcManifiesto extends Model
{
    protected $table = 'rndc_manifiestos';

    protected $fillable = [
        'ingresoidmanifiesto',
        'numnitempresatransporte',
        'fechaexpedicionmanifiesto',
        'codigoempresa',
        'nummanifiestocarga',
        'numplaca',
    ];

    protected $casts = [
        'fechaexpedicionmanifiesto' => 'date',
    ];

    public function puntosControl(): HasMany
    {
        return $this->hasMany(RndcPuntoControl::class);
    }
}
