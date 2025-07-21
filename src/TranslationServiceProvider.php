<?php

namespace Novius\TranslationLoader;

use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider as IlluminateTranslationServiceProvider;
use Novius\TranslationLoader\Console\ResetTranslations;
use Novius\TranslationLoader\Console\SyncTranslations;

class TranslationServiceProvider extends IlluminateTranslationServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/translation-loader.php', 'translation-loader');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/translation-loader.php' => config_path('translation-loader.php'),
        ], 'config');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-translation-loader');
        $this->publishes([__DIR__.'/../resources/lang' => lang_path('vendor/laravel-translation-loader')], 'lang');

        $this->commands([
            SyncTranslations::class,
            ResetTranslations::class,
        ]);
    }

    /**
     * Register the translation line loader. This method registers a
     * `TranslationLoaderManager` instead of a simple `FileLoader` as the
     * applications `translation.loader` instance.
     */
    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            $class = config('translation-loader.translation_manager');

            return new $class($app['files'], $app['path.lang']);
        });
    }
}
