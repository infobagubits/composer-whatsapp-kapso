<?php

declare(strict_types=1);

namespace Integrations\Kapso\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Integrations\Kapso\Jobs\ProcessKapsoWebhook;
use Throwable;

class KapsoWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $signature = $request->header('X-Webhook-Signature');
        $secret = config('laravel-kapso.webhook.secret');

        if (! $signature || ! is_string($secret) || $secret === '') {
            return response()->json([
                'message' => 'Missing webhook signature or secret.',
            ], 401);
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $request->getContent(),
            $secret,
        );

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        // Prendo handler assegnato dall'utente per la gestione della risposta
        $handler = config('laravel-kapso.webhook.handler');

        if (is_string($handler) && $handler !== '') {
            $event = $request->header('X-Webhook-Event');

            $isBatch = $request->header('X-Webhook-Batch') === 'true'
                || $request->boolean('batch');

            $payloads = $isBatch ? $request->input('data', []) : [$request->all()];

            foreach (is_array($payloads) ? $payloads : [] as $payload) {
                if (! is_array($payload)) {
                    continue;
                }

                if (! $this->firstTimeSeeing($request, $event, $payload, $isBatch)) {
                    continue;
                }

                $this->process($handler, $event, $payload);
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Segna l'evento come visto e dice se tocca a noi processarlo.
     *
     * Cache::add() e' atomico: scrive solo se la chiave non c'e' ancora e
     * risponde false se qualcun altro ci e' arrivato prima. Con has()+put()
     * due consegne parallele passerebbero entrambe.
     *
     * @param  array<string, mixed>  $payload
     */
    private function firstTimeSeeing(Request $request, ?string $event, array $payload, bool $isBatch): bool
    {
        $config = config('laravel-kapso.webhook.idempotency');
        $config = is_array($config) ? $config : [];

        if (! ($config['enabled'] ?? true)) {
            return true;
        }

        // I webhook Meta portano l'header e non sono mai batchati; quelli
        // Kapso no, quindi la chiave la ricaviamo dal payload.
        $header = $request->header('X-Idempotency-Key');

        $id = ! $isBatch && is_string($header) && $header !== ''
            ? $header
            : $this->payloadId($payload);

        $store = $config['store'] ?? null;
        $ttl = $config['ttl'] ?? 3600;

        return Cache::store(is_string($store) ? $store : null)
            ->add('kapso:webhook:'.hash('sha256', $event.'|'.$id), true, is_int($ttl) ? $ttl : 3600);
    }

    /**
     * Identificatore stabile del singolo evento dentro un batch.
     *
     * @param  array<string, mixed>  $payload
     */
    private function payloadId(array $payload): string
    {
        foreach (['id', 'message_id', 'wamid', 'event_id'] as $field) {
            $value = $payload[$field] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }

            if (is_int($value)) {
                return (string) $value;
            }
        }

        return hash('sha256', (string) json_encode($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function process(string $handler, ?string $event, array $payload): void
    {
        $config = config('laravel-kapso.webhook.queue');
        $config = is_array($config) ? $config : [];

        try {
            if (! ($config['enabled'] ?? true)) {
                $instance = app($handler);

                if (is_object($instance) && method_exists($instance, 'handle')) {
                    $instance->handle($event, $payload);
                }

                return;
            }

            $connection = $config['connection'] ?? null;
            $queue = $config['queue'] ?? null;

            ProcessKapsoWebhook::dispatch($handler, $event, $payload)
                ->onConnection(is_string($connection) ? $connection : null)
                ->onQueue(is_string($queue) ? $queue : null);
        } catch (Throwable $e) {
            // Un payload che esplode non deve fermare il resto del batch.
            report($e);
        }
    }
}
