<?php

namespace Novius\TranslationLoader\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonException;
use LaravelLang\Locales\Facades\Locales;
use Novius\TranslationLoader\LanguageLine;
use Symfony\Component\Finder\SplFileInfo;

class SyncTranslations extends Command
{
    protected $signature = 'translations:sync {--clean}';

    protected $description = 'Sync translations from files to database';

    protected array $availableFileExtensions = [
        'php',
    ];

    protected array $oldTranslationsKeys = [];

    protected array $newTranslationsKeys = [];

    protected int $cptAddedTranslations = 0;

    /**
     * @var class-string<LanguageLine>
     */
    protected string $translationModel;

    protected array $availableLocales = [];

    protected array $availableRemoteDirectory = [];

    protected mixed $translationLoader;

    protected array $fileTranslations = [];

    public function __construct(protected Filesystem $filesystem)
    {
        parent::__construct();

        $this->translationLoader = app('translation.loader');
        $this->availableLocales = config('translation-loader.locales') ?? Locales::installed()->pluck('code')->toArray();
        if (empty($this->availableLocales) && class_exists('LaravelLang\Locales\Facades\Locales')) {
            /** @phpstan-ignore-next-line */
            $this->availableLocales = Locales::installed()->pluck('code')->toArray();
        }
        if (empty($this->availableLocales) && class_exists('LaravelLang\Locales\Facades\Locales')) {
            $this->availableLocales = ['en'];
        }
        $this->availableRemoteDirectory = config('translation-loader.remote_directory');
        $this->translationModel = config('translation-loader.model');
    }

    /**
     * @throws JsonException
     */
    public function handle(): void
    {
        $this->oldTranslationsKeys = $this->getDatabaseLanguageLineKeys();

        $languageLines = collect();

        // Get all translations from the base project
        foreach ($this->filesystem->allFiles(lang_path()) as $file) {
            if (! in_array($file->getExtension(), $this->availableFileExtensions, true)) {
                continue;
            }
            $relativePath = $file->getRelativePath();
            $languageLines = $languageLines->concat($this->getLanguageLineFromFile($file, Str::startsWith($relativePath, 'vendor')));
        }

        // Get all translations from remote packages
        foreach ($this->availableRemoteDirectory as $remoteNamespace => $remoteDirectory) {
            foreach ($this->filesystem->allFiles(base_path($remoteDirectory)) as $file) {
                if (! in_array($file->getExtension(), $this->availableFileExtensions, true)) {
                    continue;
                }
                $languageLines = $languageLines->concat($this->getLanguageLineFromFile($file, false, $remoteNamespace));
            }
        }

        $languageLines = $languageLines->unique('translationKey');

        if ($languageLines->isNotEmpty()) {
            $bar = $this->output->createProgressBar($languageLines->count());
            foreach ($languageLines as $languageLine) {
                $this->syncLanguageLineToDatabase($languageLine);
                $bar->advance();
            }
            $bar->finish();
            $this->getOutput()->newLine();
            $this->call('cache:clear');
        }

        collect(array_unique(array_diff($this->oldTranslationsKeys, $this->newTranslationsKeys)))->each(function ($oldTranslationKey) {
            [$namespace, $group, $item] = app('translator')->parseKey($oldTranslationKey);
            $this->translationModel::query()
                ->where('namespace', $namespace)
                ->where('group', $group)
                ->where('key', $item)
                ->update([
                    'text_from_files' => null,
                    'dirty_locales' => null,
                    'orphan' => true,
                ]);
        });

        if ($this->option('clean')) {
            $deleted = $this->translationModel::query()
                ->where('orphan', true)
                ->delete();

            $this->info(trans_choice('laravel-translation-loader::translation.nb_translations_deleted', $deleted, ['cpt' => $deleted]));
        }

        $this->info(trans_choice('laravel-translation-loader::translation.nb_translations_added', $this->cptAddedTranslations, ['cpt' => $this->cptAddedTranslations]));
    }

    protected function syncLanguageLineToDatabase(array $languageLine): void
    {
        $trans = [];
        $transFromFile = [];
        foreach ($this->availableLocales as $local) {
            app()->setLocale($local);
            $translated = $languageLine['fromJson'] ? __($languageLine['translationKey']) : trans($languageLine['translationKey']);
            $trans[$local] = ($translated !== $languageLine['translationKey'] ? $translated : '');
            $transFromFile[$local] = $this->transFromFile($local, $languageLine['namespace'], $languageLine['group'], $languageLine['key']);
        }
        // Reset default locale
        app()->setLocale(config('app.locale'));

        $dirty = collect($trans)->mapWithKeys(function ($value, $key) use ($transFromFile) {
            return $value !== $transFromFile[$key] ? [$key => true] : [];
        })->toArray();

        $model = $this->translationModel::query()
            ->updateOrCreate(
                [
                    'namespace' => $languageLine['namespace'],
                    'group' => $languageLine['group'],
                    'key' => $languageLine['key'],
                ],
                [
                    'text' => $trans,
                    'text_from_files' => $transFromFile,
                    'dirty_locales' => count($dirty) ? $dirty : null,
                    'orphan' => false,
                ]
            );

        $this->newTranslationsKeys[] = $languageLine['translationKey'];
        if ($model->wasRecentlyCreated) {
            $this->cptAddedTranslations++;
        }
    }

    /**
     * Get current DB translation's keys
     */
    protected function getDatabaseLanguageLineKeys(): array
    {
        $lines = $this->translationModel::query()
            ->select(['namespace', 'group', 'key'])
            ->get();

        return $lines->map(function ($line) {
            return $this->translationKey($line->namespace, $line->group, $line->key);
        })->toArray();
    }

    /**
     * @throws JsonException
     */
    protected function getLanguageLineFromFile(SplFileInfo $file, bool $isVendor = false, string $remoteNamespace = ''): array
    {
        $translationLines = [];
        $vendor = $isVendor ? $this->getVendorName($file) : '';
        $group = $this->getGroupName($file, $vendor);

        if ($isVendor && empty($vendor)) {
            return [];
        }

        $namespace = '*';
        // @phpstan-ignore-next-line
        if (! empty($vendor) && $isVendor && empty($remoteNamespace)) {
            $namespace = $vendor;
        } elseif (! empty($remoteNamespace)) {
            $namespace = $remoteNamespace;
        }

        foreach ($this->extractTranslationKeys($file) as $translationKey) {
            $extendTranslationKey = $this->translationKey($namespace, $group, $translationKey);
            $translationLines[$extendTranslationKey] = [
                'translationKey' => $extendTranslationKey,
                'namespace' => $namespace,
                'group' => $group,
                'key' => $translationKey,
                'fromJson' => ($file->getExtension() === 'json'),
            ];
        }

        return array_values($translationLines);
    }

    protected function translationKey(string $namespace, string $group, string $key): string
    {
        return $this->translationModel::translationKey($namespace, $group, $key);
    }

    protected function getVendorName(SplFileInfo $file): string
    {
        $relativePath = $file->getRelativePath();
        $explodedPath = explode(DIRECTORY_SEPARATOR, $relativePath);
        $vendor = $explodedPath[1] ?? '';
        if (empty($vendor) || count($explodedPath) === 2) {
            return '';
        }

        return $vendor;
    }

    protected function getGroupName(SplFileInfo $file, $vendor = ''): string
    {
        $group = Str::replaceLast('.'.$file->getExtension(), '', $file->getFilename());

        $explodedRelativePath = explode(DIRECTORY_SEPARATOR, $file->getRelativePath());
        if (! empty($vendor)) {
            $explodedRelativePath = explode(DIRECTORY_SEPARATOR, Str::after($file->getRelativePath(), $vendor.DIRECTORY_SEPARATOR));
        }

        if (count($explodedRelativePath) > 1) {
            // If the translation file is in a subdirectory, we have to prefix $group with directory tree
            // ex: resources/lang/en/crud/news.php (the group should be crud/news)
            array_shift($explodedRelativePath); // remove locale DIR
            $prefix = implode(DIRECTORY_SEPARATOR, $explodedRelativePath).DIRECTORY_SEPARATOR;
            $group = $prefix.$group;
        }

        if ($file->getExtension() === 'json' && in_array($group, $this->availableLocales, true)) {
            $group = '';
        }

        return $group;
    }

    /**
     * @throws JsonException
     */
    protected function extractTranslationKeys(SplFileInfo $file): array
    {
        if ($file->getExtension() === 'php') {
            $translations = include $file->getPathname();
            if (! is_array($translations)) {
                return [];
            }

            $translations = collect($translations)->map(function ($translation, $key) {
                return $this->normalizeTranslations($translation, $key);
            });

            return $translations->flatten()->toArray();
        }

        $translations = json_decode($file->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return array_keys($translations);
    }

    protected function normalizeTranslations($translation, $key): array
    {
        if (is_string($translation)) {
            return [$key];
        }

        $subs = [];
        if (is_array($translation)) {
            foreach ($translation as $subKey => $subTranslation) {
                if (is_string($subTranslation)) {
                    $subs[] = "$key.$subKey";
                } else {
                    $subs[] = $this->normalizeTranslations($subTranslation, "$key.$subKey");
                }
            }
        }

        return $subs;
    }

    protected function transFromFile(string $local, string $namespace, string $group, string $key): string
    {

        if (is_object($this->translationLoader) &&
            method_exists($this->translationLoader, 'loadFromFiles') &&
            ! Arr::exists($this->fileTranslations, $local.'.'.$namespace.'.'.$group)
        ) {
            Arr::set($this->fileTranslations, $local.'.'.$namespace.'.'.$group, $this->translationLoader->loadFromFiles($local, $group, $namespace));
        }

        return Arr::get($this->fileTranslations, $local.'.'.$namespace.'.'.$group.'.'.$key, $this->translationKey($namespace, $group, $key));
    }
}
