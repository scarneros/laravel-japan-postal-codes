<?php

namespace Scarneros\JapanPostalCodes\Models;

use Illuminate\Database\Eloquent\Model;

class JapanPostalCode extends Model
{
    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('japan-postal-codes.table_name', 'japan_postal_codes'));
    }

    /** Full kanji address (prefecture + city + town). */
    public function getAddressAttribute(): string
    {
        return collect([$this->prefecture, $this->city, $this->town])
            ->filter()
            ->implode('');
    }

    /** Full kana address. */
    public function getAddressKanaAttribute(): string
    {
        return collect([$this->prefecture_kana, $this->city_kana, $this->town_kana])
            ->filter()
            ->implode(' ');
    }

    /** Full romaji address. */
    public function getAddressRomajiAttribute(): string
    {
        return collect([$this->prefecture_romaji, $this->city_romaji, $this->town_romaji])
            ->filter()
            ->implode(' ');
    }
}
