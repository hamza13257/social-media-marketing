<?php

namespace App\Jobs;

use App\Services\Publishing\PublishingOrchestrator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishContentJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $contentId,
        public ?int $socialAccountId = null,
        public ?string $idempotencyKey = null,
    ) {}

    public function uniqueId(): string
    {
        return 'publish-content-'.$this->contentId;
    }

    public function handle(PublishingOrchestrator $orchestrator): void
    {
        try {
            $orchestrator->publishContent(
                $this->contentId,
                $this->socialAccountId,
                $this->idempotencyKey,
            );
        } catch (\RuntimeException $exception) {
            if ($this->isBenign($exception)) {
                return;
            }

            throw $exception;
        }
    }

    protected function isBenign(\RuntimeException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'already published')
            || str_contains($message, 'already completed')
            || str_contains($message, 'must be approved');
    }
}