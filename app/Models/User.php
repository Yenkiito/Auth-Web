<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'status',
        'project_id',
        'partner_id',
        'last_login_at',
        'expires_at',
        'hwid_affected',
        'hwid_hash',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'hwid_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'expires_at' => 'datetime',
            'hwid_affected' => 'boolean',
            'role' => Role::class,
            'password' => 'hashed',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function isOwner(): bool
    {
        return $this->role === Role::OWNER;
    }

    public function isManager(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function scopeClients(Builder $query): Builder
    {
        return $query->where('role', Role::CLIENT);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [Role::OWNER, Role::ADMIN], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
