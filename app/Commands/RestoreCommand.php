<?php

namespace App\Commands;

use App\Commands\Concerns\NeedConfig;
use App\Commands\Concerns\NeedNotifications;
use App\Commands\Concerns\NeedTargetConnection;
use App\Helpers\DBStatus;
use App\Helpers\SQLFileReader;
use App\Models\Config;
use App\Models\FilePath;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command;
use function Laravel\Prompts\password;

class RestoreCommand extends CommandBase
{
    use NeedConfig, NeedNotifications, NeedTargetConnection;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'restore {backup_file}
        {--config_file=     : Use configuration file for the connection setup}
        {--database=        : Database name}       
        {--host=            : Host name (Can overwrite config_file settings)}
        {--port=            : Port number (Can overwrite config_file settings)}
        {--username=        : Database username (Can overwrite config_file settings)}
        {--password=        : Use defined password (Can overwrite config_file settings)}
        {--driver=mysql     : Database driver (Can overwrite config_file settings)}
        {--force            : Do to request confirmation before restoration
        {--dry              : Do not perform backup}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Perform backup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->title('Restore process');

        $backupFile = FilePath::fromPath($this->argument('backup_file'));

        if (!$backupFile->exists()) {
            $this->error('Backup file not found');
            return Command::FAILURE;
        }

        if ($this->option('config_file')) {
            if ($configErrorCode = $this->readConfig()) {
                return $configErrorCode;
            }

            unset($configErrorCode);
        }

        // Set connection configuration
        $this->config             = $this->config ?: new Config(['connection' => []]);
        $this->config->connection = array_merge(
            $this->config->connection,
            [
                'driver'   => $this->option('driver') ?: ($this->config->connection['driver'] ?? 'mysql'),
                'host'     => $this->option('host') ?: ($this->config->connection['host'] ?? '127.0.0.1'),
                'port'     => (int)$this->option('port') ?: ($this->config->connection['port'] ?? 3306),
                'username' => $this->option('username') ?: ($this->config->connection['username'] ?? 'root'),
                'password' => $this->option('password') ?: ($this->config->connection['password'] ?? null),
                'database' => $this->option('database') ?: ($this->config->connection['database'] ?? ''),
            ]
        );

        if (empty($this->config->connection['password']) && null === $this->option('password')) {
            $this->config->connection = array_merge(
                $this->config->connection,
                ['password' => password('DB Password? (Blank = no password)')]
            );
        }

        try {
            $exitCode = $this->executeTasks($backupFile);
        } catch (\Exception $e) {
            $this->error('Unable to restore backup: ' . $e->getMessage());
            $exitCode = Command::FAILURE;
        }

        return $exitCode;
    }

    protected function executeTasks(FilePath $file): int
    {
        // Open backup file
        $this->newLine()->info('Opening backup file...');
        $backupFile     = new SQLFileReader($file->absolutePath());
        $backupFileSize = $backupFile->getFileSize();
        $this->line("File path: {$file->path()}");
        $this->line(sprintf('File size: %d KB', $backupFileSize / 1024));

        // Configure database connection
        $this->newLine()->info('Configuring database connection...');
        $this->setTargetConnection();

        if (DBStatus::make()->isConnected()) {
            $this->line('Connected to database');
        } else {
            $this->error('Could not connect to database');
            return Command::FAILURE;
        }

        $this->newLine()->info('Restoring...');
        $progressBar = $this->output->createProgressBar($backupFileSize / 1024);

        foreach ($backupFile->readStatement() as $statement) {
            DB::connection('target')->statement($statement);
            $progressBar->setProgress($backupFile->getTotalRead() / 1024);
        }

        $progressBar->finish();

        $this->newLine();
        $this->output->success('👍 Restore process was successfully finished!');

        return Command::SUCCESS;
    }
}
