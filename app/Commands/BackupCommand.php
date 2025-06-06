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
use App\Helpers\FileEncrypt;
use App\Models\Catalog;
use App\Models\Enums\FilePathScope;
use App\Models\FilePath;
use App\Models\Lock;
use App\Models\MySQLDump;
use App\Models\Placeholder;
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
        $mysqlDumpPath = $this->searchPath(
            $this->config->mysqldump_path ?? 'mysqldump',
            'mysqldump/mariadb-dump'
        );

        if (!$mysqlDumpPath) {
            return Command::FAILURE;
        }

        $gzipPath = null;

        if ($this->config->compress) {
            $gzipPath = $this->searchPath($this->config->gzip_path ?? 'gzip');

            if (!$gzipPath) {
                return Command::FAILURE;
            }
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
        $snapshotFile = FilePath::fromPath((new Placeholder)->replace($this->config->snapshot_file));
        $this->line('Snapshot file: '.$snapshotFile->path());

        if ($snapshotFile->exists(FilePathScope::EXTERNAL)) {
            $this->error('Snapshot already exists!');

            return Command::FAILURE;
        }

        $dump = (new MySQLDump(
            $snapshotFile,
            $this->config->mysqldump_path,
            $gzipPath
        ))
            ->setHost($this->config->connection['host'])
            ->setPort($this->config->connection['port'])
            ->setUser($this->config->connection['username'] ?? null)
            ->setPassword($this->config->connection['password'] ?? null);

        if ($this->config->compress && is_int($this->config->compress)) {
            $dump->setCompressionLevel($this->config->compress);
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

            foreach ($db['tables'] as $table) {
                $dump->addTable($db['database'], $table['table'], $table['where']);
                $planList[] = [
                    'num' => count($planList) + 1,
                    'database' => $db['database'],
                    'table' => $table['table'],
                ];
            }
        }

        $this->table(['Num', 'Database', 'Table', 'Ignore'], $planList);

        if ($this->config->compress) {
            $snapshotFile->addExtension('gz');
        }

        if (! $this->option('dry')) {
            $this->newLine()->info('Dumping snapshot, please be patience...');
            $dump->process($this->output);
            $this->newLine()->line('Snapshot saved');

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
