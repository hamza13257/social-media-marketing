<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ManualPending = 'manual_pending';
    case Active = 'active';
    case Trialing = 'trialing';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Suspended = 'suspended';

    public function isUsable(): bool
    {
        return match ($this) {
            self::Active, self::Trialing => true,
            default => false,
        };
    }
}