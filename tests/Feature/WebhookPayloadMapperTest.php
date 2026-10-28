<?php

use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;
use App\Services\Webhooks\PayloadMapper;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('maps aliased order keys deterministically', function (): void {
    $result = app(PayloadMapper::class)->map([
        'event_type' => 'order.paid',
        'orderId' => 'HD-3301',
        'email' => 'casey@example.test',
    ]);

    expect($result['payload']['order_id'])->toBe('HD-3301')
        ->and($result['payload']['customer_email'])->toBe('casey@example.test')
        ->and($result['valid'])->toBeTrue();
});

it('marks an event with a missing email as invalid', function (): void {
    $result = app(PayloadMapper::class)->map([
        'event_type' => 'order.refunded',
        'order_number' => 'HD-3302',
    ]);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toContain('The customer email field is required.');
});

it('requires an event type', function (): void {
    $result = app(PayloadMapper::class)->map([
        'customer_email' => 'casey@example.test',
    ]);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toContain('The event type field is required.');
});

it('rejects a malformed customer email address', function (): void {
    $result = app(PayloadMapper::class)->map([
        'event_type' => 'order.paid',
        'customer_email' => 'not-an-email',
    ]);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toContain('The customer email field must be a valid email address.');
});

it('presents the four seeded fictional examples on the home page', function (): void {
    $this->seed(DatabaseSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('samples', 4)
            ->where('samples.1.label', 'Aliased order keys')
            ->where('samples.2.payload.event_type', 'order.refunded'));
});

it('uses the fake llm client for safe unknown-key suggestions', function (): void {
    config(['services.gemini.api_key' => null]);
    $fake = app(LlmClient::class);

    expect($fake)->toBeInstanceOf(FakeLlmClient::class);

    $result = app(PayloadMapper::class)->map([
        'event' => 'order.created',
        'purchase_reference' => 'HD-3303',
        'buyer_email' => 'devon@example.test',
    ]);

    expect($result['payload']['event_type'])->toBe('order.created')
        ->and($result['payload']['order_id'])->toBe('HD-3303')
        ->and($result['payload']['customer_email'])->toBe('devon@example.test')
        ->and($result['unknown_keys'])->toContain('purchase_reference');
});

it('keeps mapping and validation available when the optional llm fails', function (): void {
    $unavailableClient = new class implements LlmClient
    {
        public function mapUnknownKeys(array $unknownFields, array $missingFields): array
        {
            throw new RuntimeException('The model timed out.');
        }
    };

    $this->app->instance(LlmClient::class, $unavailableClient);
    $result = app(PayloadMapper::class)->map([
        'event_type' => 'order.paid',
        'order_id' => 'HD-3305',
        'customer_email' => 'sam@example.test',
        'vendor_note' => 'keep deterministic mapping alive',
    ]);

    expect($result['valid'])->toBeTrue()
        ->and($result['unknown_keys'])->toContain('vendor_note');
});

it('accepts an unauthenticated webhook post and persists its mapping', function (): void {
    config(['services.gemini.api_key' => null]);

    $response = $this->postJson(route('webhooks.map'), [
        'event_type' => 'order.paid',
        'orderId' => 'HD-3304',
        'email' => 'riley@example.test',
    ]);

    $response->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('payload.order_id', 'HD-3304')
        ->assertJsonPath('payload.customer_email', 'riley@example.test');

    $this->assertDatabaseHas('inbound_webhooks', ['status' => 'mapped']);
    $this->assertDatabaseHas('mapped_events', [
        'event_type' => 'order.paid',
        'order_id' => 'HD-3304',
        'valid' => true,
    ]);
});

it('returns a client error for malformed JSON instead of an application error', function (): void {
    $response = $this->call('POST', route('webhooks.map'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], '{"event_type":');

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'The request body must contain valid JSON.');
});
