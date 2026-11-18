<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiSmokeCommand extends Command
{
    protected $signature = 'ai:smoke';

    protected $description = 'Test Groq / OpenAI-compatible API credentials from .env';

    public function handle(): int
    {
        $key = config('ai.api_key');
        $base = config('ai.base_url');
        $model = config('ai.model');

        $this->line('AI base URL: '.$base);
        $this->line('AI model: '.$model);

        if (! filled($key)) {
            $this->error('AI_API_KEY is empty. Add your Groq key to backend/.env then run: php artisan config:clear');

            return self::FAILURE;
        }

        $this->info('AI_API_KEY is set ('.Str::mask($key, '*', 4).').');

        $url = rtrim((string) $base, '/').'/chat/completions';

        $response = Http::timeout((int) config('ai.timeout', 60))
            ->withToken((string) $key)
            ->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Reply with the single word OK.'],
                ],
                'max_tokens' => 16,
            ]);

        $this->line('HTTP status: '.$response->status());
        $this->line(Str::limit($response->body(), 500));

        if (! $response->successful()) {
            $this->error('AI provider rejected the request.');
            $this->line('Base URL should stay: https://api.groq.com/openai/v1');
            $this->line('Run: php artisan ai:models — then set AI_MODEL to an ID from the list.');
            $this->line('Groq developer tier (2026): try AI_MODEL=openai/gpt-oss-20b');

            return self::FAILURE;
        }

        $this->info('AI smoke test passed.');

        return self::SUCCESS;
    }
}