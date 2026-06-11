<?php

namespace Scarneros\JapanPostalCodes\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Scarneros\JapanPostalCodes\Services\CsvImporter;

class UpdatePostalCodesCommand extends Command
{
    protected $signature = 'japan-postal-codes:update
                            {--type=* : Data types to update: jp, romaji (default: both)}
                            {--chunk= : Override the chunk size from config}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Update Japanese postal code data with the latest CSV files';

    public function handle(ImportPostalCodesCommand $importCommand): int
    {
        $table = config('japan-postal-codes.table_name', 'japan_postal_codes');

        $count = DB::table($table)->count();

        if ($count === 0) {
            $this->warn('No existing postal code data found.');
            $this->info('Please run `php artisan japan-postal-codes:import` to perform the initial import.');

            return self::FAILURE;
        }

        $this->info("Found {$count} existing postal code records.");
        $this->line('Starting update — only missing fields will be backfilled.');
        $this->line('Existing data will NOT be overwritten.');
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('Proceed with the update?', true)) {
                $this->info('Update cancelled.');

                return self::SUCCESS;
            }
        }

        // Delegate to the import command which handles the actual CSV processing.
        // The CsvImporter's merge logic ensures data is only backfilled.
        return $importCommand->handle(app(CsvImporter::class));
    }
}
