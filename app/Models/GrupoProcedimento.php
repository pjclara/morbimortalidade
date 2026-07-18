<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoProcedimento extends Model
{
    protected $fillable = ['nome'];

    //

    public function procedimentos()
    {
        return $this->hasMany(Procedimento::class, 'grupo_procedimento_id', 'id');
    }

}