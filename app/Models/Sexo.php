<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sexo extends Model
{
    use HasFactory;

    protected $fillable = ['nome'];

    //

    public function patients()
    {
        return $this->hasMany(Patient::class, 'sexo_id', 'id');
    }

}