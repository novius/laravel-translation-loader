<?php

namespace Novius\TranslationLoader\Console;

use Illuminate\Console\Command;

class ResetTranslations extends Command
{
    protected $signature = 'translations:reset';

    protected $description = 'Clear all DB translations and re-import them';

    public function handle(): void
    {
        if (! $this->confirm('All current translations will be lost. Do you wish to continue ?')) {
            $this->error('Bye.');

            return;
        }

        $this->info('Delete current translations...');
        (config('translation-loader.model'))::query()->truncate();

        $this->call('cache:clear');

        $this->call('translations:sync');
    }
}
