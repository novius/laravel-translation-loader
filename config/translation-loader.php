<?php

return [

    /*
     * Language lines will be fetched by these loaders. You can put any class here that implements
     * the Novius\TranslationLoader\TranslationLoaders\TranslationLoader-interface.
     */
    'translation_loaders' => [
        Novius\TranslationLoader\TranslationLoaders\Db::class,
    ],

    /*
     * This is the model used by the Db Translation loader. You can put any model here
     * that extends Novius\TranslationLoader\LanguageLine.
     */
    'model' => Novius\TranslationLoader\LanguageLine::class,

    /*
     * This is the translation manager which overrides the default Laravel `translation.loader`
     */
    'translation_manager' => Novius\TranslationLoader\TranslationLoaderManager::class,

    /**
     * Available locales for translations
     */
    'locales' => [
        'en',
    ],

    /**
     * Available remote directory for translations
     * Note : The key is used in the namespace column of the database
     */
    'remote_directory' => [
        // 'my-package-name' => 'vendor/my-package-name/lang'
    ],

    /**
     * Translation files you want to exclude
     */
    'exclude_files' => [
        // 'my-filename.php',
        // 'my-directory/my-filename.php',
    ],
];
