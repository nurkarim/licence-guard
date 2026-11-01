# Licence Guard

একবার ইনস্টল করলেই Laravel সফটওয়্যারে লাইসেন্স পেজ চালু হয়। `.env`-এ কিছু দিতে হয় না। সফটওয়্যারের নাম আপনি চার জায়গায় লিখে দেন। সেই নামের `.lic` ফাইল ছাড়া লাইভ সাইট খুলবে না।

`localhost`-এ চেক লাগে না। প্রাইভেট কী এই প্যাকেজে নেই। ফাইল বানানো হয় `app-license-server` থেকে।

## 1. ইনস্টল

Packagist-এ প্যাকেজ উঠানোর পর সফটওয়্যারের ফোল্ডারে শুধু এই কমান্ড:

```bat
composer require epixel/licence-guard
```

`repositories` ব্লক লাগবে না। `/licence` পেজ নিজে চালু হয়।

## 2. চার জায়গায় নাম বসান

নিচের `pos` শুধু উদাহরণ। অ্যাকাউন্টিংয়ের জন্য `accounting`, অন্য সফটওয়্যারের জন্য সেই সফটওয়্যারের ছোট নাম দিন। চার জায়গায় একই নাম থাকতে হবে। ছোট হাতের অক্ষর, সংখ্যা, `_` আর `-` চলবে।

### public/index.php

ফাইলের একদম উপরে, `vendor/autoload.php`-এর আগে:

```php
require __DIR__.'/../vendor/epixel/licence-guard/gate.php';
licence_guard('pos', dirname(__DIR__));
```

### bootstrap/app.php

ফাইলের একদম উপরে:

```php
require __DIR__.'/../vendor/epixel/licence-guard/gate.php';
licence_guard('pos', dirname(__DIR__));
```

### বেস কন্ট্রোলার

`app/Http/Controllers/Controller.php`-এর কনস্ট্রাক্টরে:

```php
public function __construct()
{
    \Epixel\LicenceGuard\LicenceChecker::check('pos');
}
```

কন্ট্রোলার আগে থেকে এই ক্লাস এক্সটেন্ড করলে নতুন কন্ট্রোলারে আলাদা করে বসাতে হয় না।

### মেইন লেআউট

যে লেআউট দিয়ে পেজগুলো রেন্ডার হয়, তার হেডারের শুরুতে:

```blade
@include('licence-guard::check', ['product' => 'pos'])
```

## 3. লাইভ সাইট চালু করার ছাপ

ইনস্টল শেষ হলে এই খালি ফাইলটা থাকতে হবে:

`storage/installed.lock`

যে সফটওয়্যারে ইনস্টলার নেই, ডিপ্লয়ের পর এই ফাইলটা একবার বানিয়ে রাখুন। ফাইল না থাকলে লাইভ সাইট খোলা থাকে, যাতে সেটআপ শেষ করা যায়।

## 4. লাইসেন্স ফাইল বানানো

`app-license-server` ফোল্ডারে:

```bat
php artisan licence:issue customerdomain.com --product=pos
```

ফাইল তৈরি হবে:

`storage\app\licence\issued\pos.customerdomain.com.lic`

`--days` না দিলে মেয়াদ শেষ হয় না। এক বছরের জন্য:

```bat
php artisan licence:issue customerdomain.com --product=pos --days=365
```

`--product` না দিলে পুরনো অ্যাকাউন্টিং সফটওয়্যারের নামই বসে: `inventory_accounting_system`।

এই `.lic` ফাইল কাস্টমারকে পাঠান। `private.pem` পাঠাবেন না।

## 5. কাস্টমার কী করবে

1. সফটওয়্যার নিজের হোস্টিংয়ে তুলবে।
2. `https://customerdomain.com/licence` খুলবে।
3. আপনার দেওয়া `.lic` ফাইল আপলোড করবে।

ডোমেইন আর সফটওয়্যারের নাম মিললে সফটওয়্যার খুলবে। `pos`-এর ফাইল `accounting`-এ খাটবে না। অন্য ডোমেইনেও খাটবে না।

## কোন জায়গা খোলা থাকে

- `localhost`, `127.0.0.1`
- `/installer`
- `/licence`
- `storage/installed.lock` না থাকলে পুরো সাইট

লাইভ ডোমেইনে লক ফাইল থাকলে বাকি সব পেজ লাইসেন্স চায়।
