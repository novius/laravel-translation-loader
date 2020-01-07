# Laravel Translation Loader
[![Travis](https://img.shields.io/travis/novius/laravel-translation-loader.svg?maxAge=1800&style=flat-square)](https://travis-ci.org/novius/laravel-translation-loader)
[![Packagist Release](https://img.shields.io/packagist/v/novius/laravel-translation-loader.svg?maxAge=1800&style=flat-square)](https://packagist.org/packages/novius/laravel-translation-loader)
[![Licence](https://img.shields.io/packagist/l/novius/laravel-translation-loader.svg?maxAge=1800&style=flat-square)](https://github.com/novius/laravel-translation-loader#licence)

This package is an adaptation of [spatie/laravel-translation-loader](https://github.com/spatie/laravel-translation-loader)

> **WARNING**: this package is currently in development.

## Features added

* Translations namespace compatibility ;
* Console commands to synchronise translations from files to DB;

## Requirements

* PHP >= 7.2
* Laravel Framework >= 5.8

## Installation

```sh
composer require novius/laravel-translation-loader:dev-master
```

In `config/app.php` (Laravel) or `bootstrap/app.php` (Lumen) you should replace Laravel's translation service provider

```php
Illuminate\Translation\TranslationServiceProvider::class,
```

by the one included in this package:

```php
Novius\TranslationLoader\TranslationServiceProvider::class,
```

You must publish and run the migrations to create the `language_lines` table:

```bash
php artisan vendor:publish --provider="Novius\TranslationLoader\TranslationServiceProvider" --tag="migrations"
php artisan migrate
```

Publish languages files:

```bash
php artisan vendor:publish --provider="Novius\TranslationLoader\TranslationServiceProvider" --tag="lang"
```

Optionally you could publish the config file using this command.

```bash
php artisan vendor:publish --provider="Novius\TranslationLoader\TranslationServiceProvider" --tag="config"
```


> **Note:** publishing assets doesn't work out of the box in Lumen. Instead you have to copy the files from the repo.

## Commands

```bash
# Synchronise translations from files to DB
php artisan translations:sync

# Clear DB translations + re-import them
php artisan translations:reset
```

## Lint

Run php-cs with:

```sh
composer run-script lint
```

## Contributing

Contributions are welcome!
Leave an issue on Github, or create a Pull Request.


## Licence

This package is under MIT Licence.
