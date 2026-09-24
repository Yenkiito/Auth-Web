<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'key_prefix', 'description', 'project_key', 'owner_id', 'version', 'status'];

    protected $hidden = ['project_key'];

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
