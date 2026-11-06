<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * A cached config overrides phpunit.xml, and RefreshDatabase would then wipe the real
     * database. Traits are set up after this check, so it always gets there first.
     */
    protected function setUpTraits()
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');

        if (! str_ends_with($database, '_testing')) {
            throw new \RuntimeException('Tests must run on a *_testing database. Run "php artisan config:clear" first.');
        }

        return parent::setUpTraits();
    }
}
