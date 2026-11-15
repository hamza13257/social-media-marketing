<?php

namespace App\Services\Ai;

interface AiClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<string, mixed>
     */
    public function chatJson(array $messages, array $schemaHint = []): array;

    public function isConfigured(): bool;
}