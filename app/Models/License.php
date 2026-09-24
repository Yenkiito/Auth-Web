<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class License extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['project_id', 'partner_id', 'user_id', 'key', 'mask', 'type', 'subscription', 'note', 'expiry_unit', 'expiry_duration', 'status', 'activated_at', 'expires_at', 'max_devices'];

    protected function casts(): array
    {
        return ['activated_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }
}
