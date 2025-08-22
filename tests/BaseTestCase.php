<?php

namespace Qoraiche\MailEclipse\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Qoraiche\MailEclipse\MailEclipseServiceProvider;

class BaseTestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Register package aliases for Testbench.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'MailEclipse' => \Qoraiche\MailEclipse\Facades\MailEclipse::class,
        ];
    }

    protected function getPackageProviders($app)
    {
        return [
            MailEclipseServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set a default app key for encryption
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }
}
