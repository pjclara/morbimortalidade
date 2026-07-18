<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complicacao extends Model
{
    protected $fillable = ['grupo_complicacao_id', 'nome'];

    //

    public function grupoComplicacao()
    {
        return $this->belongsTo(GrupoComplicacao::class, 'grupo_complicacao_id', 'id');
    }

    public function complicacaoInternamentos()
    {
        return $this->hasMany(ComplicacaoInternamento::class, 'complicacao_id', 'id');
    }

}