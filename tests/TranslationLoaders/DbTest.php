<?php

namespace Novius\TranslationLoader\Test\TranslationLoaders;

use DB;
use Novius\TranslationLoader\Exceptions\InvalidConfiguration;
use Novius\TranslationLoader\LanguageLine;
use Novius\TranslationLoader\Test\TestCase;

class DbTest extends TestCase
{
    /** @var LanguageLine */
    protected $languageLine;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_get_a_translation_for_the_current_app_locale(): void
    {
        $this->assertEquals('english', trans('group.key'));
    }

    /** @test */
    public function it_can_get_a_correct_translation_after_the_locale_has_been_changed(): void
    {
        app()->setLocale('nl');

        $this->assertEquals('nederlands', trans('group.key'));
    }

    /** @test */
    public function it_can_return_the_group_and_the_key_when_getting_a_non_existing_translation(): void
    {
        app()->setLocale('nl');

        $this->assertEquals('group.unknown', trans('group.unknown'));
    }

    /** @test */
    public function it_supports_placeholders(): void
    {
        $this->createLanguageLine('group', 'placeholder', ['en' => 'text with :placeholder']);

        $this->assertEquals(
            'text with filled in placeholder',
            trans('group.placeholder', ['placeholder' => 'filled in placeholder'])
        );
    }

    /** @test */
    public function it_will_cache_all_translations(): void
    {
        trans('group.key');

        $queryCount = count(DB::getQueryLog());
        $this->flushIlluminateTranslatorCache();

        trans('group.key');

        $this->assertCount($queryCount, DB::getQueryLog());
    }

    /** @test */
    public function it_flushes_the_cache_when_a_translation_has_been_created(): void
    {
        $this->assertEquals('group.new', trans('group.new'));

        $this->createLanguageLine('group', 'new', ['en' => 'created']);
        $this->flushIlluminateTranslatorCache();

        $this->assertEquals('created', trans('group.new'));
    }

    /** @test */
    public function it_flushes_the_cache_when_a_translation_has_been_updated(): void
    {
        trans('group.key');

        $this->languageLine->setTranslation('en', 'updated');
        $this->languageLine->save();

        $this->flushIlluminateTranslatorCache();

        $this->assertEquals('updated', trans('group.key'));
    }

    /** @test */
    public function it_flushes_the_cache_when_a_translation_has_been_deleted(): void
    {
        $this->assertEquals('english', trans('group.key'));

        $this->languageLine->delete();
        $this->flushIlluminateTranslatorCache();

        $this->assertEquals('group.key', trans('group.key'));
    }

    /** @test */
    public function it_can_work_with_a_custom_model(): void
    {
        $alternativeModel = new class extends LanguageLine
        {
            protected $table = 'language_lines';

            public static function getTranslationsForGroup(string $locale, string $group, string $namespace = '*'): array
            {
                return ['key' => 'alternative class'];
            }
        };

        $this->app['config']->set('translation-loader.model', get_class($alternativeModel));

        $this->assertEquals('alternative class', trans('group.key'));
    }

    /** @test */
    public function it_will_throw_an_exception_if_the_configured_model_does_not_extend_the_default_one(): void
    {
        $invalidModel = new class {};

        $this->app['config']->set('translation-loader.model', get_class($invalidModel));

        $this->expectException(InvalidConfiguration::class);

        $this->assertEquals('alternative class', trans('group.key'));
    }

    protected function flushIlluminateTranslatorCache(): void
    {
        $this->app['translator']->setLoaded([]);
    }
}
