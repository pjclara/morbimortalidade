<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDeCirurgia extends Model
{
    protected $fillable = ['nome'];

    //

    public function blocoOperatorios()
    {
        return $this->hasMany(BlocoOperatorio::class, 'tipo_de_cirurgia_id', 'id');
    }

}