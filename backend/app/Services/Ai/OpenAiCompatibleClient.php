<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

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

        $endpoint = rtrim((string) config('ai.base_url'), '/').'/chat/completions';
        $body = [
            'model' => config('ai.model'),
            'temperature' => 0.7,
            'messages' => $payloadMessages,
        ];

        if (config('ai.json_response', true)) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::timeout(config('ai.timeout'))
                ->withToken((string) config('ai.api_key'))
                ->post($endpoint, $body)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $providerMessage = data_get($exception->response?->json(), 'error.message');

            if (is_string($providerMessage) && $providerMessage !== '') {
                throw new \RuntimeException('AI provider error: '.$providerMessage, $exception->getCode(), $exception);
            }

            throw new \RuntimeException(
                'AI provider request failed. Check AI_API_KEY, AI_BASE_URL, and AI_MODEL (Groq: https://api.groq.com/openai/v1).',
                $exception->getCode(),
                $exception
            );
        }

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