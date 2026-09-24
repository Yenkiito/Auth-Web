<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['project_id', 'parent_id', 'user_id', 'name', 'status'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
