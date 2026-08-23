<div align="center">
    <h1>Darauf</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/clicamal/darauf"><img src="https://img.shields.io/packagist/v/clicamal/darauf.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/clicamal/darauf"><img src="https://img.shields.io/packagist/php-v/clicamal/darauf.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/clicamal/darauf"><img src="https://badge.laravel.cloud/badge/clicamal/darauf?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/clicamal/darauf/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/clicamal/darauf/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/clicamal/darauf"><img src="https://img.shields.io/packagist/dt/clicamal/darauf.svg?style=flat-square" alt="Total Downloads"></a>
</p>

A simple DID protocol compatible authentication system for Laravel.

## Status

Currently under development.

## Installation

You can install the package via Composer:

```bash
composer require clicamal/darauf
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="darauf"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="darauf-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="darauf-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="darauf-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="darauf-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="darauf-assets"
```

## Usage

<!-- Add a basic usage example here. -->

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Darauf! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [clicamal](https://github.com/clicamal)
- [All Contributors](../../contributors)

## License

Darauf is open-sourced software licensed under the [MIT license](LICENSE.md).
