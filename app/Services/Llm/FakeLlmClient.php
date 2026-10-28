<?php

namespace App\Services\Llm;

class FakeLlmClient implements LlmClient
{
    /** @var array<string, string> */
    private const KEY_HINTS = [
        'event' => 'event_type',
        'event_name' => 'event_type',
        'eventtype' => 'event_type',
        'type' => 'event_type',
        'purchase_reference' => 'order_id',
        'purchase_id' => 'order_id',
        'transaction_id' => 'order_id',
        'reference' => 'order_id',
        'contact_email' => 'customer_email',
        'email_address' => 'customer_email',
        'buyer_email' => 'customer_email',
    ];

    public function mapUnknownKeys(array $unknownFields, array $missingFields): array
    {
        $suggestions = [];

        foreach ($unknownFields as $key => $value) {
            $destination = self::KEY_HINTS[strtolower($key)] ?? null;

            if ($destination !== null && in_array($destination, $missingFields, true)) {
                $suggestions[$destination] = $value;
            }
        }

        return $suggestions;
    }
}
