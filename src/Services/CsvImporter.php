<?php

namespace Scarneros\JapanPostalCodes\Services;

use Illuminate\Support\Facades\DB;
use Scarneros\JapanPostalCodes\JapanPostalCodes;

class CsvImporter
{
    protected const JP_MIN_COLS = 9;

    protected const ROMAJI_MIN_COLS = 7;

    /**
     * Import a CSV file by type ('jp' or 'romaji').
     *
     * When $overwrite is false (import), existing rows are only updated where
     * fields are NULL — safe for merging two CSVs without data loss.
     * When $overwrite is true (update), existing rows are overwritten with
     * fresh CSV data.
     */
    public function import(string $filePath, string $type, int $chunkSize = 500, bool $overwrite = false): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new \RuntimeException("File not found or not readable: {$filePath}");
        }

        $handle = fopen($filePath, 'r');

        if (! $handle) {
            throw new \RuntimeException("Failed to open file: {$filePath}");
        }

        // Detect encoding and apply stream filter
        stream_filter_append($handle, 'convert.iconv.SJIS/UTF-8//TRANSLIT');

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $batch = [];

        while (($row = fgetcsv($handle)) !== false) {
            $parsed = $type === 'jp'
                ? $this->parseJpRow($row)
                : $this->parseRomajiRow($row);

            if ($parsed === null) {
                $skipped++;

                continue;
            }

            $batch[] = $parsed;

            if (count($batch) >= $chunkSize) {
                [$ins, $upd] = $this->upsertBatch($batch, $type, $overwrite);
                $inserted += $ins;
                $updated += $upd;
                $batch = [];
            }
        }

        // Process remaining rows
        if (! empty($batch)) {
            [$ins, $upd] = $this->upsertBatch($batch, $type, $overwrite);
            $inserted += $ins;
            $updated += $upd;
        }

        fclose($handle);

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Parse a row from KEN_ALL.CSV (kanji + kana).
     */
    protected function parseJpRow(array $row): ?array
    {
        if (count($row) < self::JP_MIN_COLS) {
            return null;
        }

        $postalCode = trim($row[2]);

        if (mb_strlen($postalCode) !== 7 || ! is_numeric($postalCode)) {
            return null;
        }

        $prefecture = trim($row[6]);
        $city = trim($row[7]);
        $town = trim($row[8]);

        // Skip "以下に掲載がない場合" rows (generic placeholder for large areas)
        if ($town === '以下に掲載がない場合') {
            return null;
        }

        return [
            'postal_code' => $postalCode,
            'postal_code_formatted' => JapanPostalCodes::format($postalCode),
            'prefecture' => $prefecture,
            'city' => $city,
            'town' => $town,
            'prefecture_kana' => trim($row[3]) ?: null,
            'city_kana' => trim($row[4]) ?: null,
            'town_kana' => trim($row[5]) ?: null,
            'prefecture_romaji' => null,
            'city_romaji' => null,
            'town_romaji' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Parse a row from KEN_ALL_ROME.CSV (romaji).
     */
    protected function parseRomajiRow(array $row): ?array
    {
        if (count($row) < self::ROMAJI_MIN_COLS) {
            return null;
        }

        $postalCode = trim($row[0]);

        if (mb_strlen($postalCode) !== 7 || ! is_numeric($postalCode)) {
            return null;
        }

        return [
            'postal_code' => $postalCode,
            'postal_code_formatted' => JapanPostalCodes::format($postalCode),
            'prefecture' => trim($row[1]),
            'city' => trim($row[2]),
            'town' => trim($row[3]) ?: null,
            'prefecture_kana' => null,
            'city_kana' => null,
            'town_kana' => null,
            'prefecture_romaji' => trim($row[4]) ?: null,
            'city_romaji' => trim($row[5]) ?: null,
            'town_romaji' => trim($row[6]) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Upsert a batch of rows. Existing rows are only updated where fields are NULL.
     */
    protected function upsertBatch(array $batch, string $type, bool $overwrite): array
    {
        $table = config('japan-postal-codes.table_name', 'japan_postal_codes');
        $inserted = 0;
        $updated = 0;

        DB::transaction(function () use ($batch, $type, $table, $overwrite, &$inserted, &$updated) {
            foreach ($batch as $data) {
                $existing = DB::table($table)
                    ->where('postal_code', $data['postal_code'])
                    ->first();

                if (! $existing) {
                    DB::table($table)->insert($data);
                    $inserted++;
                } else {
                    $updates = $this->buildMergeUpdates($existing, $data, $type, $overwrite);

                    if (! empty($updates)) {
                        $updates['updated_at'] = now();

                        DB::table($table)
                            ->where('id', $existing->id)
                            ->update($updates);

                        $updated++;
                    }
                }
            }
        });

        return [$inserted, $updated];
    }

    protected function buildMergeUpdates(object $existing, array $incoming, string $type, bool $overwrite): array
    {
        $updates = [];

        $fillableFields = match ($type) {
            'jp' => ['prefecture', 'city', 'town', 'prefecture_kana', 'city_kana', 'town_kana'],
            'romaji' => ['prefecture', 'city', 'town', 'prefecture_romaji', 'city_romaji', 'town_romaji'],
        };

        foreach ($fillableFields as $field) {
            if ($overwrite) {
                // Overwrite mode: update if incoming has a non-empty value
                if (! empty($incoming[$field])) {
                    $updates[$field] = $incoming[$field];
                }
            } else {
                // Merge mode: only fill NULL fields
                if (empty($existing->{$field}) && ! empty($incoming[$field])) {
                    $updates[$field] = $incoming[$field];
                }
            }
        }

        return $updates;
    }
}
