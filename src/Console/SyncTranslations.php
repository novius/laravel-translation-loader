<?php

namespace Novius\TranslationLoader\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class SyncTranslations extends Command
{
    protected $signature = 'translations:sync';

    protected $description = 'Sync translations from files to database';

    protected $availableFileExtensions = [
        'php',
    ];

    protected $dbTranslationsKeys = [];

    protected $cptAddedTranslations = 0;

    /**
     * @var \Novius\TranslationLoader\LanguageLine
     */
    protected $translationModel;

    protected $availableLocales = [];

    protected $availableRemoteDirectory = [];

    protected $filesystem;

    protected $excludeFiles = [];

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->availableLocales = config('translation-loader.locales');
        $this->availableRemoteDirectory = config('translation-loader.remote_directory');
        $this->excludeFiles = config('translation-loader.exclude_files');
        $this->filesystem = $filesystem;
        $this->translationModel = config('translation-loader.model');
    }

    public function handle()
    {
        $this->dbTranslationsKeys = $this->getDatabaseLanguageLineKeys();

        $languageLines = collect();

        // Get all translations from base project
        foreach ($this->filesystem->allFiles(lang_path()) as $file) {
            if (! in_array($file->getExtension(), $this->availableFileExtensions)
                || in_array($file->getFilename(), $this->excludeFiles)) {
                continue;
            }
            $relativePath = $file->getRelativePath();
            $languageLines = $languageLines->concat($this->getLanguageLineFromFile($file, Str::startsWith($relativePath, 'vendor')));
        }

        // Get all translations from remote packages
        foreach ($this->availableRemoteDirectory as $remoteNamespace => $remoteDirectory) {
            foreach ($this->filesystem->allFiles(base_path($remoteDirectory)) as $file) {
                if (! in_array($file->getExtension(), $this->availableFileExtensions)) {
                    continue;
                }
                $languageLines = $languageLines->concat($this->getLanguageLineFromFile($file, false, $remoteNamespace));
            }
        }

        $languageLines = $languageLines->unique('translationKey')->filter(function ($languageLine) {
            // Import only translations which not exists in DB
            return ! in_array($languageLine['translationKey'], $this->dbTranslationsKeys);
        });

        if ($languageLines->isNotEmpty()) {
            $bar = $this->output->createProgressBar($languageLines->count());
            foreach ($languageLines as $languageLine) {
                $this->syncLanguageLineToDatabase($languageLine);
                $bar->advance();
            }
            $bar->finish();
            $this->getOutput()->newLine(1);
            $this->call('cache:clear');
        }

        $this->info(trans_choice('laravel-translation-loader::translation.nb_translations_added', $this->cptAddedTranslations, ['cpt' => $this->cptAddedTranslations]));
    }

    protected function syncLanguageLineToDatabase(array $languageLine)
    {
        $trans = [];
        foreach ($this->availableLocales as $local) {
            app()->setLocale($local);
            $translated = $languageLine['fromJson'] ? Lang::getFromJson($languageLine['translationKey']) : trans($languageLine['translationKey']);
            $trans[$local] = ($translated !== $languageLine['translationKey'] ? $translated : '');
        }
        // Reset default locale
        app()->setLocale(config('app.locale'));

        $this->translationModel::create([
            'namespace' => $languageLine['namespace'],
            'group' => $languageLine['group'],
            'key' => $languageLine['key'],
            'text' => $trans,
        ]);

        $this->dbTranslationsKeys[] = $languageLine['translationKey'];
        $this->cptAddedTranslations++;
    }

    /**
     * Get current DB translation's keys
     */
    protected function getDatabaseLanguageLineKeys(): array
    {
        $lines = $this->translationModel::select(['namespace', 'group', 'key'])->get();

        return $lines->map(function ($line) {
            return $this->translationKey($line->namespace, $line->group, $line->key);
        })->toArray();
    }

    /**
     * @param  bool  $isVendor
     */
    protected function getLanguageLineFromFile(SplFileInfo $file, $isVendor = false, string $remoteNamespace = ''): array
    {
        $translationLines = [];
        $vendor = $isVendor ? $this->getVendorName($file) : '';
        $group = $this->getGroupName($file, $vendor);

        if ($isVendor && empty($vendor)) {
            return [];
        }

        $namespace = '*';
        if (! empty($vendor) && $isVendor && empty($remoteNamespace)) {
            $namespace = $vendor;
        } elseif (! empty($remoteNamespace)) {
            $namespace = $remoteNamespace;
        }

        foreach ($this->extractTranslationKeys($file) as $translationKey) {
            $translationLines[$this->translationKey($namespace, $group, $translationKey)] = [
                'translationKey' => $this->translationKey($namespace, $group, $translationKey),
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
        if (empty($vendor) || count($explodedPath) == 2) {
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
            // If translation file is in sub-directory we have to prefix $group with directory tree
            // ex : resources/lang/en/crud/news.php (group should be crud/news)
            array_shift($explodedRelativePath); // remove locale DIR
            $prefix = implode(DIRECTORY_SEPARATOR, $explodedRelativePath).DIRECTORY_SEPARATOR;
            $group = $prefix.$group;
        }

        if ($file->getExtension() === 'json' && in_array($group, $this->availableLocales)) {
            $group = '';
        }

        return $group;
    }

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

        $translations = json_decode($file->getContents(), true);

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
}
