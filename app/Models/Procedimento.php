<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procedimento extends Model
{
    protected $fillable = ['codigo', 'descricao', 'grupo_procedimento_id', 'nome'];

    //

    public function grupoProcedimento()
    {
        return $this->belongsTo(GrupoProcedimento::class, 'grupo_procedimento_id', 'id');
    }

}