<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Icd10CapitulosSecoes extends Model
{
    protected $table = 'icd10_capitulos_secoes';

    protected $fillable = [
        'capitulo',
        'capitulo_descricao',
        'seccao',
        'seccao_descricao',
        'codigo_inicio',
        'codigo_fim',
    ];

    public $timestamps = false;

    public function scopeCapitulo($query, $capitulo)
    {
        return $query->where('capitulo', $capitulo);
    }

    public function scopeSeccao($query, $seccao)
    {
        return $query->where('seccao', $seccao);
    }

}
