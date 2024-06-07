<?php

namespace App\Commands;

use App\Models\Config;
use App\Models\Enums\FilePathScope;
use App\Models\Exceptions\ConfigFileException;
use App\Models\FilePath;
use Illuminate\Console\Concerns\PromptsForMissingInput;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaravelZero\Framework\Commands\Command;

abstract class CommandBase extends Command implements \Illuminate\Contracts\Console\PromptsForMissingInput
{
    use PromptsForMissingInput;

    /**
     * Configuration.
     */
    protected ?Config $config = null;

    /**
     * Execute the console command.
     */
    protected function readConfig(): ?int
    {
        $this->newLine()->info('Reading configuration...');

        // Read configuration file.
        try {
            $this->config = Config::readFromFile($this->argument('config_file'));
        } catch (ConfigFileException $e) {
            $this->error($e->getMessage());

            return $e->getCode();
        }

        $this->line('Configuration successfully read for plan: <options=bold>' . $this->config->name . '</options=bold>');

        return null;
    }

    /**
     * Prepare catalog connection.
     *
     * @return bool
     */
    protected function prepareCatalog(): void
    {
        $this->newLine()->info('Preparing catalog...');
        $catalogFile = FilePath::fromPath($this->config->catalog_file);

        $runMigration = false;

        if (!$catalogFile->exists(FilePathScope::EXTERNAL)) {
            touch($catalogFile->path());
            $runMigration = true;
        }

        // Set catalog connection
        config(['database.connections.default.database' => $catalogFile->absolutePath()]);
        $this->line('Catalog connection was set');

        if ($runMigration) {
            Schema::create('catalogs', function (Blueprint $table) {
                $table->id();
                $table->text('snapshot');
                $table->char('crc', 32);
                $table->unsignedBigInteger('size');
                $table->timestamps();
            });

            Schema::create('locks', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });

            $this->line('💾 Created catalog file at: ' . config('database.connections.default.database'));
        }

    }

    /**
     * Setup filesystems.
     */
    protected function setupFilesystems(): void
    {
        $filesystems = $this->config->filesystems ?? [];

        $filesystems['local'] = [
            'driver' => 'local',
            'root'   => dirname($this->config->snapshot_file),
        ];

        config(['filesystems.disks' => $filesystems]);
    }
}
