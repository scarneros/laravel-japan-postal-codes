<?php

namespace Scarneros\JapanPostalCodes;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Scarneros\JapanPostalCodes\Models\JapanPostalCode;

class JapanPostalCodes
{
    /**
     * Look up a postal code (accepts raw, hyphenated, or full‑width input).
     */
    public function lookup(string $postalCode): Collection
    {
        $normalized = static::normalize($postalCode);

        return $this->queryByPostalCode($normalized);
    }

    /** Alias for lookup(). */
    public function search(string $postalCode): Collection
    {
        return $this->lookup($postalCode);
    }

    /** Normalize full‑width / messy input into a clean 7‑digit string. */
    public static function normalize(string $postalCode): string
    {
        // Convert full-width digits to half-width
        $normalized = mb_convert_kana($postalCode, 'as', 'UTF-8');

        // Remove everything except digits
        $normalized = preg_replace('/\D/', '', $normalized);

        return $normalized;
    }

    /** Format a 7‑digit string as NNN-NNNN. */
    public static function format(string $postalCode): string
    {
        $digits = preg_replace('/\D/', '', $postalCode);

        if (mb_strlen($digits) !== 7) {
            return $digits;
        }

        return substr($digits, 0, 3).'-'.substr($digits, 3);
    }

    protected function queryByPostalCode(string $normalizedPostalCode): Collection
    {
        $maxResults = config('japan-postal-codes.search.max_results', 50);

        // Optional caching
        if (config('japan-postal-codes.cache.enabled', false)) {
            $cacheKey = sprintf('japan-pc:%s', $normalizedPostalCode);
            $ttl = config('japan-postal-codes.cache.ttl', 86400);
            $store = config('japan-postal-codes.cache.store');

            return Cache::store($store)->remember($cacheKey, $ttl, function () use ($normalizedPostalCode, $maxResults) {
                return $this->executeQuery($normalizedPostalCode, $maxResults);
            });
        }

        return $this->executeQuery($normalizedPostalCode, $maxResults);
    }

    /**
     * Execute the actual Eloquent query.
     *
     * @return Collection<int, array>
     */
    protected function executeQuery(string $normalizedPostalCode, int $limit): Collection
    {
        return JapanPostalCode::query()
            ->where('postal_code', $normalizedPostalCode)
            ->limit($limit)
            ->get()
            ->map(fn (JapanPostalCode $row) => $this->rowToArray($row));
    }

    /**
     * Convert a JapanPostalCode model into the standard array response.
     */
    protected function rowToArray(JapanPostalCode $row): array
    {
        if ($row->prefecture === '以下に掲載がない場合') {
            return [];
        }

        return [
            'postal_code' => $row->postal_code,
            'postal_code_formatted' => $row->postal_code_formatted,
            'prefecture' => $row->prefecture,
            'city' => $row->city,
            'town' => $row->town,
            'address' => $row->address,
            'kana' => $row->address_kana,
            'romaji' => $row->address_romaji,
        ];
    }
}
