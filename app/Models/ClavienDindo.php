<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClavienDindo extends Model
{
    use HasFactory;

    // Grau usado para assinalar explicitamente "sem complicações" num
    // internamento com bloco operatório, sem exigir complicações registadas.
    const SEM_COMPLICACOES_ID = 10;

    // Grau V: óbito. Um internamento com bloco operatório cujo destino é
    // "Falecido" é, por definição da classificação Clavien-Dindo, Grau V —
    // nunca "sem complicações" (ver Internamento::falecido).
    const GRAU_V_ID = 9;

    protected $fillable = ['descricao', 'nome'];

    //

    public function internamentos()
    {
        return $this->hasMany(Internamento::class, 'clavien_dindo_id', 'id');
    }

    public function resolucaos()
    {
        return $this->hasMany(Resolucao::class, 'clavien_dindo_id', 'id');
    }

}