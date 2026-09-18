<?php

declare(strict_types=1);

namespace Integrations\Kapso\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Esegue l'handler dell'utente fuori dalla richiesta HTTP.
 *
 * Il webhook deve rispondere 200 entro 10 secondi: tutto cio' che l'handler
 * fa di lento (query, invii, chiamate a terzi) vive qui, non nel controller.
 */
class ProcessKapsoWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  string  $handler  Classe o binding da risolvere dal container.
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $handler,
        public readonly ?string $event,
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $instance = app($this->handler);

        if (! is_object($instance) || ! method_exists($instance, 'handle')) {
            return;
        }

        $instance->handle($this->event, $this->payload);
    }
}
