<?php

namespace Scarneros\JapanPostalCodes\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Scarneros\JapanPostalCodes\Services\CsvImporter;
use ZipArchive;

class UpdatePostalCodesCommand extends Command
{
    protected $signature = 'japan-postal-codes:update
                            {--type=* : Data types to update: jp, romaji (default: both)}
                            {--chunk= : Override the chunk size from config}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Update Japanese postal code data with the latest CSV files';

    public function handle(CsvImporter $importer): int
    {
        $table = config('japan-postal-codes.table_name', 'japan_postal_codes');
        $count = DB::table($table)->count();

        if ($count === 0) {
            $this->warn('No existing postal code data found.');
            $this->info('Please run `php artisan japan-postal-codes:import` to perform the initial import.');

            return self::FAILURE;
        }

        $this->info("Found {$count} existing postal code records.");
        $this->line('Existing data WILL be overwritten with fresh CSV values.');
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('Proceed with the update?', true)) {
                $this->info('Update cancelled.');

                return self::SUCCESS;
            }
        }

        $types = $this->normalizeTypes();
        $chunk = (int) ($this->option('chunk') ?: config('japan-postal-codes.import.chunk_size', 500));

        $this->info('Starting update...');
        $this->line(sprintf('Types: %s | Chunk size: %d', implode(', ', $types), $chunk));

        foreach ($types as $type) {
            $this->processType($importer, $type, $chunk);
        }

        $this->newLine();
        $this->info('Update finished successfully.');

        return self::SUCCESS;
    }

    protected function processType(CsvImporter $importer, string $type, int $chunk): void
    {
        $label = $type === 'jp' ? 'Japanese (kanji + kana)' : 'Romaji';

        $this->newLine();
        $this->info("── Updating {$label} data ──");

        try {
            $csvPath = $this->downloadAndExtract($type);

            $this->line("Reading: {$csvPath}");

            ['inserted' => $ins, 'updated' => $upd, 'skipped' => $skip] =
                $importer->import($csvPath, $type, $chunk, overwrite: true);

            $this->table(
                ['Inserted', 'Updated', 'Skipped'],
                [[(string) $ins, (string) $upd, (string) $skip]],
            );

            @unlink($csvPath);
        } catch (\Throwable $e) {
            $this->error("Error updating {$type}: ".$e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
        }
    }

    protected function downloadAndExtract(string $type): string
    {
        $url = config("japan-postal-codes.import.csv_urls.{$type}");

        if (! $url) {
            throw new \RuntimeException("No URL configured for type: {$type}");
        }

        $this->line("Downloading: {$url}");

        $zipContents = file_get_contents($url);

        if ($zipContents === false) {
            throw new \RuntimeException("Failed to download: {$url}");
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'jpc-zip-');
        file_put_contents($tmpZip, $zipContents);

        $zip = new ZipArchive;
        $extractDir = sys_get_temp_dir().'/jpc-extract-'.uniqid();

        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);

            throw new \RuntimeException('Failed to open ZIP archive.');
        }

        @mkdir($extractDir, 0755, true);
        $zip->extractTo($extractDir);
        $zip->close();
        @unlink($tmpZip);

        $files = glob($extractDir.'/*.[cC][sS][vV]');

        if (empty($files)) {
            throw new \RuntimeException('No CSV file found in the downloaded archive.');
        }

        return $files[0];
    }

    protected function normalizeTypes(): array
    {
        $types = array_filter((array) $this->option('type'));

        if (empty($types)) {
            return ['jp', 'romaji'];
        }

        return array_intersect($types, ['jp', 'romaji']);
    }
}
