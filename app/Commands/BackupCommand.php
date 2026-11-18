<?php

namespace App\Commands;

use App\Commands\Concerns\NeedActions;
use App\Commands\Concerns\NeedCatalog;
use App\Commands\Concerns\NeedConfig;
use App\Commands\Concerns\NeedFilesystem;
use App\Commands\Concerns\NeedNotifications;
use App\Commands\Concerns\NeedSearchPath;
use App\Commands\Concerns\NeedTargetConnection;
use App\Helpers\DBStatus;
use App\Helpers\DeclarativeHumanDate;
use App\Models\Catalog;
use App\Models\Enums\FilePathScope;
use App\Models\FilePath;
use App\Models\Lock;
use App\Models\MySQLDump;
use App\Models\Placeholder;
use App\Notifications\BackupFailedNotification;
use App\Notifications\BackupFinishedNotification;
use Symfony\Component\Console\Command\Command;

use function Laravel\Prompts\password;

class BackupCommand extends CommandBase
{
    use NeedActions,
        NeedCatalog,
        NeedConfig,
        NeedFilesystem,
        NeedNotifications,
        NeedTargetConnection,
        NeedSearchPath;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'backup {config_file : Configuration file}
        {--dry : Do not perform backup}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Perform backup';

    /**
     * Reason of the backup failure.
     */
    protected ?string $failureReason = null;

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
                if (Lock::isStale()) {
                    $this->warn('Removing stale lock, the process that created it is no longer running');
                    Lock::unlock();
                } else {
                    $this->failure('Backup process is locked');
                    $this->line('If the backup failed run '.EXECUTABLE.' unlock '.$this->argument('config_file'));
                    $this->notifyFailure();

                    return Command::FAILURE;
                }
            }

            Lock::lock();
            $this->line('🔒 Lock created');
        }

        try {
            $exitCode = $this->executeTasks();
        } catch (\Exception $e) {
            $exitCode = $this->failure('Unable to perform backup: '.$e->getMessage());
        }

        if (! $this->option('dry')) {
            Lock::unlock();
            $this->info('Lock destroyed');

            if ($exitCode !== Command::SUCCESS) {
                $this->notifyFailure();
            }
        }

        return $exitCode;

    }

    /**
     * Report a backup failure.
     */
    protected function failure(string $reason): int
    {
        $this->error($reason);
        $this->failureReason = $reason;

        return Command::FAILURE;
    }

    /**
     * Send the failure notification.
     */
    protected function notifyFailure(): void
    {
        $this->sendNotification(BackupFailedNotification::class, [
            'name' => $this->config->name ?? $this->argument('config_file'),
            'reason' => $this->failureReason ?? 'Unknown error, check the backup output',
        ]);
    }

    protected function executeTasks(): int
    {
        // Check mysqldump path
        $mysqlDumpPath = $this->searchPath(
            $this->config->mysqldump_path ?? 'mysqldump',
            'mysqldump/mariadb-dump'
        );

        if (!$mysqlDumpPath) {
            $this->failureReason = 'Unable to find mysqldump/mariadb-dump';

            return Command::FAILURE;
        }

        // Configure database connection
        $this->newLine()->info('Configuring database connection...');
        $this->setTargetConnection();

        if (DBStatus::make()->isConnected()) {
            $this->line('Connected to database');
        } else {
            return $this->failure('Could not connect to database');
        }

        // Check replication status
        if ($this->config->is_replica) {
            $this->newLine()->info('Checking replication status...');
            if (DBStatus::make()->hasActiveReplication()) {
                $this->line('Replication already running');
            } else {
                return $this->failure('Replication is not working');
            }
        }

        $this->newLine()->info('Creating backup plan...');
        $snapshotFile = FilePath::fromPath((new Placeholder)->replace($this->config->snapshot_file));
        $encryption = $this->config->encryptionSettings();

        if ($this->config->compress) {
            $snapshotFile->addExtension('gz');
        }

        if ($encryption) {
            $snapshotFile->addExtension('aes');
        }

        // The snapshot is written into a partial file that is renamed once the backup is completed
        $dumpFile = FilePath::fromPath($snapshotFile->path().'.partial');

        $this->line('Snapshot file: '.$snapshotFile->path());

        if ($snapshotFile->exists(FilePathScope::EXTERNAL)) {
            return $this->failure('Snapshot already exists!');
        }

        $dump = (new MySQLDump(
            $dumpFile,
            $this->config->mysqldump_path,
            (bool) $this->config->compress
        ))
            ->setHost($this->config->connection['host'])
            ->setPort($this->config->connection['port'])
            ->setUser($this->config->connection['username'] ?? null)
            ->setPassword($this->config->connection['password'] ?? null)
            ->setBeginText($this->config->dump['begin'] ?? [])
            ->setEndText($this->config->dump['end'] ?? []);

        if ($this->config->compress && is_int($this->config->compress)) {
            $dump->setCompressionLevel($this->config->compress);
        }

        if ($encryption) {
            $dump->setEncryption($encryption['key'], $encryption['method']);
        }

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
                    $planList[count($planList) - 1]['ignore'] = implode(', ', $db['ignore']);
                }

                continue;
            }

            $views = DBStatus::make()->views($db['database']);
            $dbViews = [];

            foreach ($db['tables'] as $table) {
                if (in_array($table['table'], $views, true)) {
                    $dbViews[] = $table['table'];

                    continue;
                }

                $dump->addTable($db['database'], $table['table'], $table['where'], $table['only_struct']);
                $planList[] = [
                    'num' => count($planList) + 1,
                    'database' => $db['database'],
                    'table' => $table['table'].($table['only_struct'] ? ' (structure only)' : ''),
                ];
            }

            // Views are dumped together after the tables, so mysqldump resolves the dependencies between them
            foreach ($dbViews as $view) {
                $dump->addView($db['database'], $view);
                $planList[] = [
                    'num' => count($planList) + 1,
                    'database' => $db['database'],
                    'table' => $view.' (view)',
                ];
            }
        }

        $this->table(['Num', 'Database', 'Table', 'Ignore'], $planList);

        if (! $this->option('dry')) {
            try {
                $this->newLine()->info('Dumping snapshot, please be patience...');
                $dump->initialize()->process($this->output);

                if (! rename($dumpFile->path(), $snapshotFile->path())) {
                    return $this->failure('Unable to write snapshot file '.$snapshotFile->path());
                }

                $this->newLine()->line(
                    $encryption ? 'Snapshot saved and encrypted' : 'Snapshot saved'
                );
            } finally {
                // Incomplete snapshots are never kept
                if ($dumpFile->exists()) {
                    $dumpFile->rm();
                }
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

            $this->newLine()->info('Base backup file is available at: '.$snapshotFile->absolutePath());

            // Execute post actions
            if ($this->config->post_actions) {
                $this->runActions([
                    'snapshot_file' => $snapshotInfo['snapshot'],
                    'crc' => $snapshotInfo['crc'],
                ]);
            }

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

            // Send notification
            $this->sendNotification(BackupFinishedNotification::class, $snapshotInfo);
        }

        $this->output->success('👍 Backup process was successfully finished!');

        return Command::SUCCESS;

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
                    'only_struct' => false,
                ];

                $currentTable = &$currentList['tables'][$kTable];

                if (is_string($table)) {
                    $currentTable['table'] = $table;

                    continue;
                }

                $currentTable['table'] = array_key_first($table);
                $currentTable['where'] = $table[$currentTable['table']]['where'] ?? null;
                $currentTable['only_struct'] = (bool) ($table[$currentTable['table']]['only_struct'] ?? false);
            }
        }

        return $dbList;
    }
}
