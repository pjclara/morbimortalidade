<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sexo extends Model
{
    protected $fillable = ['nome'];

    //

    public function patients()
    {
        return $this->hasMany(Patient::class, 'sexo_id', 'id');
    }

}