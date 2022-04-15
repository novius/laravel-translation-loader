<?php

namespace Novius\TranslationLoader;

use Illuminate\Support\Str;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider as IlluminateTranslationServiceProvider;
use Novius\TranslationLoader\Console\ResetTranslations;
use Novius\TranslationLoader\Console\SyncTranslations;

class TranslationServiceProvider extends IlluminateTranslationServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/translation-loader.php', 'translation-loader');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole() && !Str::contains($this->app->version(), 'Lumen')) {
            $this->publishes([
                __DIR__.'/../config/translation-loader.php' => config_path('translation-loader.php'),
            ], 'config');

            if (!class_exists('CreateLanguageLinesTable')) {
                $this->publishes([
                    __DIR__.'/../database/migrations/create_language_lines_table.php.stub' => database_path('migrations/2019_12_19_162632_create_language_lines_table.php'),

                ], 'migrations');
            }

            if (!class_exists('AlterLanguageLinesTableAddNamespace')) {
                $this->publishes([
                    __DIR__.'/../database/migrations/alter_language_lines_table_add_namespace.stub' => database_path('migrations/2019_12_19_162633_alter_language_lines_table_add_namespace.php'),

                ], 'migrations');
            }

            $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-translation-loader');
            $this->publishes([__DIR__.'/../resources/lang' => lang_path('vendor/laravel-translation-loader')], 'lang');

            $this->commands([
                SyncTranslations::class,
                ResetTranslations::class,
            ]);
        }
    }

    /**
     * Register the translation line loader. This method registers a
     * `TranslationLoaderManager` instead of a simple `FileLoader` as the
     * applications `translation.loader` instance.
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            $class = config('translation-loader.translation_manager');

            return new $class($app['files'], $app['path.lang']);
        });
    }
}
