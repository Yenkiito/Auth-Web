<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor', 'project_id', 'partner_id', 'user_id', 'action', 'ip', 'user_agent', 'metadata', 'created_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }
}
