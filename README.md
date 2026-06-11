# Laravel Japan Postal Codes

[![Latest Version](https://img.shields.io/packagist/v/scarneros/laravel-japan-postal-codes.svg)](https://packagist.org/packages/scarneros/laravel-japan-postal-codes)
[![Tests](https://github.com/scarneros/laravel-japan-postal-codes/actions/workflows/tests.yml/badge.svg)](https://github.com/scarneros/laravel-japan-postal-codes/actions/workflows/tests.yml)
[![License](https://img.shields.io/github/license/scarneros/laravel-japan-postal-codes)](LICENSE)

Look up Japanese addresses by postal code. Returns **kanji**, **kana** and **romaji**.  
Supports full‑width character normalization, CSV imports from [Japan Post](https://www.post.japanpost.jp/service/search/zipcode/download/readme.html), and an optional JSON API.

---

## Requirements

- **PHP** 8.2+ with `ext-mbstring` and `ext-zip`
- **Laravel** 11, 12, or 13
- Any database supported by Laravel

---

## Installation

```bash
composer require scarneros/laravel-japan-postal-codes
```

### 1. Publish the config (optional)

```bash
php artisan vendor:publish --tag=japan-postal-codes-config
```

### 2. Run the migration

```bash
php artisan migrate
```

### 3. Import the postal code data

```bash
php artisan japan-postal-codes:import
```

This downloads and imports both official CSV datasets from Japan Post (kanji + kana, and romaji).

| Option          | Description                                   |
| --------------- | --------------------------------------------- |
| `--file=`       | Use a local CSV or ZIP instead of downloading |
| `--type=jp`     | Import only Japanese (kanji + kana)           |
| `--type=romaji` | Import only romaji                            |
| `--chunk=1000`  | Rows per batch (default: 500)                 |

> The importer never overwrites data when you import a second dataset — it only fills in `NULL` fields. This means you can run `import --type=jp` and `import --type=romaji` in any order.

### 4. Keep data up to date

```bash
php artisan japan-postal-codes:update
```

The update command downloads the latest CSV files and **overwrites** any changed fields. Use `--force` to skip the confirmation prompt.

---

## Usage

```php
use Scarneros\JapanPostalCodes\Facades\PostalCode;

// Lookup — accepts raw, hyphenated, or full‑width input
PostalCode::lookup('150-0001');
PostalCode::lookup('1500001');
PostalCode::lookup('１５０ー０００１');

// search() is an alias for lookup()
PostalCode::search('160-0023');

// Normalize messy input to a clean 7‑digit string
PostalCode::normalize('１６０ー００２３'); // "1600023"

// Format a 7‑digit number with hyphen
PostalCode::format('1600023'); // "160-0023"
```

**Return value:**

```php
// PostalCode::lookup('150-0001')
[
    [
        'postal_code'           => '1500001',
        'postal_code_formatted' => '150-0001',
        'prefecture'            => '東京都',
        'city'                  => '渋谷区',
        'town'                  => '神宮前',
        'address'               => '東京都渋谷区神宮前',
        'kana'                  => 'トウキョウト シブヤク ジングウマエ',
        'romaji'                => 'TOKYO SHIBUYA-KU JINGUMAE',
    ],
]
```

### Eloquent model

```php
use Scarneros\JapanPostalCodes\Models\JapanPostalCode;

$row = JapanPostalCode::where('postal_code', '1600023')->first();

$row->address; // "東京都新宿区西新宿"
$row->address_kana; // "トウキョウト シンジュクク ニシシンジュク"
$row->address_romaji; // "TOKYO SHINJUKU-KU NISHI-SHINJUKU"
```

---

## JSON API

Enabled by default. You can toggle it, change the prefix, or adjust middleware in the published config.

```
GET /api/postal-codes/{postalCode}
```

```json
{
  "data": [
    {
      "postal_code": "1600023",
      "formatted": "160-0023",
      "prefecture": "東京都",
      "city": "新宿区",
      "town": "西新宿",
      "address": "東京都新宿区西新宿",
      "kana": "トウキョウト シンジュクク ニシシンジュク",
      "romaji": "TOKYO SHINJUKU-KU NISHI-SHINJUKU"
    }
  ]
}
```

Invalid postal codes return `422`.

---

## Testing

```bash
composer test
```

---

## License

The MIT License (MIT). Data provided by [Japan Post](https://www.post.japanpost.jp/service/search/zipcode/download/readme.html).
