<?php

namespace Novius\TranslationLoader;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class LanguageLine extends Model
{
    /** @var array */
    public $translatable = ['text'];

    /** @var array */
    public $guarded = ['id'];

    /** @var array */
    protected $casts = ['text' => 'array'];

    public static function boot()
    {
        parent::boot();

        $flushGroupCache = function (self $languageLine) {
            $languageLine->flushGroupCache();
        };

        static::saved($flushGroupCache);
        static::deleted($flushGroupCache);
    }

    public static function getTranslationsForGroup(string $locale, string $group, string $namespace = '*'): array
    {
        return Cache::rememberForever(static::getCacheKey($group, $locale, $namespace), function () use ($group, $locale, $namespace) {
            return static::query()
                ->where('namespace', $namespace)
                ->where('group', $group)
                ->get()
                ->reduce(function ($lines, self $languageLine) use ($locale) {
                    $translation = $languageLine->getTranslation($locale);
                    if ($translation !== null) {
                        Arr::set($lines, $languageLine->key, $translation);
                    }

                    return $lines;
                }) ?? [];
        });
    }

    public static function getCacheKey(string $group, string $locale, string $namespace = '*'): string
    {
        return "spatie.translation-loader.{$namespace}.{$group}.{$locale}";
    }

    /**
     * @return string
     */
    public function getTranslation(string $locale): ?string
    {
        if (! isset($this->text[$locale])) {
            $fallback = config('app.fallback_locale');

            return $this->text[$fallback] ?? null;
        }

        return $this->text[$locale];
    }

    /**
     * @return $this
     */
    public function setTranslation(string $locale, string $value)
    {
        $this->text = array_merge($this->text ?? [], [$locale => $value]);

        return $this;
    }

    public function flushGroupCache()
    {
        foreach ($this->getTranslatedLocales() as $locale) {
            Cache::forget(static::getCacheKey($this->group, $locale, $this->namespace));
        }
    }

    protected function getTranslatedLocales(): array
    {
        return array_keys($this->text);
    }

    public function getTranslations(): array
    {
        $translations = [];
        foreach ($this->getTranslatedLocales() as $locale) {
            $translations[$locale] = $this->getTranslation($locale);
        }

        return $translations;
    }

    public static function translationKey(string $namespace, string $group, string $key): string
    {
        if ($namespace === '*') {
            $namespace = '';
        }

        $translationKey = $namespace;
        if (! empty($namespace)) {
            $translationKey .= '::';
        }

        $translationKey .= $group;
        if (! empty($group)) {
            $translationKey .= '.';
        }

        $translationKey .= $key;

        return $translationKey;
    }
}
