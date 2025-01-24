<?php

namespace App\Commands\Concerns;

use App\Models\Enums\FilePathScope;
use App\Models\FilePath;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait NeedCatalog
{
    use NeedConfig;

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

        if (! $catalogFile->exists(FilePathScope::EXTERNAL)) {
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

            $this->line('💾 Created catalog file at: '.config('database.connections.default.database'));
        }

    }
}
