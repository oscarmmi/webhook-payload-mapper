<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use JsonException;

class GeminiLlmClient implements LlmClient
{
    /** @param array<string, mixed> $unknownFields
     * @param  list<string>  $missingFields
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function mapUnknownKeys(array $unknownFields, array $missingFields): array
    {
        $apiKey = (string) config('services.gemini.api_key');

        if ($apiKey === '') {
            return [];
        }

        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $response = Http::timeout(12)
            ->connectTimeout(3)
            ->acceptJson()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [[
                    'parts' => [[
                        'text' => implode("\n", [
                            'Map only clearly equivalent source values to the Harbor Desk fields event_type, order_id, and customer_email.',
                            'Return a JSON object using canonical field names as keys. Do not invent or transform values.',
                            'Do not include any field not listed as missing.',
                            'Missing fields: '.implode(', ', $missingFields),
                            'Unknown source fields: '.json_encode($unknownFields, JSON_THROW_ON_ERROR),
                        ]),
                    ]],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0,
                ],
            ]);

        $response->throw();

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            return [];
        }

        $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
