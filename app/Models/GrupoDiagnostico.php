<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoDiagnostico extends Model
{
    protected $fillable = ['nome'];

    //

    public function diagnosticos()
    {
        return $this->hasMany(Diagnostico::class, 'grupo_diagnostico_id', 'id');
    }

}