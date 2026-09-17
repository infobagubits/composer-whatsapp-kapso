<?php

declare(strict_types=1);

namespace Integrations\Kapso\Console\Commands;

use Illuminate\Console\Command;

class KapsoCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'laravel-kapso:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package laravel-kapso.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Kapso placeholder command executed.');

        return self::SUCCESS;
    }
}
