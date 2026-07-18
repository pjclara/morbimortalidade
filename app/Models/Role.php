<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['guard_name', 'name'];

    //

    public function modelHasRoles()
    {
        return $this->hasMany(ModelHasRole::class, 'role_id', 'id');
    }

    public function roleHasPermissions()
    {
        return $this->hasMany(RoleHasPermission::class, 'role_id', 'id');
    }

}