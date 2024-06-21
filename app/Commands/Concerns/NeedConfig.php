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
            $this->config = Config::readFromFile($this->argument('config_file'));
        } catch (ConfigFileException $e) {
            $this->error($e->getMessage());

            return $e->getCode();
        }

        $this->line('Configuration successfully read for plan: <options=bold>'.$this->config->name.'</options=bold>');

        return null;
    }

}
