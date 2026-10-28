<?php

namespace App\Services\Llm;

interface LlmClient
{
    /**
     * Suggest canonical Harbor Desk fields for otherwise-unmapped source keys.
     *
     * @param  array<string, mixed>  $unknownFields
     * @param  list<string>  $missingFields
     * @return array<string, mixed>
     */
    public function mapUnknownKeys(array $unknownFields, array $missingFields): array;
}
