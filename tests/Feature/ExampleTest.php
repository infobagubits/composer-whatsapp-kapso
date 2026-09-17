<?php

declare(strict_types=1);

use Integrations\Kapso\Kapso;

it('resolves the singleton', function () {
    expect(app(Kapso::class))->toBeInstanceOf(Kapso::class);
});

it('returns the same instance from the container', function () {
    expect(app(Kapso::class))->toBe(app(Kapso::class));
});

it('merges the package config', function () {
    expect(config('laravel-kapso.placeholder'))->toBe('default');
});

it('registers the artisan command', function () {
    $this->artisan('laravel-kapso:placeholder')
        ->expectsOutputToContain('Kapso placeholder command executed.')
        ->assertSuccessful();
});
