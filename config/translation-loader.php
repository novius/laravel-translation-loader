<?php

use Novius\TranslationLoader\LanguageLine;
use Novius\TranslationLoader\TranslationLoaderManager;
use Novius\TranslationLoader\TranslationLoaders\Db;

return [

    /*
     * Language lines will be fetched by these loaders. You can put any class here that implements
     * the Novius\TranslationLoader\TranslationLoaders\TranslationLoader-interface.
     */
    'translation_loaders' => [
        Db::class,
    ],

    /*
     * This is the model used by the Db Translation loader. You can put any model here
     * that extends Novius\TranslationLoader\LanguageLine.
     */
    'model' => LanguageLine::class,

    /*
     * This is the translation manager which overrides the default Laravel `translation.loader`
     */
    'translation_manager' => TranslationLoaderManager::class,

    /**
     * Available locales for translations
     * If leave empty, all locales set will be used
     */
    'locales' => [
    ],

    /**
     * Available remote directory for translations
     * Note : The key is used in the namespace column of the database
     */
    'remote_directory' => [
        // 'my-package-name' => 'vendor/my-package-name/lang'
    ],
];
