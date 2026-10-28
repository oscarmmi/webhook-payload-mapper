<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MappedEvent extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'inbound_webhook_id',
        'event_type',
        'order_id',
        'customer_email',
        'payload',
        'valid',
        'errors',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'valid' => 'boolean',
            'errors' => 'array',
        ];
    }

    /** @return BelongsTo<InboundWebhook, $this> */
    public function inboundWebhook(): BelongsTo
    {
        return $this->belongsTo(InboundWebhook::class);
    }
}
