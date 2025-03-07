<?php

namespace Novius\TranslationLoader\Test;

use Novius\TranslationLoader\TranslationLoaders\Db;

class TranslationManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_will_not_use_database_translations_if_the_provider_is_not_configured(): void
    {
        $this->app['config']->set('translation-loader.translation_loaders', []);

        $this->assertEquals('group.key', trans('group.key'));
    }

    /** @test */
    public function it_will_merge_translation_from_all_providers(): void
    {
        $this->app['config']->set('translation-loader.translation_loaders', [
            Db::class,
            DummyLoader::class,
        ]);

        $this->createLanguageLine('db', 'key', ['en' => 'db']);

        $this->assertEquals('db', trans('db.key'));
        $this->assertEquals('this is dummy', trans('dummy.dummy'));
    }

    /** @test */
    public function it_will_merge_translation_from_all_providers_for_vendor(): void
    {
        $this->app['config']->set('translation-loader.translation_loaders', [
            Db::class,
            DummyLoader::class,
        ]);

        $this->createLanguageLine('file', 'lib_key', ['en' => 'db'], 'lib');

        $this->assertEquals('db', trans('lib::file.lib_key'));
    }

    /** @test */
    public function it_will_not_use_database_translations_if_no_database(): void
    {
        $this->app['config']->set('database.default', 'mysql');
        $this->app['config']->set('translation-loader.translation_loaders', [
            Db::class,
        ]);

        $this->assertEquals('en value', trans('file.key'));
    }
}
