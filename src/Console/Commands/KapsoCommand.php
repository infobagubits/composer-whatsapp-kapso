<?php

declare(strict_types=1);

namespace Integrations\Kapso\Console\Commands;

use Illuminate\Console\Command;
use Integrations\Kapso\Kapso;

class KapsoCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'laravel-kapso:webhook-register';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package laravel-kapso.';

    /**
     * Execute the console command.
     */
    public function handle(Kapso $kapso): int
    {
        $this->line('Kapso placeholder command executed.');

        $response = $kapso->registerWebhook();

        return self::SUCCESS;
    }
}
