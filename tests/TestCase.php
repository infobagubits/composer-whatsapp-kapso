<?php

declare(strict_types=1);

namespace Integrations\Kapso\Tests;

use Integrations\Kapso\KapsoServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            KapsoServiceProvider::class,
        ];
    }
}
