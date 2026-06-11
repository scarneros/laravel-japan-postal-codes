<?php

namespace Scarneros\JapanPostalCodes\Console;

use Illuminate\Console\Command;
use Scarneros\JapanPostalCodes\Services\CsvImporter;
use ZipArchive;

class ImportPostalCodesCommand extends Command
{
    protected $signature = 'japan-postal-codes:import
                            {--file= : Path to a local CSV/ZIP file (skips download)}
                            {--type=* : Data types to import: jp, romaji (default: both)}
                            {--chunk= : Override the chunk size from config}';

    protected $description = 'Import Japanese postal codes from Japan Post CSV files';

    public function handle(CsvImporter $importer): int
    {
        $types = $this->normalizeTypes();
        $chunk = (int) ($this->option('chunk') ?: config('japan-postal-codes.import.chunk_size', 500));
        $fileArg = $this->option('file');

        $this->info('Starting Japanese postal code import...');
        $this->line(sprintf('Types: %s | Chunk size: %d', implode(', ', $types), $chunk));

        foreach ($types as $type) {
            $this->processType($importer, $type, $chunk, $fileArg);
        }

        $this->newLine();
        $this->info('Import finished successfully.');

        return self::SUCCESS;
    }

    protected function processType(CsvImporter $importer, string $type, int $chunk, ?string $fileArg): void
    {
        $label = $type === 'jp' ? 'Japanese (kanji + kana)' : 'Romaji';

        $this->newLine();
        $this->info("── Importing {$label} data ──");

        try {
            $csvPath = $fileArg
                ? $this->resolveLocalFile($fileArg)
                : $this->downloadAndExtract($type);

            $this->line("Reading: {$csvPath}");

            ['inserted' => $ins, 'updated' => $upd, 'skipped' => $skip] =
                $importer->import($csvPath, $type, $chunk);

            $this->table(
                ['Inserted', 'Updated', 'Skipped'],
                [[(string) $ins, (string) $upd, (string) $skip]],
            );

            // Cleanup temporary files
            if (! $fileArg) {
                @unlink($csvPath);
            }
        } catch (\Throwable $e) {
            $this->error("Error importing {$type}: ".$e->getMessage());

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

        // Write to a temporary ZIP file
        $tmpZip = tempnam(sys_get_temp_dir(), 'jpc-zip-');
        file_put_contents($tmpZip, $zipContents);

        // Extract
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

        // Find the CSV file inside
        $files = glob($extractDir.'/*.[cC][sS][vV]');

        if (empty($files)) {
            throw new \RuntimeException('No CSV file found in the downloaded archive.');
        }

        return $files[0];
    }

    protected function resolveLocalFile(string $path): string
    {
        if (! file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        // If it's already a CSV, return as‑is
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'csv') {
            return $path;
        }

        // Try to extract from ZIP
        $zip = new ZipArchive;
        $extractDir = sys_get_temp_dir().'/jpc-extract-'.uniqid();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException("Failed to open ZIP archive: {$path}");
        }

        @mkdir($extractDir, 0755, true);
        $zip->extractTo($extractDir);
        $zip->close();

        $files = glob($extractDir.'/*.[cC][sS][vV]');

        if (empty($files)) {
            throw new \RuntimeException('No CSV file found in the provided archive.');
        }

        return $files[0];
    }

    protected function normalizeTypes(): array
    {
        $types = (array) $this->option('type');

        // Filter out empty strings from the option array
        $types = array_filter($types);

        if (empty($types)) {
            return ['jp', 'romaji'];
        }

        $valid = ['jp', 'romaji'];

        return array_intersect($types, $valid);
    }
}
