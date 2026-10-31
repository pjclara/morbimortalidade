<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtrasoAlta extends Model
{
    use HasFactory;

    protected $table = 'atrasos_alta';

    protected $fillable = [
        'internamento_id',
        'motivo_id',
        'data_sinalizacao_servico_social',
        'data_pedido_rede',
        'observacoes',
    ];

    public function internamento()
    {
        return $this->belongsTo(Internamento::class, 'internamento_id', 'id');
    }

    public function motivo()
    {
        return $this->belongsTo(MotivoAtraso::class, 'motivo_id', 'id');
    }
}
