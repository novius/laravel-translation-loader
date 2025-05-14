<?php

namespace Novius\TranslationLoader\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsObject;
use Novius\TranslationLoader\LanguageLine;

/**
 * @method static void run(Collection $languageLines, array $locales)
 */
class DbToFile
{
    use AsObject;

    /**
     * @param  Collection<int, LanguageLine>  $languageLines
     */
    public function handle(Collection $languageLines, array $locales): void
    {
        foreach ($languageLines as $languageLine) {
            foreach ($locales as $locale) {
                if ($languageLine->text[$locale] !== $languageLine->text_from_files[$locale]) {
                    $this->appendFile($this->getFile($languageLine, $locale), $languageLine->key, $languageLine->text[$locale]);
                    $text_from_files = $languageLine->text_from_files;
                    $text_from_files[$locale] = $languageLine->text[$locale];
                    $languageLine->text_from_files = $text_from_files;
                }
            }
            $languageLine->save();
        }
    }

    protected function appendFile(string $path, string $key, string $value): void
    {
        $translations = [];
        if (file_exists($path)) {
            $translations = require $path;
        }

        $translations[$key] = $value;
        File::makeDirectory(dirname($path), 0755, true, true);
        file_put_contents($path, '<?php return '.PHP_EOL.var_export($translations, true).';');
    }

    protected function getFile(LanguageLine $languageLine, string $locale): string
    {
        if ($languageLine->namespace === '*') {
            return lang_path($locale.DIRECTORY_SEPARATOR.$languageLine->group.'.php');
        }

        return lang_path('vendor'.DIRECTORY_SEPARATOR.$languageLine->namespace.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$languageLine->group.'.php');
    }
}
