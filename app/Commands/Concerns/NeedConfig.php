<?php

namespace App\Commands\Concerns;

use App\Models\Config;
use App\Models\Exceptions\ConfigFileException;

trait NeedConfig
{
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
            $configFile = $this->hasOption('config_file') ?
                $this->option('config_file') : $this->argument('config_file');

            $this->config = Config::readFromFile($configFile);
        } catch (ConfigFileException $e) {
            $this->error($e->getMessage());

            return $e->getCode();
        }

        $this->line('Configuration successfully read for plan: <options=bold>'.$this->config->name.'</options=bold>');

        return null;
    }
}
