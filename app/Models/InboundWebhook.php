<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InboundWebhook extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'label',
        'raw_json',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'raw_json' => 'array',
        ];
    }

    /** @return HasOne<MappedEvent, $this> */
    public function mappedEvent(): HasOne
    {
        return $this->hasOne(MappedEvent::class);
    }
}
