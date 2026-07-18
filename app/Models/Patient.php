<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = ['data_nascimento', 'localidade_id', 'processo', 'sexo_id'];

    //

    public function sexo()
    {
        return $this->belongsTo(Sexo::class, 'sexo_id', 'id');
    }

    public function internamentos()
    {
        return $this->hasMany(Internamento::class, 'patient_id', 'id');
    }

}