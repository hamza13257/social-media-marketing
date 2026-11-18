<?php

namespace App\Services\Content;

use App\Enums\ApprovalDecision;
use App\Enums\ContentStatus;
use App\Models\Content;
use App\Models\ContentApprovalLog;
use App\Models\User;
use App\Services\Billing\PlanPublishingQuotaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContentApprovalService
{
    public function submit(Content $content, User $user, ?string $comment = null): Content
    {
        return DB::transaction(function () use ($content, $user, $comment) {
            $from = $content->status;
            $content->transitionTo(ContentStatus::PendingApproval);
            $content->save();

            $this->log($content, $user, ApprovalDecision::Resubmitted, $from, $content->status, $comment);

            return $content->fresh(['brand', 'author']);
        });
    }

    public function approve(Content $content, User $user, ?string $comment = null): Content
    {
        return DB::transaction(function () use ($content, $user, $comment) {
            $from = $content->status;
            $content->transitionTo(ContentStatus::Approved);
            $content->version++;
            $content->save();

            $this->log($content, $user, ApprovalDecision::Approved, $from, $content->status, $comment);

            return $content->fresh(['brand', 'author']);
        });
    }

    public function reject(Content $content, User $user, ?string $comment = null): Content
    {
        return DB::transaction(function () use ($content, $user, $comment) {
            $from = $content->status;
            $content->transitionTo(ContentStatus::Rejected);
            $content->save();

            $this->log($content, $user, ApprovalDecision::Rejected, $from, $content->status, $comment);

            return $content->fresh(['brand', 'author']);
        });
    }

    public function requestChanges(Content $content, User $user, ?string $comment = null): Content
    {
        return DB::transaction(function () use ($content, $user, $comment) {
            $from = $content->status;
            $content->transitionTo(ContentStatus::ChangesRequested);
            $content->save();

            $this->log($content, $user, ApprovalDecision::ChangesRequested, $from, $content->status, $comment);

            return $content->fresh(['brand', 'author']);
        });
    }

    public function schedule(Content $content, User $user, \DateTimeInterface $scheduledAt): Content
    {
        return DB::transaction(function () use ($content, $user, $scheduledAt) {
            if (! in_array($content->status, [ContentStatus::Approved, ContentStatus::Scheduled], true)) {
                throw new \InvalidArgumentException('Only approved content can be scheduled.');
            }

            $content->loadMissing('tenant');
            app(PlanPublishingQuotaService::class)->assertWithinPublishingLimit(
                $content->tenant,
                $content,
                Carbon::instance($scheduledAt),
            );

            $from = $content->status;
            $content->scheduled_at = $scheduledAt;
            if ($content->status === ContentStatus::Approved) {
                $content->transitionTo(ContentStatus::Scheduled);
            }
            $content->save();

            $this->log($content, $user, ApprovalDecision::Approved, $from, $content->status, 'Scheduled for publishing.');

            return $content->fresh(['brand', 'author']);
        });
    }

    protected function log(
        Content $content,
        User $user,
        ApprovalDecision $decision,
        ContentStatus $from,
        ContentStatus $to,
        ?string $comment,
    ): void {
        ContentApprovalLog::query()->create([
            'content_id' => $content->id,
            'user_id' => $user->id,
            'decision' => $decision,
            'comment' => $comment,
            'from_status' => $from->value,
            'to_status' => $to->value,
        ]);
    }
}