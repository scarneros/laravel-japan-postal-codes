<?php

use Illuminate\Support\Facades\Route;
use Scarneros\JapanPostalCodes\Http\Controllers\PostalCodeController;

/*
|--------------------------------------------------------------------------
| Japan Postal Codes — API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the JapanPostalCodesServiceProvider when the
| API feature is enabled in the configuration.
|
*/

Route::prefix(config('japan-postal-codes.api.prefix', 'api'))
    ->middleware(config('japan-postal-codes.api.middleware', ['api']))
    ->group(function () {
        $uri = config('japan-postal-codes.api.route_uri', 'postal-codes');

        Route::get("{$uri}/{postalCode}", [PostalCodeController::class, 'show'])
            ->name('japan-postal-codes.lookup');
    });
