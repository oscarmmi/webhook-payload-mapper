<?php

namespace App\Http\Controllers;

use App\Models\InboundWebhook;
use App\Models\MappedEvent;
use App\Services\Webhooks\PayloadMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use JsonException;
use stdClass;

class WebhookMappingController extends Controller
{
    public function index(): Response
    {
        $samples = InboundWebhook::query()
            ->whereNotNull('label')
            ->orderBy('id')
            ->get(['id', 'label', 'raw_json', 'status'])
            ->map(fn (InboundWebhook $webhook): array => [
                'id' => $webhook->id,
                'label' => $webhook->label,
                'payload' => $webhook->raw_json,
            ])
            ->values();

        return Inertia::render('Welcome', [
            'samples' => $samples,
            'llmMode' => filled(config('services.gemini.api_key')) ? 'Gemini assist enabled' : 'Offline demo mode',
        ]);
    }

    public function store(Request $request, PayloadMapper $mapper): JsonResponse
    {
        try {
            $source = $this->sourcePayload($request);
        } catch (JsonException) {
            return response()->json([
                'message' => 'The request body must contain valid JSON.',
            ], 422);
        }

        if (! $source instanceof stdClass) {
            return response()->json([
                'message' => 'The webhook payload must be a JSON object.',
            ], 422);
        }

        $source = get_object_vars($source);

        $mapped = $mapper->map($source);

        $event = DB::transaction(function () use ($source, $mapped): MappedEvent {
            $webhook = InboundWebhook::query()->create([
                'raw_json' => $source,
                'status' => $mapped['valid'] ? 'mapped' : 'invalid',
            ]);

            return $webhook->mappedEvent()->create([
                'event_type' => $mapped['payload']['event_type'],
                'order_id' => $mapped['payload']['order_id'],
                'customer_email' => $mapped['payload']['customer_email'],
                'payload' => $mapped['payload'],
                'valid' => $mapped['valid'],
                'errors' => $mapped['errors'],
            ]);
        });

        return response()->json([
            'id' => $event->id,
            'payload' => $mapped['payload'],
            'valid' => $mapped['valid'],
            'errors' => $mapped['errors'],
            'unknown_keys' => $mapped['unknown_keys'],
            'status' => $event->valid ? 'mapped' : 'invalid',
        ]);
    }

    /** @throws JsonException */
    private function sourcePayload(Request $request): mixed
    {
        $body = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if ($body instanceof stdClass && property_exists($body, 'payload_json')) {
            $payload = $body->payload_json;

            return is_string($payload)
                ? json_decode($payload, false, 512, JSON_THROW_ON_ERROR)
                : $payload;
        }

        return $body;
    }
}
