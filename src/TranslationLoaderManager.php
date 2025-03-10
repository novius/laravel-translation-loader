<?php

namespace Novius\TranslationLoader;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\FileLoader;
use Novius\TranslationLoader\TranslationLoaders\TranslationLoader;

class TranslationLoaderManager extends FileLoader
{
    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     */
    public function load($locale, $group, $namespace = null): array
    {
        try {
            $fileTranslations = parent::load($locale, $group, $namespace);

            if (! is_null($namespace) && $namespace !== '*') {
                // Difference with spatie/laravel-translation-loader: load vendors' translations primarily from DB
                // return $fileTranslations;
            }

            $loaderTranslations = $this->getTranslationsForTranslationLoaders($locale, $group, $namespace);

            return array_replace_recursive($fileTranslations, $loaderTranslations);
        } catch (QueryException $e) {
            $modelClass = config('translation-loader.model');
            $model = new $modelClass;
            if (is_a($model, LanguageLine::class)) {
                try {
                    if (! Schema::hasTable($model->getTable())) {
                        return parent::load($locale, $group, $namespace);
                    }
                } catch (QueryException) {
                    return parent::load($locale, $group, $namespace);
                }
            }

            throw $e;
        }
    }

    protected function getTranslationsForTranslationLoaders(
        string $locale,
        string $group,
        ?string $namespace = null
    ): array {
        return collect(config('translation-loader.translation_loaders'))
            ->map(function (string $className) {
                return app($className);
            })
            ->mapWithKeys(function (TranslationLoader $translationLoader) use ($locale, $group, $namespace) {
                return $translationLoader->loadTranslations($locale, $group, $namespace);
            })
            ->toArray();
    }
}
