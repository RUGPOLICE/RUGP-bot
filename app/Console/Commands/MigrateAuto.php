<?php

namespace App\Console\Commands;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;
use Doctrine\DBAL\Schema\Comparator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class MigrateAuto extends Command
{
    protected $signature = 'migrate:auto {--f|--fresh} {--s|--seed} {--force}';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->warn('Use the <info>--force</info> to migrate in production.');

            return;
        }

        if ($this->option('fresh')) {
            Artisan::call('db:wipe', [], $this->getOutput());
        }

        $this->handleAutomaticMigrations();
        $this->handleTraditionalMigrations();
        $this->seed();

        $this->info('Automatic migration completed successfully.');
    }

    private function handleTraditionalMigrations(): void
    {
        $command = 'migrate';

        if ($this->option('force')) {
            $command .= ' --force';
        }

        Artisan::call($command, [], $this->getOutput());
    }

    /**
     * @throws Exception
     */
    private function handleAutomaticMigrations(): void
    {
        $path = app_path('Models');
        $namespace = app()->getNamespace();
        $models = collect();

        if (!is_dir($path)) {
            return;
        }

        foreach ((new Finder)->in($path) as $model) {
            $model = $namespace . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($model->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
                );

            if (method_exists($model, 'migration')) {
                $models->push([
                    'object' => $object = app($model),
                    'order' => $object->migrationOrder ?? 0,
                ]);
            }
        }

        foreach ($models->sortBy('order') as $model) {
            $this->migrate($model['object']);
        }
    }

    /**
     * @throws Exception
     */
    private function migrate($model): void
    {
        $modelTable = $model->getTable();
        $tempTable = 'table_' . $modelTable;
        $tableExists = Schema::hasTable($modelTable);

        $this->line($modelTable);

        Schema::dropIfExists($tempTable);
        Schema::create($tableExists ? $tempTable : $modelTable, function (Blueprint $table) use ($model) {
            $model->migration($table);
        });

        if ($tableExists) {

            $settings = config('database.connections')[$model->getConnection()->getName()];
            $connectionParams = [
                'dbname' => $settings['database'],
                'user' => $settings['username'],
                'password' => $settings['password'],
                'host' => $settings['host'],
                'driver' => 'pdo_' . $settings['driver'],
            ];

            $conn = DriverManager::getConnection($connectionParams);
            $schemaManager = $conn->createSchemaManager();

            $modelTableDetails = $schemaManager->introspectTable($modelTable);
            $tempTableDetails = $schemaManager->introspectTable($tempTable);

            foreach ($tempTableDetails->getIndexes() as $indexName => $indexInfo){

                if ($indexInfo->isPrimary())
                    continue;

                $correctIndexName = str_replace('table_', '', $indexName);
                $tempTableDetails->renameIndex($indexName, $correctIndexName);

            }

            $tableDiff = (new Comparator)->compareTables($modelTableDetails, $tempTableDetails);
            if (!$tableDiff->isEmpty()) {

                $schemaManager->alterTable($tableDiff);
                $this->line('<info>Table updated:</info> ' . $modelTable);

            }

            Schema::drop($tempTable);

        } else {

            // Schema::rename($tempTable, $modelTable);
            $this->line('<info>Table created:</info> ' . $modelTable);

        }
    }

    private function seed(): void
    {
        if (!$this->option('seed')) {
            return;
        }

        $command = 'db:seed';

        if ($this->option('force')) {
            $command .= ' --force';
        }

        Artisan::call($command, [], $this->getOutput());
    }
}
