<?php

namespace Scarneros\JapanPostalCodes\Facades;

use Illuminate\Support\Facades\Facade;
use Scarneros\JapanPostalCodes\JapanPostalCodes;

/**
 * @method static \Illuminate\Support\Collection lookup(string $postalCode)
 * @method static \Illuminate\Support\Collection search(string $postalCode)
 * @method static string normalize(string $postalCode)
 * @method static string format(string $postalCode)
 *
 * @see JapanPostalCodes
 */
class PostalCode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'japan-postal-codes';
    }
}
