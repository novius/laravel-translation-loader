<?php

namespace Novius\TranslationLoader\Test;

use Novius\TranslationLoader\LanguageLine;

class LanguageLineTest extends TestCase
{
    /** @test */
    public function it_can_get_a_translation(): void
    {
        $languageLine = $this->createLanguageLine('group', 'new', ['en' => 'english', 'nl' => 'nederlands']);

        $this->assertEquals('english', $languageLine->getTranslation('en'));
        $this->assertEquals('nederlands', $languageLine->getTranslation('nl'));
    }

    /** @test */
    public function it_can_set_a_translation(): void
    {
        $languageLine = $this->createLanguageLine('group', 'new', ['en' => 'english']);

        $languageLine->setTranslation('nl', 'nederlands');

        $this->assertEquals('english', $languageLine->getTranslation('en'));
        $this->assertEquals('nederlands', $languageLine->getTranslation('nl'));
    }

    /** @test */
    public function it_can_set_a_translation_on_a_fresh_model(): void
    {
        $languageLine = new LanguageLine;

        $languageLine->setTranslation('nl', 'nederlands');

        $this->assertEquals('nederlands', $languageLine->getTranslation('nl'));
    }

    /** @test */
    public function it_doesnt_show_error_when_getting_nonexistent_translation(): void
    {
        $languageLine = $this->createLanguageLine('group', 'new', ['nl' => 'nederlands']);
        $this->assertNull($languageLine->getTranslation('en'));
    }

    /** @test */
    public function get_fallback_locale_if_doesnt_exists(): void
    {
        $languageLine = $this->createLanguageLine('group', 'new', ['en' => 'English']);
        $this->assertEquals('English', $languageLine->getTranslation('es'));
    }
}
