<?php

namespace App\Enums;

enum TenantRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Editor = 'editor';

    public function canManageBrands(): bool
    {
        return match ($this) {
            self::Owner, self::Admin, self::Editor => true,
        };
    }

    public function canDeleteBrands(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Editor => false,
        };
    }

    public function canManageTenant(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Editor => false,
        };
    }

    public function canViewSocialAccounts(): bool
    {
        return match ($this) {
            self::Owner, self::Admin, self::Editor => true,
        };
    }

    public function canManageSocialAccounts(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Editor => false,
        };
    }
}