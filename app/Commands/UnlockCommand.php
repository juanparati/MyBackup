<?php

namespace App\Commands;

use App\Commands\Concerns\NeedCatalog;
use App\Commands\Concerns\NeedConfig;
use App\Models\Lock;
use LaravelZero\Framework\Commands\Command;

class UnlockCommand extends CommandBase
{
    use NeedCatalog, NeedConfig;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'unlock {config_file : configuration file}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Unlock backup process';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->title('Unlock');

        if ($configErrorCode = $this->readConfig()) {
            return $configErrorCode;
        }

        unset($configErrorCode);

        // Prepare catalog
        $this->prepareCatalog();

        Lock::unlock();

        $this->output->success('🔓 Lock removed!');

        return Command::SUCCESS;
    }
}
