<?php

namespace Novius\TranslationLoader\Exceptions;

use Exception;
use Novius\TranslationLoader\LanguageLine;

class InvalidConfiguration extends Exception
{
    public static function invalidModel(string $className): self
    {
        return new self("You have configured an invalid class `{$className}`.".
            'A valid class extends '.LanguageLine::class.'.');
    }
}
