<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Icd10CM extends Model
{
    protected $table = 'icd10_cm';

    protected $fillable = [
        'code',
        'description',
        'valid',
        'notes'
    ];

    public $timestamps = false;

    
}
