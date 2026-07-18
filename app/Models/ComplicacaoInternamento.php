<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplicacaoInternamento extends Model
{
    protected $fillable = ['complicacao_id', 'internamento_id', 'resolucaos'];

    //

    public function complicacao()
    {
        return $this->belongsTo(Complicacao::class, 'complicacao_id', 'id');
    }

    public function internamento()
    {
        return $this->belongsTo(Internamento::class, 'internamento_id', 'id');
    }

}