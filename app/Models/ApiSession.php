<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'license_id',
        'token_hash',
        'hwid_hash',
        'ip_address',
        'user_agent',
        'last_seen_at',
        'expires_at',
    ];

    protected $hidden = ['token_hash', 'hwid_hash'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
