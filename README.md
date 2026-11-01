# Licence Guard

Licence Guard keeps a Laravel application closed on a live domain until a valid licence file is uploaded. The file is signed for one product and one domain. A licence for `pos` will not open `accounting`, and a file made for one website will not open another.

Nothing is read from `.env`. You choose a short product name and place that same name in four spots. Local development on `localhost` stays open.

## Requirements

- PHP 8.1 or newer
- Laravel 10, 11, or 12

## Install

```bash
composer require epixel/licence-guard
```

The package registers the `/licence` page on its own.

## 1. Choose a product name

Use one short name for the whole application, such as `pos` or `accounting`. Use the same name in every step below.

Allowed characters are lowercase letters, numbers, `_`, and `-`. The name must start with a letter or number and can be up to 41 characters.

## 2. Connect the guard

### `public/index.php`

Place this at the top of the file, before `vendor/autoload.php` is loaded:

```php
require __DIR__.'/../vendor/epixel/licence-guard/gate.php';
licence_guard('pos', dirname(__DIR__));
```

### `bootstrap/app.php`

Place the same two lines at the top of the file:

```php
require __DIR__.'/../vendor/epixel/licence-guard/gate.php';
licence_guard('pos', dirname(__DIR__));
```

### Base controller

In `app/Http/Controllers/Controller.php`:

```php
public function __construct()
{
    \Epixel\LicenceGuard\LicenceChecker::check('pos');
}
```

Controllers that extend this class are covered. You do not add the check to each controller.

### Main layout

At the top of the layout that renders your pages:

```blade
@include('licence-guard::check', ['product' => 'pos'])
```

## 3. Turn the check on

After setup, the application needs an empty file:

```text
storage/installed.lock
```

If the application has no installer, create that file once when you deploy. Until the file exists, the live site stays open so setup can finish.

## 4. Activate

On the live website, open:

```text
https://customerdomain.com/licence
```

Upload the `.lic` file, or paste its contents. When the product name and domain match, the application opens.

## Issuing a licence

From the licence server:

```bash
php artisan licence:issue customerdomain.com --product=pos
```

The file is written to:

```text
storage/app/licence/issued/pos.customerdomain.com.lic
```

Send that file to the customer. Omit `--days` for a licence that does not expire. For one year:

```bash
php artisan licence:issue customerdomain.com --product=pos --days=365
```

If `--product` is omitted, the name `inventory_accounting_system` is used.

## What stays open

- `localhost` and `127.0.0.1`
- `/installer`
- `/licence`
- Any site that does not yet have `storage/installed.lock`

On a live domain, once `storage/installed.lock` exists, every other page asks for a valid licence.
