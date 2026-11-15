<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Generating = 'generating';
    case PendingApproval = 'pending_approval';
    case ChangesRequested = 'changes_requested';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'failed';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Generating, self::PendingApproval, self::Draft], true),
            self::Generating => in_array($next, [self::PendingApproval, self::Draft, self::Failed], true),
            self::PendingApproval => in_array($next, [self::Approved, self::ChangesRequested, self::Rejected, self::Draft], true),
            self::ChangesRequested => in_array($next, [self::Draft, self::PendingApproval], true),
            self::Rejected => in_array($next, [self::Draft], true),
            self::Approved => in_array($next, [self::Scheduled, self::Draft, self::PendingApproval], true),
            self::Scheduled => in_array($next, [self::Publishing, self::Approved, self::Draft], true),
            self::Publishing => in_array($next, [self::Published, self::Failed], true),
            self::Published, self::Failed => false,
        };
    }

    public function isPublishable(): bool
    {
        return in_array($this, [self::Approved, self::Scheduled], true);
    }
}