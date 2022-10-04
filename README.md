# Draft Status

[![Latest Version on Packagist](https://img.shields.io/packagist/v/trinityrank/sitemap.svg?style=flat-square)](https://packagist.org/packages/trinityrank/sitemap)
[![Total Downloads](https://img.shields.io/packagist/dt/trinityrank/sitemap.svg?style=flat-square)](https://packagist.org/packages/trinityrank/sitemap)

---

## Installation

You can install the package via composer:

```bash
composer require trinityrank/sitemap
```

## How To Use

This package has multilang support. It is compatibile with `trinityrank/multilanguage`.
For default language we use config `app.locale` value.


### Step 1: Publishing

You need to publish file from package:

```shell
    php artisan vendor:publish --provider="Trinityrank\Sitemap\SitemapServiceProvider" --tag="generate-sitemap-files"
```

And change settings array according to your needs

### Step 2: Usage

Add traits to fix missing scopes

- For Categories
    ```shell
    use Trinityrank\Sitemap\Traits\ScopeSitemapCategory;
    ...
    use ScopeSitemapCategory
    ```

- For User class
    ```shell
    use Trinityrank\Sitemap\Traits\ScopeSitemapAuthor;
    ...
    use ScopeSitemapAuthor
    ```
    

## Step 3: Create sitemap

- Now it is time to create your sitemap
    ```shell
    php artisan sitemap:generate
    ```

- You can also put it into your Kernel.php
    ```shell
    $schedule->command('sitemap:generate')->dailyAt('03:00');
    ```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
