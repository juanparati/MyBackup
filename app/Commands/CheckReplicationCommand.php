<?php

namespace App\Commands;

use App\Helpers\DBStatus;
use Illuminate\Console\Concerns\PromptsForMissingInput;
use LaravelZero\Framework\Commands\Command;

class CheckReplicationCommand extends BackupCommand implements \Illuminate\Contracts\Console\PromptsForMissingInput
{
    use PromptsForMissingInput;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'check_replication {config_file : configuration file}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Verify is replication is working and active';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->title('Check replication');

        if ($configErrorCode = $this->readConfig()) {
            return $configErrorCode;
        }

        unset($configErrorCode);

        $this->setTargetConnection();

        $this->newLine()->info('Checking replication status...');

        if (!DBStatus::make()->hasActiveReplication()) {
            $this->output->error('💥 Replication is NOT working!');
            return Command::FAILURE;
        }

        $this->output->success('👍 Replication on progress!');
        return Command::SUCCESS;

    }
}
