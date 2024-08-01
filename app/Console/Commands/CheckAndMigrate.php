<?php

namespace App\Console\Commands;


    use Illuminate\Console\Command;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Artisan;
    use Illuminate\Support\Facades\Log;

    class CheckAndMigrate extends Command
    {
        protected $signature = 'check:migrate';
        protected $description = 'Check if tables exist before running migrations';

        public function handle()
        {
            // Get all migration files in the database/migrations directory
            $migrationFiles = glob(database_path('migrations/*.php'));

            // Open log file
            $logFile = storage_path('logs/migration.log');
            $logHandle = fopen($logFile, 'a');
            fwrite($logHandle, "Migration started at " . now() . "\n");

            foreach ($migrationFiles as $file) {
                $contents = file_get_contents($file);

                // Extract table name from Schema::create statement
                if (preg_match('/Schema::create\(\'([^\']+)\'/', $contents, $matches)) {
                    $table = $matches[1];

                    // Check if the table already exists
                    if (Schema::hasTable($table)) {
                        $message = "Table {$table} already exists. Skipping migration.\n";
                        $this->info($message);
                        // fwrite($logHandle, $message);
                    } else {
                        $message = "Migrating {$file}\n";
                        $this->info($message);
                        fwrite($logHandle, $message);

                        // Run the specific migration
                        Artisan::call('migrate', [
                            '--path' => str_replace(base_path(), '', $file),
                        ]);

                        // Log the successful migration
                        $message = "Migrated {$file} successfully.\n";
                        fwrite($logHandle, $message);
                    }
                } else {
                    $message = "No create statement found in {$file}. Skipping.\n";
                    $this->warn($message);
                    fwrite($logHandle, $message);
                }
            }

            // Close log file
            fwrite($logHandle, "Migration finished at " . now() . "\n\n");
            fclose($logHandle);
        }
}
