<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class MigrateToMySQL extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:migrate-to-mysql 
                            {--host=127.0.0.1 : MySQL host}
                            {--port=3306 : MySQL port}
                            {--database= : MySQL database name}
                            {--username=root : MySQL username}
                            {--password= : MySQL password}
                            {--skip-migrations : Skip running migrations}
                            {--skip-seeders : Skip running seeders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all data from SQLite to MySQL database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration from SQLite to MySQL...');

        // Get MySQL connection details
        $host = $this->option('host');
        $port = $this->option('port');
        $database = $this->option('database');
        $username = $this->option('username');
        $password = $this->option('password');

        if (!$database) {
            $database = $this->ask('Please enter MySQL database name');
        }

        if (!$password) {
            $password = $this->secret('Please enter MySQL password (leave empty if no password)');
        }

        // Test MySQL connection
        $this->info('Testing MySQL connection...');
        try {
            $mysqlConnection = $this->createMySQLConnection($host, $port, $database, $username, $password);
            $this->info('✓ MySQL connection successful');
        } catch (Exception $e) {
            $this->error('✗ MySQL connection failed: ' . $e->getMessage());
            $this->error('Please ensure MySQL is running and credentials are correct.');
            return 1;
        }

        // Check SQLite connection BEFORE updating .env
        $this->info('Checking SQLite database...');
        $sqlitePath = database_path('database.sqlite');
        if (!file_exists($sqlitePath)) {
            $this->error('✗ SQLite database not found at: ' . $sqlitePath);
            return 1;
        }
        
        try {
            // Explicitly set SQLite database path
            config(['database.connections.sqlite.database' => $sqlitePath]);
            DB::connection('sqlite')->getPdo();
            $this->info('✓ SQLite connection successful');
        } catch (Exception $e) {
            $this->error('✗ SQLite connection failed: ' . $e->getMessage());
            return 1;
        }

        // Create database if it doesn't exist
        $this->info('Ensuring MySQL database exists...');
        try {
            $tempConnection = $this->createMySQLConnection($host, $port, null, $username, $password);
            $tempConnection->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info('✓ Database ready');
        } catch (Exception $e) {
            $this->error('✗ Failed to create database: ' . $e->getMessage());
            return 1;
        }

        // Update .env file
        $this->info('Updating .env file...');
        $this->updateEnvFile($host, $port, $database, $username, $password);
        $this->info('✓ .env file updated');

        // Run migrations if not skipped
        if (!$this->option('skip-migrations')) {
            $this->info('Running migrations on MySQL...');
            $this->call('migrate', [
                '--database' => 'mysql',
                '--force' => true,
            ]);
            $this->info('✓ Migrations completed');
        } else {
            $this->warn('Skipping migrations (--skip-migrations flag set)');
        }

        // Migrate data
        $this->info('Migrating data from SQLite to MySQL...');
        $this->migrateData();
        $this->info('✓ Data migration completed');

        // Run seeders if not skipped
        if (!$this->option('skip-seeders')) {
            $this->info('Running seeders...');
            $this->call('db:seed', [
                '--database' => 'mysql',
                '--force' => true,
            ]);
            $this->info('✓ Seeders completed');
        } else {
            $this->warn('Skipping seeders (--skip-seeders flag set)');
        }

        // Verify data
        $this->info('Verifying data migration...');
        $this->verifyData();

        $this->info('');
        $this->info('✓ Migration completed successfully!');
        $this->info('Your application is now using MySQL database.');
        
        return 0;
    }

    /**
     * Create a temporary MySQL connection
     */
    private function createMySQLConnection($host, $port, $database, $username, $password)
    {
        $dsn = "mysql:host={$host};port={$port}";
        if ($database) {
            $dsn .= ";dbname={$database}";
        }
        $dsn .= ";charset=utf8mb4";

        return new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * Update .env file with MySQL configuration
     */
    private function updateEnvFile($host, $port, $database, $username, $password)
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            $this->error('.env file not found');
            return;
        }

        $envContent = file_get_contents($envPath);
        $sqlitePath = database_path('database.sqlite');
        
        // Update database configuration
        $envContent = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=mysql', $envContent);
        $envContent = preg_replace('/^DB_HOST=.*/m', "DB_HOST={$host}", $envContent);
        $envContent = preg_replace('/^DB_PORT=.*/m', "DB_PORT={$port}", $envContent);
        $envContent = preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE={$database}", $envContent);
        $envContent = preg_replace('/^DB_USERNAME=.*/m', "DB_USERNAME={$username}", $envContent);
        $envContent = preg_replace('/^DB_PASSWORD=.*/m', "DB_PASSWORD={$password}", $envContent);

        // Add SQLITE_DATABASE to preserve SQLite path
        if (!preg_match('/^SQLITE_DATABASE=/m', $envContent)) {
            $envContent .= "\nSQLITE_DATABASE={$sqlitePath}";
        } else {
            $envContent = preg_replace('/^SQLITE_DATABASE=.*/m', "SQLITE_DATABASE={$sqlitePath}", $envContent);
        }

        // If any of these don't exist, add them
        if (!preg_match('/^DB_CONNECTION=/m', $envContent)) {
            $envContent .= "\nDB_CONNECTION=mysql";
        }
        if (!preg_match('/^DB_HOST=/m', $envContent)) {
            $envContent .= "\nDB_HOST={$host}";
        }
        if (!preg_match('/^DB_PORT=/m', $envContent)) {
            $envContent .= "\nDB_PORT={$port}";
        }
        if (!preg_match('/^DB_DATABASE=/m', $envContent)) {
            $envContent .= "\nDB_DATABASE={$database}";
        }
        if (!preg_match('/^DB_USERNAME=/m', $envContent)) {
            $envContent .= "\nDB_USERNAME={$username}";
        }
        if (!preg_match('/^DB_PASSWORD=/m', $envContent)) {
            $envContent .= "\nDB_PASSWORD={$password}";
        }

        file_put_contents($envPath, $envContent);
        
        // Re-set SQLite path in config after .env update
        config(['database.connections.sqlite.database' => $sqlitePath]);
        DB::purge('sqlite');
    }

    /**
     * Migrate all data from SQLite to MySQL
     */
    private function migrateData()
    {
        // Ensure SQLite connection uses correct path
        $sqlitePath = database_path('database.sqlite');
        config(['database.connections.sqlite.database' => $sqlitePath]);
        DB::purge('sqlite');
        
        // Get all tables from SQLite
        $sqliteTables = DB::connection('sqlite')
            ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        $tables = array_map(function ($table) {
            return $table->name;
        }, $sqliteTables);

        // Exclude migrations table - we'll handle it separately
        $tables = array_filter($tables, function ($table) {
            return $table !== 'migrations';
        });

        $progressBar = $this->output->createProgressBar(count($tables));
        $progressBar->start();

        foreach ($tables as $table) {
            try {
                // Check if table exists in MySQL
                if (!Schema::connection('mysql')->hasTable($table)) {
                    $this->newLine();
                    $this->warn("Table {$table} does not exist in MySQL. Skipping...");
                    $progressBar->advance();
                    continue;
                }

                // Get all data from SQLite
                $data = DB::connection('sqlite')->table($table)->get();

                if ($data->isEmpty()) {
                    $progressBar->advance();
                    continue;
                }

                // Disable foreign key checks temporarily
                DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0');

                // Clear existing data (optional - comment out if you want to append)
                // DB::connection('mysql')->table($table)->truncate();

                // Insert data in chunks using Laravel's insert method
                $chunks = $data->chunk(100);
                foreach ($chunks as $chunk) {
                    $insertData = [];
                    foreach ($chunk as $row) {
                        $rowArray = (array) $row;
                        // Convert SQLite row to array format
                        $insertRow = [];
                        foreach ($rowArray as $key => $value) {
                            // Handle JSON fields
                            if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
                                $decoded = json_decode($value, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $insertRow[$key] = $decoded;
                                } else {
                                    $insertRow[$key] = $value;
                                }
                            } else {
                                $insertRow[$key] = $value;
                            }
                        }
                        $insertData[] = $insertRow;
                    }

                    if (!empty($insertData)) {
                        try {
                            // Use insert with ignore for duplicate keys
                            DB::connection('mysql')->table($table)->insertOrIgnore($insertData);
                        } catch (Exception $e) {
                            // If insertOrIgnore fails, try individual inserts with update on duplicate
                            foreach ($insertData as $rowData) {
                                try {
                                    DB::connection('mysql')->table($table)->upsert(
                                        [$rowData],
                                        ['id'], // unique columns
                                        array_keys($rowData) // update columns
                                    );
                                } catch (Exception $e2) {
                                    // Last resort: try regular insert
                                    try {
                                        DB::connection('mysql')->table($table)->insert($rowData);
                                    } catch (Exception $e3) {
                                        $this->newLine();
                                        $this->warn("Error inserting row into {$table}: " . $e3->getMessage());
                                    }
                                }
                            }
                        }
                    }
                }

                // Re-enable foreign key checks
                DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1');

            } catch (Exception $e) {
                $this->newLine();
                $this->error("Error migrating table {$table}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Migrate migrations table
        $this->info('Migrating migrations table...');
        try {
            $migrations = DB::connection('sqlite')->table('migrations')->get();
            foreach ($migrations as $migration) {
                DB::connection('mysql')->table('migrations')->updateOrInsert(
                    ['migration' => $migration->migration, 'batch' => $migration->batch],
                    (array) $migration
                );
            }
            $this->info('✓ Migrations table migrated');
        } catch (Exception $e) {
            $this->warn("Could not migrate migrations table: " . $e->getMessage());
        }
    }

    /**
     * Verify data migration
     */
    private function verifyData()
    {
        // Ensure SQLite connection uses correct path
        $sqlitePath = database_path('database.sqlite');
        config(['database.connections.sqlite.database' => $sqlitePath]);
        DB::purge('sqlite');
        
        $tables = [
            'users', 'students', 'teachers', 'lessons', 
            'timetables', 'timetable_events', 'billings', 
            'billing_items', 'families', 'teacher_salaries',
            'student_packages', 'timezone_adjustments', 'support_attendances'
        ];

        $this->table(
            ['Table', 'SQLite Count', 'MySQL Count', 'Status'],
            array_map(function ($table) {
                try {
                    $sqliteCount = DB::connection('sqlite')->table($table)->count();
                    $mysqlCount = DB::connection('mysql')->table($table)->count();
                    $status = $sqliteCount === $mysqlCount ? '✓ OK' : '⚠ Mismatch';
                    
                    return [$table, $sqliteCount, $mysqlCount, $status];
                } catch (Exception $e) {
                    return [$table, 'N/A', 'N/A', '✗ Error'];
                }
            }, $tables)
        );
    }
}

