<?php

namespace App\Services\Webhooks;

use App\Services\Llm\LlmClient;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PayloadMapper
{
    /** @var array<string, list<string>> */
    private const ALIASES = [
        'event_type' => ['event_type'],
        'order_id' => ['order_id', 'orderId', 'order_number'],
        'customer_email' => ['customer_email', 'email'],
    ];

    public function __construct(private readonly LlmClient $llmClient) {}

    /**
     * @param  array<string, mixed>  $source
     * @return array{payload: array{event_type: ?string, order_id: ?string, customer_email: ?string}, errors: list<string>, unknown_keys: list<string>, valid: bool}
     */
    public function map(array $source): array
    {
        $mapped = [
            'event_type' => null,
            'order_id' => null,
            'customer_email' => null,
        ];
        $knownKeys = [];

        foreach (self::ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $source)) {
                    $knownKeys[] = $alias;

                    if ($mapped[$field] === null) {
                        $mapped[$field] = $this->stringValue($source[$alias]);
                    }
                }
            }
        }

        $unknownFields = array_diff_key($source, array_flip($knownKeys));
        $missingFields = array_keys(array_filter($mapped, static fn (?string $value): bool => $value === null || trim($value) === ''));

        if ($unknownFields !== [] && $missingFields !== []) {
            try {
                $suggestions = $this->llmClient->mapUnknownKeys($unknownFields, $missingFields);

                foreach ($suggestions as $field => $value) {
                    if (array_key_exists($field, $mapped) && $mapped[$field] === null && is_scalar($value)) {
                        $mapped[$field] = $this->stringValue($value);
                    }
                }
            } catch (Throwable) {
                // A model outage must not prevent deterministic mapping or validation.
            }
        }

        $validation = Validator::make($mapped, [
            'event_type' => ['required', 'string', 'max:120'],
            'order_id' => ['nullable', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:254'],
        ]);

        return [
            'payload' => $mapped,
            'errors' => array_values($validation->errors()->all()),
            'unknown_keys' => array_map('strval', array_keys($unknownFields)),
            'valid' => $validation->passes(),
        ];
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        return trim((string) $value);
    }
}
