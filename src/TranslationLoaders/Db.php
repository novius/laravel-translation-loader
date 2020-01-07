<?php

namespace Novius\TranslationLoader\TranslationLoaders;

use Novius\TranslationLoader\LanguageLine;
use Novius\TranslationLoader\Exceptions\InvalidConfiguration;

class Db implements TranslationLoader
{
    public function loadTranslations(string $locale, string $group, string $namespace = '*'): array
    {
        $model = $this->getConfiguredModelClass();

        return $model::getTranslationsForGroup($locale, $group, $namespace);
    }

    protected function getConfiguredModelClass(): string
    {
        $modelClass = config('translation-loader.model');

        if (! is_a(new $modelClass, LanguageLine::class)) {
            throw InvalidConfiguration::invalidModel($modelClass);
        }

        return $modelClass;
    }
}
