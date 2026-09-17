<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    config()->set('laravel-kapso.kapso', [
        'base_url' => 'https://api.kapso.ai/meta/whatsapp/v24.0',
        'phone_id' => 'PHONE',
        'api_key' => 'KEY',
        'business_id' => 'WABA',
    ]);

    Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.TEST']]])]);
});

it('registra una route per ogni endpoint', function (): void {
    $uris = collect(Route::getRoutes())
        ->map(fn ($route): string => $route->uri())
        ->filter(fn (string $uri): bool => str_starts_with($uri, 'kapso/'));

    expect($uris)->toHaveCount(76);
});

it('mantiene la route storica di invio testo', function (): void {
    $this->postJson('kapso/send/message', ['to' => '15551234567', 'text' => 'Ciao'])
        ->assertOk()
        ->assertJsonPath('messages.0.id', 'wamid.TEST');

    Http::assertSentCount(1);
});

it('valida i campi obbligatori', function (): void {
    $this->postJson('kapso/send/message', ['to' => '15551234567'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('text');

    Http::assertNothingSent();
});

it('inoltra i filtri di elenco come query string', function (): void {
    $this->getJson('kapso/messages?direction=inbound&limit=5')->assertOk();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/PHONE/messages?direction=inbound&limit=5');
});

it('instrada i bottoni interattivi', function (): void {
    $this->postJson('kapso/send/buttons', [
        'to' => '15551234567',
        'body' => 'Confermi?',
        'buttons' => [['id' => 'si', 'title' => 'Si']],
    ])->assertOk();

    Http::assertSent(fn (Request $request): bool => $request->data()['interactive']['type'] === 'button');
});

it('rifiuta piu\' di tre bottoni', function (): void {
    $this->postJson('kapso/send/buttons', [
        'to' => '15551234567',
        'body' => 'Confermi?',
        'buttons' => [['id' => 'a'], ['id' => 'b'], ['id' => 'c'], ['id' => 'd']],
    ])->assertStatus(422)->assertJsonValidationErrors('buttons');
});

it('distingue le route media fisse da quelle con parametro', function (): void {
    Http::fake(['*' => Http::response('BINARIO')]);

    $this->get('kapso/media/download?token=TOKEN')->assertOk();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/media_download?token=TOKEN'));

    $this->getJson('kapso/media/MEDIA123')->assertOk();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/MEDIA123?phone_number_id=PHONE'));
});

it('distingue kapso/flows/phone dalla route con flow id', function (): void {
    $this->getJson('kapso/flows/phone')->assertOk();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/PHONE/flows');

    $this->postJson('kapso/flows/FLOW1/publish')->assertOk();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/FLOW1/publish');
});

it('carica un file sulla route media', function (): void {
    $this->post('kapso/media', ['file' => UploadedFile::fake()->create('foto.jpg', 10, 'image/jpeg')])
        ->assertOk();

    Http::assertSent(fn (Request $request): bool => $request->isMultipart()
        && $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/PHONE/media');
});

it('usa i verbi HTTP corretti su block-users', function (): void {
    $this->postJson('kapso/block-users', ['users' => ['15551234567']])->assertOk();
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST');

    $this->deleteJson('kapso/block-users', ['users' => ['15551234567']])->assertOk();
    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE');
});

it('valida il pin a sei cifre del numero', function (): void {
    $this->postJson('kapso/phone-number', ['pin' => '123'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('pin');

    $this->postJson('kapso/phone-number', ['pin' => '123456'])->assertOk();
});
