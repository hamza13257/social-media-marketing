<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AiModelsCommand extends Command
{
    protected $signature = 'ai:models';

    protected $description = 'List Groq / OpenAI-compatible models available for your API key';

    public function handle(): int
    {
        $key = config('ai.api_key');
        $base = rtrim((string) config('ai.base_url'), '/');

        if (! filled($key)) {
            $this->error('AI_API_KEY is empty.');

            return self::FAILURE;
        }

        $response = Http::withToken((string) $key)->get($base.'/models');

        if (! $response->successful()) {
            $this->error('HTTP '.$response->status());
            $this->line($response->body());

            return self::FAILURE;
        }

        $rows = collect($response->json('data', []))
            ->sortBy('id')
            ->map(fn (array $m) => [$m['id'] ?? '—', $m['owned_by'] ?? '—']);

        $this->table(['Model ID (use in AI_MODEL)', 'Owner'], $rows);
        $this->newLine();
        $this->line('Copy a Model ID into backend/.env → AI_MODEL= then php artisan config:clear');

        return self::SUCCESS;
    }
}