<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Jobs\PublishContentJob;
use App\Models\Content;
use Illuminate\Console\Command;

class PublishDueContentCommand extends Command
{
    protected $signature = 'content:publish-due';

    protected $description = 'Queue publish jobs for scheduled content that is due';

    public function handle(): int
    {
        $count = 0;

        Content::query()
            ->where('status', ContentStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->chunkById(50, function ($contents) use (&$count) {
                foreach ($contents as $content) {
                    PublishContentJob::dispatch(
                        $content->id,
                        null,
                        'content:'.$content->id.':v'.$content->version
                    );
                    $count++;
                }
            });

        $this->info("Queued {$count} publishing job(s).");

        return self::SUCCESS;
    }
}