<?php

namespace Novius\TranslationLoader\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsObject;
use Novius\TranslationLoader\LanguageLine;

/**
 * @method static void run(Collection $languageLines, array $locales)
 */
class FileToDb
{
    use AsObject;

    /**
     * @param  Collection<int, LanguageLine>  $languageLines
     */
    public function handle(Collection $languageLines, array $locales): void
    {
        foreach ($languageLines as $languageLine) {
            foreach ($locales as $locale) {
                if (isset($languageLine->text_from_files[$locale])) {
                    $text = $languageLine->text;
                    $text[$locale] = $languageLine->text_from_files[$locale];
                    $languageLine->text = $text;
                }
            }
            $languageLine->save();
        }
    }
}
