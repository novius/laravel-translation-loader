# Laravel Translation Loader

[![Novius CI](https://github.com/novius/laravel-translation-loader/actions/workflows/master.yml/badge.svg?branch=master)](https://github.com/novius/laravel-translation-loader/actions/workflows/master.yml)
[![Packagist Release](https://img.shields.io/packagist/v/novius/laravel-translation-loader.svg?maxAge=1800&style=flat-square)](https://packagist.org/packages/novius/laravel-translation-loader)
[![Licence](https://img.shields.io/packagist/l/novius/laravel-translation-loader.svg?maxAge=1800&style=flat-square)](https://github.com/novius/laravel-translation-loader#licence)

This package is an adaptation of [spatie/laravel-translation-loader](https://github.com/spatie/laravel-translation-loader)

> **WARNING**: this package is currently in development.

## Features added

* Translations namespace compatibility ;
* Console commands to synchronize translations from files to DB;
* Manage differences between file translations and DB translations;
* Detect orphan translations (translations in DB but no longer in files);
* Identify "dirty" locales (DB translation different from file translation);
* Actions to sync specific translations (File to DB or DB to file);

## Requirements

* PHP >= 8.1
* Laravel Framework >= 9.0

> **NOTE**: These instructions are for Laravel >= 9.0. If you are using prior version, please
> see the [previous version's docs](https://github.com/novius/laravel-translation-loader/tree/2.x).


## Installation

```sh
composer require novius/laravel-translation-loader:dev-master
```

In `config/app.php` (Laravel) you should replace Laravel's translation service provider

```php
Illuminate\Translation\TranslationServiceProvider::class,
```

by the one included in this package:

```php
Novius\TranslationLoader\TranslationServiceProvider::class,
```

Run migrations:

```bash
php artisan migrate
```

Publish languages' files:

```bash
php artisan vendor:publish --provider="Novius\TranslationLoader\TranslationServiceProvider" --tag="lang"
```

Optionally, you could publish the config file using this command.

```bash
php artisan vendor:publish --provider="Novius\TranslationLoader\TranslationServiceProvider" --tag="config"
```

## Commands

```bash
# Synchronise translations from files to DB
php artisan translations:sync

# Synchronise and delete translations from DB that are not in files anymore
php artisan translations:sync --clean

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
