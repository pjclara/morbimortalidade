<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo ICD-10-PCS — eixo 4 (parte do corpo): os 4 primeiros caracteres
 * do código de procedimento (secção + sistema corporal + operação + parte do
 * corpo) mapeados para a descrição legível.
 */
class Icd10PcsAxis4Option extends Model
{
    protected $table = 'icd10_pcs_axis_4_options';

    protected $fillable = [
        'parent_prefix',
        'prefix',
        'value',
        'term',
        'description',
        'code_count',
    ];

    public function scopePrefix($query, $prefix)
    {
        return $query->where('prefix', $prefix);
    }
}
