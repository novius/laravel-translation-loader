<?php

namespace Novius\TranslationLoader;

use Illuminate\Translation\FileLoader;
use Novius\TranslationLoader\TranslationLoaders\TranslationLoader;

class TranslationLoaderManager extends FileLoader
{
    /**
     * Load the messages for the given locale.
     *
     * @param string $locale
     * @param string $group
     * @param string $namespace
     *
     * @return array
     */
    public function load($locale, $group, $namespace = null): array
    {
        //dump(get_defined_vars());
        $fileTranslations = parent::load($locale, $group, $namespace);
        //dump($fileTranslations);

        if (! is_null($namespace) && $namespace !== '*') {
            //dd($namespace);
            //return $fileTranslations;
        }

        $loaderTranslations = $this->getTranslationsForTranslationLoaders($locale, $group, $namespace);

        return array_replace_recursive($fileTranslations, $loaderTranslations);
    }

    protected function getTranslationsForTranslationLoaders(
        string $locale,
        string $group,
        string $namespace = null
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
