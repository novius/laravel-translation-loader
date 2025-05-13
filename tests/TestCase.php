<?php

namespace Novius\TranslationLoader\Test;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Novius\TranslationLoader\LanguageLine;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate');
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['path.lang'] = $this->getFixturesDirectory('lang');

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function getFixturesDirectory(string $path): string
    {
        return __DIR__."/fixtures/{$path}";
    }

    protected function createLanguageLine(string $group, string $key, array $text, string $namespace = '*'): LanguageLine
    {
        return LanguageLine::create(compact('namespace', 'group', 'key', 'text'));
    }
}
