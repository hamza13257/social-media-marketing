<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Models\Content;
use App\Models\User;
use App\Support\CurrentTenant;

class ContentPolicy
{
    public function __construct(private CurrentTenant $currentTenant) {}

    public function viewAny(User $user): bool
    {
        return $this->currentTenant->isSet()
            && $this->currentTenant->role()->canManageContent();
    }

    public function view(User $user, Content $content): bool
    {
        return $this->sameTenant($content) && $this->currentTenant->role()->canManageContent();
    }

    public function create(User $user): bool
    {
        return $this->currentTenant->isSet()
            && $this->currentTenant->role()->canManageContent();
    }

    public function update(User $user, Content $content): bool
    {
        if (! $this->sameTenant($content) || ! $this->currentTenant->role()->canManageContent()) {
            return false;
        }

        return ! in_array($content->status, [ContentStatus::Publishing, ContentStatus::Published], true);
    }

    public function delete(User $user, Content $content): bool
    {
        return $this->sameTenant($content)
            && $this->currentTenant->role()->canApproveContent()
            && ! in_array($content->status, [ContentStatus::Publishing, ContentStatus::Published], true);
    }

    public function submit(User $user, Content $content): bool
    {
        return $this->update($user, $content);
    }

    public function approve(User $user, Content $content): bool
    {
        return $this->sameTenant($content)
            && $this->currentTenant->role()->canApproveContent()
            && $content->status === ContentStatus::PendingApproval;
    }

    public function generate(User $user, Content $content): bool
    {
        return $this->update($user, $content);
    }

    public function schedule(User $user, Content $content): bool
    {
        return $this->sameTenant($content)
            && $this->currentTenant->role()->canApproveContent();
    }

    public function publish(User $user, Content $content): bool
    {
        return $this->sameTenant($content)
            && $this->currentTenant->role()->canApproveContent()
            && $content->isEligibleToPublish();
    }

    private function sameTenant(Content $content): bool
    {
        return $this->currentTenant->isSet()
            && (int) $content->tenant_id === $this->currentTenant->id();
    }
}