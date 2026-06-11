<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the table where Japanese postal codes will be stored.
    | You may change this before running the migration if needed.
    |
    */
    'table_name' => 'japan_postal_codes',

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Controls whether the built-in JSON API endpoint is enabled, along with
    | its route prefix and middleware stack.
    |
    */
    'api' => [
        'enabled' => true,

        'prefix' => 'api',

        'middleware' => ['api'],

        /*
        |--------------------------------------------------------------------------
        | API Route URI
        |--------------------------------------------------------------------------
        |
        | The URI segment used for the lookup endpoint.
        | Example: /api/postal-codes/160-0023
        |
        */
        'route_uri' => 'postal-codes',
    ],

    /*
    |--------------------------------------------------------------------------
    | CSV Import Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the artisan commands that import CSV data from the
    | official Japan Post website.
    |
    */
    'import' => [
        /*
        |--------------------------------------------------------------------------
        | Chunk Size
        |--------------------------------------------------------------------------
        |
        | Number of rows to process per chunk during CSV import. This helps
        | keep memory usage under control for the large postal code datasets.
        |
        */
        'chunk_size' => 500,

        /*
        |--------------------------------------------------------------------------
        | Remote CSV URLs (Japan Post official data)
        |--------------------------------------------------------------------------
        |
        | These are the official download URLs from Japan Post. They may
        | change over time; update them here if needed.
        |
        | KEN_ALL.CSV  — Japanese (kanji + kana) data
        | KEN_ALL_ROME.CSV — Romanized (romaji) data
        |
        */
        'csv_urls' => [
            'jp' => 'https://www.post.japanpost.jp/service/search/zipcode/download/kogaki/zip/ken_all.zip',
            'romaji' => 'https://www.post.japanpost.jp/service/search/zipcode/download/roman/KEN_ALL_ROME.zip',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    |
    | Options controlling the search() and lookup() behaviour.
    |
    */
    'search' => [
        /*
        |--------------------------------------------------------------------------
        | Maximum Results
        |--------------------------------------------------------------------------
        |
        | Limit the number of rows returned by search/lookup queries.
        | A single postal code rarely maps to more than a handful of addresses.
        |
        */
        'max_results' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Postal code data rarely changes; caching lookups can greatly improve
    | performance for high-traffic applications.
    |
    */
    'cache' => [
        'enabled' => false,

        'ttl' => 86400, // 24 hours

        'store' => null, // null = default cache store
    ],
];
