<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoNaoAntecipacaoAlta extends Model
{
    use HasFactory;

    protected $table = 'motivos_nao_antecipacao_alta';

    protected $fillable = ['nome'];

    public function internamentos()
    {
        return $this->hasMany(Internamento::class, 'motivo_nao_antecipacao_alta_id', 'id');
    }
}
