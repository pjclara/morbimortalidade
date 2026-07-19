<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticoInternamento extends Model
{
    protected $table = 'diagnostico_internamento';
    protected $fillable = ['descricao', 'diagnostico_id', 'internamento_id', 'principal'];

    //

    public function diagnostico()
    {
        return $this->belongsTo(Diagnostico::class, 'diagnostico_id', 'id');
    }

    public function internamento()
    {
        return $this->belongsTo(Internamento::class, 'internamento_id', 'id');
    }

}