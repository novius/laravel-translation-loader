<?php

namespace Novius\TranslationLoader\Test;

use Illuminate\Support\Facades\Artisan;
use Novius\TranslationLoader\LanguageLine;
use Novius\TranslationLoader\TranslationServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @var \Novius\TranslationLoader\LanguageLine */
    protected $languageLine;

    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate');

        include_once __DIR__.'/../database/migrations/create_language_lines_table.php.stub';
        include_once __DIR__.'/../database/migrations/alter_language_lines_table_add_namespace.stub';

        (new \CreateLanguageLinesTable())->up();
        (new \AlterLanguageLinesTableAddNamespace())->up();

        $this->languageLine = $this->createLanguageLine('group', 'key', ['en' => 'english', 'nl' => 'nederlands']);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            TranslationServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
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
