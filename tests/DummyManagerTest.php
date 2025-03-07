<?php

namespace Novius\TranslationLoader\Test;

use Illuminate\Foundation\Application;
use Novius\TranslationLoader\Test\TranslationManagers\DummyManager;

class DummyManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('translation-loader.translation_manager', DummyManager::class);
    }

    /** @test */
    public function it_allow_to_change_translation_manager(): void
    {
        $this->assertInstanceOf(DummyManager::class, $this->app['translation.loader']);
    }

    /** @test */
    public function it_can_translate_using_dummy_manager_using_file(): void
    {
        $this->assertEquals('en value', trans('file.key'));
    }

    /** @test */
    public function it_can_translate_using_dummy_manager_using_db(): void
    {
        $this->createLanguageLine('file', 'key', ['en' => 'en value from db']);
        $this->assertEquals('en value from db', trans('file.key'));
    }

    /** @test */
    public function it_can_translate_using_dummy_manager_using_file_with_incomplete_db(): void
    {
        $this->createLanguageLine('file', 'key', ['nl' => 'nl value from db']);
        $this->assertEquals('en value', trans('file.key'));
    }

    /** @test */
    public function it_can_translate_using_dummy_manager_using_empty_translation_in_db(): void
    {
        $this->createLanguageLine('file', 'key', ['en' => '']);
        $this->assertSame('', trans('file.key'));
    }
}
