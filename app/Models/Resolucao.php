<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resolucao extends Model
{
    protected $fillable = ['clavien_dindo_id', 'descricao', 'nome'];

    //

    public function clavienDindo()
    {
        return $this->belongsTo(ClavienDindo::class, 'clavien_dindo_id', 'id');
    }

    public function complicacaoResolucaos()
    {
        return $this->hasMany(ComplicacaoResolucao::class, 'resolucao_id', 'id');
    }

}