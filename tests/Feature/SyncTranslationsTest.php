<?php

namespace Novius\TranslationLoader\Test\Feature;

use Illuminate\Support\Facades\Artisan;
use Novius\TranslationLoader\Actions\DbToFile;
use Novius\TranslationLoader\Actions\FileToDb;
use Novius\TranslationLoader\LanguageLine;
use Novius\TranslationLoader\Test\TestCase;

class SyncTranslationsTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('translation-loader.locales', ['en', 'fr']);
    }

    /** @test */
    public function it_can_sync_translations_from_files_to_db()
    {
        // On s'assure que le dossier fr existe pour les tests
        $frPath = $this->getFixturesDirectory('lang/fr');
        if (! is_dir($frPath)) {
            mkdir($frPath, 0755, true);
        }
        file_put_contents($frPath.'/file.php', "<?php\n\nreturn ['key' => 'valeur fr'];");

        // On réinitialise le loader pour qu'il prenne en compte les changements de config/fichiers
        $this->app->forgetInstance('translation.loader');
        $this->app->singleton('translation.loader', function ($app) {
            $class = config('translation-loader.translation_manager');

            return new $class($app['files'], $app['path.lang']);
        });

        Artisan::call('translations:sync');

        $this->assertDatabaseHas('language_lines', [
            'namespace' => '*',
            'group' => 'file',
            'key' => 'key',
            'orphan' => 0,
        ]);

        $line = LanguageLine::where('group', 'file')->where('key', 'key')->first();
        $this->assertEquals(['en' => 'en value', 'fr' => 'valeur fr'], $line->text);
        $this->assertEquals(['en' => 'en value', 'fr' => 'valeur fr'], $line->text_from_files);
        $this->assertNull($line->dirty_locales);
    }

    /** @test */
    public function it_detects_dirty_translations()
    {
        $line = $this->createLanguageLine('file', 'key', ['en' => 'db value', 'fr' => 'valeur fr']);
        $line->text_from_files = ['en' => 'en value', 'fr' => 'valeur fr'];
        $line->save();

        // Le hook de LanguageLine devrait mettre à jour dirty_locales
        $this->assertEquals(['en' => true], $line->dirty_locales);
    }

    /** @test */
    public function it_marks_orphan_translations()
    {
        $this->createLanguageLine('old_group', 'old_key', ['en' => 'old value']);

        Artisan::call('translations:sync');

        $line = LanguageLine::where('group', 'old_group')->where('key', 'old_key')->first();
        $this->assertTrue($line->orphan);
        $this->assertNull($line->text_from_files);
    }

    /** @test */
    public function it_can_apply_file_to_db_action()
    {
        $line = $this->createLanguageLine('file', 'key', ['en' => 'db value']);
        $line->text_from_files = ['en' => 'file value'];
        $line->save();

        FileToDb::run(collect([$line]), ['en']);

        $line->refresh();
        $this->assertEquals('file value', $line->text['en']);
        $this->assertNull($line->dirty_locales);
    }

    /** @test */
    public function it_can_apply_db_to_file_action()
    {
        $filePath = $this->getFixturesDirectory('lang/en/temp.php');
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        file_put_contents($filePath, "<?php\n\nreturn ['key' => 'old file value'];");

        $line = new LanguageLine;
        $line->namespace = '*';
        $line->group = 'temp';
        $line->key = 'key';
        $line->text = ['en' => 'new db value'];
        $line->text_from_files = ['en' => 'old file value'];
        $line->save();

        DbToFile::run(collect([$line]), ['en']);

        $line->refresh();
        $this->assertEquals('new db value', $line->text_from_files['en']);
        $this->assertNull($line->dirty_locales);

        // Opcache peut poser problème si on require le même fichier plusieurs fois dans le même processus
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($filePath, true);
        }
        $fileContent = include $filePath;
        $this->assertEquals('new db value', $fileContent['key']);

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
