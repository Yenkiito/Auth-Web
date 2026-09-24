<?php

namespace App\Enums;

enum Role: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case PARTNER = 'PARTNER';
    case CLIENT = 'CLIENT';

    public function dashboard(): string
    {
        return match ($this) {
            self::OWNER, self::ADMIN => 'admin.dashboard',
            self::PARTNER => 'partner.dashboard',
            self::CLIENT => 'client.dashboard',
        };
    }
}
