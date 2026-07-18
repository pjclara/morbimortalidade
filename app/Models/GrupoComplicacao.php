<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoComplicacao extends Model
{
    protected $fillable = ['nome'];

    //

    public function complicacaos()
    {
        return $this->hasMany(Complicacao::class, 'grupo_complicacao_id', 'id');
    }

}