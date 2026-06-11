# Laravel Japan Postal Codes

[![Latest Version](https://img.shields.io/packagist/v/scarneros/laravel-japan-postal-codes.svg)](https://packagist.org/packages/scarneros/laravel-japan-postal-codes)
[![License](https://img.shields.io/github/license/scarneros/laravel-japan-postal-codes)](LICENSE)

A clean, tested Laravel package for looking up Japanese addresses by postal code.  
Supports **kanji**, **kana** (katakana) and **romaji** output, full‑width character normalization, smart CSV imports from Japan Post official data, and an optional JSON API.

---

## Requirements

- **PHP** 8.2 or higher
- **Laravel** 11, 12, or 13
- **Database**: MySQL, PostgreSQL, SQLite, or SQL Server

---

## Installation

```bash
composer require scarneros/laravel-japan-postal-codes
```

### 1. Publish the configuration (optional)

```bash
php artisan vendor:publish --tag=japan-postal-codes-config
```

This copies `config/japan-postal-codes.php` to your application, where you can customise table names, API settings, and more.

### 2. Run the migration

```bash
php artisan migrate
```

This creates the `japan_postal_codes` table (name configurable via `config('japan-postal-codes.table_name')`).

### 3. Import postal code data

The package needs the official CSV datasets from Japan Post. Download the CSV files from the official website:

| Dataset | URL |
|---------|-----|
| **Japanese** (kanji + kana) | [ken_all.zip](https://www.post.japanpost.jp/zipcode/dl/kogaki/zip/ken_all.zip) |
| **Romaji** | [ken_all_rome.zip](https://www.post.japanpost.jp/zipcode/dl/roman/ken_all_rome.zip) |

> ⚠️ Both files are Shift-JIS encoded and provided as ZIP archives. The importer handles extraction and encoding automatically.

**Option A — Let the package download the files for you:**

```bash
php artisan japan-postal-codes:import
```

**Option B — Use local files you've already downloaded:**

```bash
php artisan japan-postal-codes:import --file=/path/to/ken_all.zip
php artisan japan-postal-codes:import --file=/path/to/KEN_ALL_ROME.CSV --type=romaji
```

**Option C — Import only one dataset:**

```bash
php artisan japan-postal-codes:import --type=jp      # kanji + kana only
php artisan japan-postal-codes:import --type=romaji   # romaji only
```

### 4. Updating data later

Japan Post publishes updates periodically. To pull the latest data without losing what you already have:

```bash
php artisan japan-postal-codes:update
```

The update command **never overwrites existing data** — it only backfills fields that are currently `NULL`. You can run it as often as you like.

---

## Usage

### Facade (recommended)

```php
use Scarneros\JapanPostalCodes\Facades\PostalCode;

$addresses = PostalCode::lookup('150-0001');

/*
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
*/
```

### Helper methods

```php
// search() is an alias for lookup()
PostalCode::search('160-0023');

// Accepts raw (1600023), hyphenated (160-0023), or full‑width (１６０ー００２３)
PostalCode::lookup('160-0023');
PostalCode::lookup('1600023');

// Normalize full‑width / messy input
PostalCode::normalize('１６０ー００２３');   // "1600023"
PostalCode::normalize('160-0023');    // "1600023"

// Format a 7‑digit number with hyphen
PostalCode::format('1600023');        // "160-0023"
```

### Without the facade

```php
use Scarneros\JapanPostalCodes\JapanPostalCodes;

$service = app(JapanPostalCodes::class);

$results = $service->lookup('150-0001');
```

### Eloquent model

The package includes a `JapanPostalCode` Eloquent model you can query directly:

```php
use Scarneros\JapanPostalCodes\Models\JapanPostalCode;

$record = JapanPostalCode::where('postal_code', '1600023')->first();

echo $record->address;        // "東京都新宿区西新宿"
echo $record->address_kana;   // "トウキョウト シンジュクク ニシシンジュク"
echo $record->address_romaji; // "TOKYO SHINJUKU-KU NISHI-SHINJUKU"
```

---

## JSON API

If enabled in config, the package registers an API route automatically:

```
GET /api/postal-codes/{postalCode}
```

**Example request:**

```
GET /api/postal-codes/160-0023
```

**Example response:**

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

**Invalid postal code (non‑7‑digit):**

```json
{
  "error": "Invalid postal code format.",
  "message": "A Japanese postal code must contain exactly 7 digits."
}
```

> HTTP status code: `422 Unprocessable Entity`

---

## Configuration reference

Publish the config file to see all options:

```bash
php artisan vendor:publish --tag=japan-postal-codes-config
```

| Key | Default | Description |
|-----|---------|-------------|
| `table_name` | `japan_postal_codes` | Database table name |
| `api.enabled` | `true` | Enable/disable the JSON API endpoint |
| `api.prefix` | `api` | Route prefix |
| `api.middleware` | `['api']` | Middleware stack |
| `api.route_uri` | `postal-codes` | URI segment for the lookup route |
| `import.chunk_size` | `500` | Rows per batch during CSV import |
| `import.csv_urls.jp` | Japan Post URL | KEN_ALL.CSV download URL |
| `import.csv_urls.romaji` | Japan Post URL | KEN_ALL_ROME.CSV download URL |
| `search.max_results` | `50` | Max rows returned per query |
| `cache.enabled` | `false` | Enable caching of lookups |
| `cache.ttl` | `86400` | Cache duration in seconds |
| `cache.store` | `null` | Cache store (null = default) |

---

## CSV Merging Strategy

The package supports two CSV files from Japan Post:

- **KEN_ALL.CSV** — Japanese addresses with kanji and kana (katakana)
- **KEN_ALL_ROME.CSV** — Japanese addresses with romaji (romanized)

You can import either file first. The importer merges them intelligently:

1. First import creates rows with the available fields
2. Second import **only fills `NULL` fields** in existing rows — it never overwrites data you already have
3. If you import only one file, the corresponding fields simply stay `NULL`

---

## Artisan Commands

| Command | Description |
|---------|-------------|
| `japan-postal-codes:import` | Download & import postal code CSV data |
| `japan-postal-codes:update` | Update existing data with latest CSV files |

### Import options

| Option | Description |
|--------|-------------|
| `--file=` | Path to a local CSV or ZIP file |
| `--type=jp` | Import only Japanese (kanji + kana) data |
| `--type=romaji` | Import only romaji data |
| `--type=jp --type=romaji` | Import both (default) |
| `--chunk=1000` | Override chunk size |

### Update options

| Option | Description |
|--------|-------------|
| `--type=` | Same as import |
| `--chunk=` | Same as import |
| `--force` | Skip confirmation prompt |

---

## Testing

```bash
composer test
```

The test suite uses Orchestra Testbench with an in‑memory SQLite database — no setup required.

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## Security

If you discover any security related issues, please email sergi@scarneros.com instead of using the issue tracker.

---

## Credits

- [Sergi Carneros](https://github.com/scarneros)
- [All Contributors](../../contributors)
- Data provided by [Japan Post](https://www.post.japanpost.jp/zipcode/dl/)

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
