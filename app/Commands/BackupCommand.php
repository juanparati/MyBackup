<?php

namespace App\Commands;

use App\Helpers\DBStatus;
use App\Helpers\DeclarativeHumanDate;
use App\Helpers\FileEncrypt;
use App\Helpers\GzipCompressor;
use App\Helpers\NotificationSender;
use App\Models\Catalog;
use App\Models\Enums\FilePathScope;
use App\Models\FilePath;
use App\Models\Lock;
use App\Models\MySQLDump;
use App\Models\Placeholder;
use App\Notifications\BackupFinishedNotification;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Command\Command;

use function Laravel\Prompts\password;

class BackupCommand extends CommandBase
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'backup {config_file : configuration file}
        {--dry : Do not perform backup}
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
    public function handle()
    {
        $this->title('Backup process');

        if ($configErrorCode = $this->readConfig()) {
            return $configErrorCode;
        }

        unset($configErrorCode);

        if (! isset($this->config->connection['password'])) {
            $this->config->connection = array_merge(
                $this->config->connection,
                ['password' => password('DB Password? (Blank = no password)')]
            );
        }

        $this->prepareCatalog();
        $this->setupFilesystems();

        if (! $this->option('dry')) {
            $this->newLine()->info('Checking lock...');

            if (Lock::hasLock()) {
                $this->error('Backup process is locked');
                $this->line('If the backup failed run '.EXECUTABLE.' unlock '.$this->argument('config_file'));

                return 0;
            }

            Lock::lock();
            $this->line('🔒 Lock created');
        }

        try {
            $exitCode = $this->executeTasks();
        } catch (\Exception $e) {
            $this->error('Unable to perform backup: '.$e->getMessage());
            $exitCode = Command::FAILURE;
        }

        if (! $this->option('dry')) {
            Lock::unlock();
            $this->info('Lock destroyed');
        }

        return $exitCode;

    }

    protected function executeTasks(): int
    {

        // Check mysqldump path
        $this->newLine()->info('Checking mysqldump/mariadb-dump executable...');

        $mysqlDumpPath = FilePath::fromPath($this->config->mysqldump_path ?? 'mysqldump');

        if ($mysqlDumpPath->exists()) {
            $this->line('Executable found!');
        } else {
            $this->line('Unable to locate MySQL dump... trying to locate automatically');
            $mysqlDumpPathSearch = Process::run(['which', $mysqlDumpPath->basename()]);

            if (! $mysqlDumpPathSearch->successful()) {
                $this->error('Unable to find '.$mysqlDumpPath);

                return Command::FAILURE;
            }

            $mysqlDumpPath = FilePath::fromPath($mysqlDumpPathSearch->output());
            unset($mysqlDumpPathSearch);

            $this->line("Located at $mysqlDumpPath");
        }

        // Configure database connection
        $this->newLine()->info('Configuring database connection...');
        $this->setTargetConnection();

        if (DBStatus::make()->isConnected()) {
            $this->line('Connected to database');
        } else {
            $this->error('Could not connect to database');
            Lock::unlock();

            return Command::FAILURE;
        }

        // Check replication status
        if ($this->config->is_replica) {
            $this->newLine()->info('Checking replication status...');
            if (DBStatus::make()->hasActiveReplication()) {
                $this->line('Replication already running');
            } else {
                $this->error('Replication is not working');
                Lock::unlock();

                return Command::FAILURE;
            }
        }

        $this->newLine()->info('Creating backup plan...');
        $snapshotFile = FilePath::fromPath((new Placeholder())->replace($this->config->snapshot_file));
        $this->line('Snapshot file: '.$snapshotFile->path());

        if ($snapshotFile->exists(FilePathScope::EXTERNAL)) {
            $this->error('Snapshot already exists!');

            return Command::FAILURE;
        }

        $dump = (new MySQLDump(
            $snapshotFile,
            $this->config->mysqldump_path,
        ))
            ->setHost($this->config->connection['host'])
            ->setPort($this->config->connection['port'])
            ->setUser($this->config->connection['username'] ?? null)
            ->setPassword($this->config->connection['password'] ?? null);

        $dbList = $this->retrieveDbTableList();
        $planList = [];

        foreach ($dbList as $db) {

            $dump->setDatabaseOptions($db['database'], $db['options'] ?? []);

            if ($db['to']) {
                $dump->addLocation($db['database'], $db['to']);
            }

            if (empty($db['tables'])) {
                $dump->addDatabase($db['database']);

                $planList[] = [
                    'num' => count($planList) + 1,
                    'database' => $db['database'],
                    'table' => '*',
                ];

                if ($db['ignore']) {
                    $dump->addIgnoreTables($db['database'], $db['ignore']);
                    //$this->line("- The following tables from {$db['database']} database are ignored: ".implode(', ', $db['ignore']));
                    $planList[count($planList) - 1]['ignore'] = implode(', ', $db['ignore']);
                }

                continue;
            }

            foreach ($db['tables'] as $table) {
                $dump->addTable($db['database'], $table['table'], $table['where']);
                //$this->line("- The table {$db['database']}.{$table['table']} is going to be added");
                $planList[] = [
                    'num' => count($planList) + 1,
                    'database' => $db['database'],
                    'table' => $table['table'],
                ];
            }
        }

        $this->table(['Num', 'Database', 'Table', 'Ignore'], $planList);

        if (! $this->option('dry')) {
            $this->newLine()->info('Dumping snapshot, please be patience...');
            $dump->initialize()->process($this->output);
            $this->newLine()->line('Snapshot saved');

            // Compress snapshot if proceeds
            if ($this->config->compress) {
                $compressedSnapshot = FilePath::fromPath($snapshotFile->absolutePath().'.gz');
                $this->newLine()->info('Compressing snapshot...');
                (new GzipCompressor($snapshotFile->absolutePath(), $compressedSnapshot->path()))
                    ->gzip($this->config->compression_level ?: 6, $this->output);
                $snapshotFile->rm();
                $snapshotFile = $compressedSnapshot;
                unset($compressedSnapshot);
                $this->newLine()->line('Snapshot was compressed as: '.$snapshotFile->absolutePath());
            }

            // Encrypt snapshot if proceeds
            if ($this->config->encryption['key'] ?? null) {
                $this->newLine()->info('Encrypting snapshot...');
                $encryptedSnapshot = FilePath::fromPath($snapshotFile->absolutePath().'.aes');
                $encrypted = FileEncrypt::encrypt(
                    $snapshotFile->absolutePath(),
                    $encryptedSnapshot->path(),
                    $this->config->encryption['key'],
                    $this->config->encryption['method'] ?? 'AES-128-CBC',
                    $this->output
                );

                if (! $encrypted) {
                    $this->error('Unable to encrypt snapshot file');

                    return Command::FAILURE;
                }

                $snapshotFile->rm();
                $snapshotFile = $encryptedSnapshot;
                unset($encryptedSnapshot);
                $this->newLine()->line('Snapshot was encrypted as: '.$snapshotFile->absolutePath());

            }

            // Save last snapshot into the catalog
            $this->newLine()->info('Registering snapshot into the catalog...');

            $snapshotInfo = [
                'snapshot' => $snapshotFile->absolutePath(),
                'crc' => $snapshotFile->md5(),
                'size' => $snapshotFile->size(),
            ];

            $lastCreatedCatalog = Catalog::create($snapshotInfo);
            $this->line('Registered snapshot CRC: '.$snapshotInfo['crc']);

            // Rotate backups
            if ($this->config->backup_rotation !== null) {
                $this->newLine()->info('Rotating backups...');
                if (is_int($this->config->backup_rotation) || ctype_digit($this->config->backup_rotation)) {
                    $catalogItems = Catalog::query()
                        ->where('id', '<=', ($lastCreatedCatalog->id - $this->config->backup_rotation))
                        ->orderBy('id')
                        ->get();
                } else {
                    $catalogItems = Catalog::query()
                        ->where('created_at', '<=', DeclarativeHumanDate::relative($this->config->backup_rotation))
                        ->orderByDesc('created_at')
                        ->get();
                }

                foreach ($catalogItems as $catalogItem) {
                    @unlink($catalogItem->snapshot);
                    $catalogItem->delete();
                    $this->line('- Removed: '.$catalogItem->snapshot);
                }

                $this->line('Rotated backup elements');
            }

            // Execute post actions
            if ($this->config->post_actions) {
                $this->runActions([
                    'snapshot_file' => $snapshotInfo['snapshot'],
                    'crc' => $snapshotInfo['crc'],
                ]);
            }

            // Send notification
            (new NotificationSender($this->config->notifications ?? []))
                ->send(BackupFinishedNotification::class, $snapshotInfo);
        }

        $this->output->success('👍 Backup process was successfully finished!');

        return Command::SUCCESS;

    }

    protected function runActions(array $dictionary): void
    {

        $this->newLine()->info('Running post actions');

        $placeholder = new Placeholder($dictionary);

        foreach ($this->config->post_actions as $k => $action) {
            $actionName = array_key_first($action);
            $instructions = $action[$actionName];
            $actionClass = 'App\\Actions\\'.str($actionName)->camel()->ucfirst().'Action';

            if (! class_exists($actionClass)) {
                $this->warn("Action [$k] $actionName is not available");

                continue;
            }

            $this->task(
                "- Running action [$k] $actionName",
                fn() => (new $actionClass($this->config, $placeholder, $instructions))()
            );
        }
    }

    protected function setTargetConnection(): void
    {
        config([
            'database.connections.target' => array_merge(
                config('database.connections.target'),
                $this->config->connection
            ),
        ]);
    }

    /**
     * Retrieve the list of databases and tables to back up.
     */
    protected function retrieveDbTableList(): array
    {
        $dbList = [];

        foreach ($this->config->databases as $k => $db) {
            $dbList[$k] = [
                'options' => [],
                'tables' => [],
                'ignore' => [],
                'to' => null,
            ];

            $currentList = &$dbList[$k];

            if (is_string($db)) {
                $currentList['database'] = $db;

                continue;
            }

            $currentList['database'] = array_key_first($db);
            $currentList['options'] = $db[$currentList['database']]['options'] ?? [];
            $currentList['ignore'] = $db[$currentList['database']]['ignore'] ?? [];
            $currentList['to'] = $db[$currentList['database']]['to'] ?? null;

            foreach (($db[$currentList['database']]['tables'] ?? []) as $kTable => $table) {
                $currentList['tables'][$kTable] = [
                    'where' => null,
                ];

                $currentTable = &$currentList['tables'][$kTable];

                if (is_string($table)) {
                    $currentTable['table'] = $table;

                    continue;
                }

                $currentTable['table'] = array_key_first($table);
                $currentTable['where'] = $table[$currentTable['table']]['where'] ?? null;
            }
        }

        return $dbList;
    }
}
