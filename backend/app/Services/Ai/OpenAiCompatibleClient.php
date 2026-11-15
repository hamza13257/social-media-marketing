<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenAiCompatibleClient implements AiClient
{
    public function isConfigured(): bool
    {
        return filled(config('ai.api_key'));
    }

    public function chatJson(array $messages, array $schemaHint = []): array
    {
        $systemSuffix = $schemaHint
            ? ' Respond ONLY with valid JSON matching this shape: '.json_encode($schemaHint)
            : ' Respond ONLY with valid JSON.';

        $payloadMessages = $messages;
        if (isset($payloadMessages[0]['role']) && $payloadMessages[0]['role'] === 'system') {
            $payloadMessages[0]['content'] .= $systemSuffix;
        } else {
            array_unshift($payloadMessages, [
                'role' => 'system',
                'content' => 'You are a marketing copywriter. Never invent statistics or unsupported claims.'.$systemSuffix,
            ]);
        }

        $response = Http::timeout(config('ai.timeout'))
            ->withToken((string) config('ai.api_key'))
            ->post(rtrim((string) config('ai.base_url'), '/').'/chat/completions', [
                'model' => config('ai.model'),
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
                'messages' => $payloadMessages,
            ])
            ->throw()
            ->json();

        $content = data_get($response, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('AI response was empty.');
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('AI response was not valid JSON.');
        }

        return $decoded;
    }
}