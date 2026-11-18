<?php

namespace App\Models;

use App\Helpers\EncryptedWriter;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Arr;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MySQLDump
{
    /**
     * Environment variable used for defining mysql/mariadb password.
     */
    protected const PASSWORD_ENV_VARIABLE = 'MYSQL_PWD';

    /**
     * Snapshot file.
     */
    protected FilePath $snapshotFile;

    /**
     * Mysqldump path
     */
    protected FilePath $mysqldumpPath;

    /**
     * Compress snapshot with GZIP
     */
    protected bool $compress = false;

    /**
     * GZIP compression level
     *
     * @var int
     */
    protected int $compressionLevel = 6;

    /**
     * Encryption key
     */
    protected ?string $encryptionKey = null;

    /**
     * Encryption method
     */
    protected string $encryptionMethod = 'AES-128-CBC';

    /**
     * Snapshot file handle while the snapshot is processed.
     *
     * @var resource|null
     */
    protected $snapshotHandle = null;

    /**
     * Snapshot encryption while the snapshot is processed.
     */
    protected ?EncryptedWriter $encryptedWriter = null;

    /**
     * Dump options
     */
    protected array $options = [];

    /**
     * List of databases to dump
     */
    protected array $databases = [];

    /**
     * Database options.
     */
    protected array $databaseOptions = [];

    /**
     * Tables to ignore.
     */
    protected array $ignoreTables = [];

    /**
     * Tables.
     */
    protected array $tables = [];

    /**
     * Views grouped by database.
     */
    protected array $views = [];

    /**
     * Dump locations.
     */
    protected array $locations = [];

    /**
     * Text lines added at the beginning of the snapshot.
     */
    protected array $beginText = [];

    /**
     * Text lines added at the end of the snapshot.
     */
    protected array $endText = [];

    /**
     * Set a password.
     */
    private ?string $password = null;

    /**
     * Constructor.
     */
    public function __construct(
        FilePath|string      $destFile,
        FilePath|string      $mysqldumpPath,
        bool                 $compress = false
    )
    {
        $this->snapshotFile  = FilePath::fromPath($destFile);
        $this->mysqldumpPath = FilePath::fromPath($mysqldumpPath ?: '/usr/bin/mysqldump');
        $this->compress      = $compress;
    }

    /**
     * Setup a new dump
     *
     * @return $this
     */
    public function newDump($options): static
    {
        if ($options) {
            $this->options = $options;
        } else {
            $this->options = [];
        }

        $this->databases       = [];
        $this->databaseOptions = [];
        $this->password        = null;

        return $this;
    }

    /**
     * Set the username
     *
     * @return $this
     */
    public function setUser(?string $user): static
    {
        if ($user === null) {
            unset($this->options['-u']);
        } else {
            $this->options['-u'] = $user;
        }

        return $this;
    }

    /**
     * Set the password
     *
     * @return $this
     */
    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set the hostname
     *
     * @return $this
     */
    public function setHost(string $host): static
    {
        $this->options['-h'] = $host;

        return $this;
    }

    /**
     * Set the MySQL port
     *
     * @return $this
     */
    public function setPort(string|int $port): static
    {
        $this->options['-P'] = $port;

        return $this;
    }

    /**
     * Set additional options
     *
     * @return $this
     */
    public function setOptions($options): static
    {
        $options = is_array($options) ? $options : [$options];

        foreach ($options as $option) {
            $this->options[$option] = true;
        }

        return $this;
    }

    public function setDatabaseOptions(string $database, array $options): static
    {
        $this->databaseOptions[$database] = $options;

        return $this;
    }


    public function setCompressionLevel(int $compressionLevel): static
    {
        $this->compressionLevel = $compressionLevel > 9 ? 9 : (max($compressionLevel, 1));
        return $this;
    }

    /**
     * Encrypt the snapshot while it's dumped.
     *
     * @return $this
     */
    public function setEncryption(?string $key, string $method = 'AES-128-CBC'): static
    {
        $this->encryptionKey    = $key;
        $this->encryptionMethod = $method;

        return $this;
    }

    /**
     * Set the text added at the beginning of the snapshot, each element is a line.
     *
     * @return $this
     */
    public function setBeginText(string|array $lines): static
    {
        $this->beginText = is_array($lines) ? $lines : [$lines];

        return $this;
    }

    /**
     * Set the text added at the end of the snapshot, each element is a line.
     *
     * @return $this
     */
    public function setEndText(string|array $lines): static
    {
        $this->endText = is_array($lines) ? $lines : [$lines];

        return $this;
    }

    /**
     * Add tables to ignore.
     *
     * @return $this
     */
    public function addIgnoreTables(string $database, array $tables): static
    {
        $this->ignoreTables[$database] = array_merge(
            $this->ignoreTables[$database] ?? [],
            array_map(fn($r) => $database . '.' . $r, $tables)
        );

        return $this;
    }

    /**
     * Specify locations.
     *
     * @return void
     */
    public function addLocation(string $database, string $location)
    {
        $this->locations[$database] = $location;
    }

    /**
     * Add table.
     *
     * @return $this
     */
    public function addTable(string $database, string $table, ?string $where = null, bool $onlyStruct = false): static
    {
        $this->tables[$database . ' ' . $table] = [
            'database'    => $database,
            'table'       => $table,
            'where'       => $where,
            'only_struct' => $onlyStruct,
        ];

        return $this;
    }

    /**
     * Add view.
     *
     * The views of a database are dumped together after its tables, so mysqldump
     * resolves the dependencies between them.
     *
     * @return $this
     */
    public function addView(string $database, string $view): static
    {
        $this->views[$database][$view] = $view;

        return $this;
    }

    /**
     * Add a database
     *
     * @return $this
     */
    public function addDatabase(string $database): static
    {
        $this->databases[] = $database;

        return $this;
    }

    public function initialize(): static
    {
        if (!$this->snapshotFile->truncate()) {
            throw new \RuntimeException('Unable to write file ' . $this->snapshotFile);
        }

        return $this;
    }

    /**
     * Process, compress and encrypt snapshot
     *
     * @param OutputStyle|null $output
     * @return $this
     */
    public function process(?OutputStyle $console = null): static
    {
        if (!$this->snapshotFile->exists()) {
            $this->initialize();
        }

        $this->snapshotHandle = fopen($this->snapshotFile->absolutePath(), 'ab');

        if (!$this->snapshotHandle) {
            throw new \RuntimeException('Unable to write file ' . $this->snapshotFile);
        }

        try {
            // The whole snapshot is encrypted, so it never reaches the disk unencrypted
            $this->encryptedWriter = $this->encryptionKey
                ? new EncryptedWriter($this->snapshotHandle, $this->encryptionKey, $this->encryptionMethod)
                : null;

            $dumpCommands = $this->generateCommandForTables() + $this->generateCommandForDatabases();
            $bar          = $console?->createProgressBar(count(Arr::flatten($dumpCommands)) + 1);
            $bar?->advance();

            if ($this->beginText) {
                $this->addContentToSnapshot($this->formatText($this->beginText) . "\n");
            }

            foreach ($dumpCommands as $database => $commands) {

                if ($this->locations[$database] ?? null) {
                    $this->addContentToSnapshot(sprintf("USE %s;\n", $this->locations[$database]));
                }

                foreach ($commands as $command) {

                    if ($console?->isVerbose()) {
                        $console?->comment($command);
                    }

                    $process = Process::fromShellCommandline(
                        $command,
                        env: $this->password ? [static::PASSWORD_ENV_VARIABLE => $this->password] : null,
                        timeout: null
                    );

                    try {
                        $this->dumpToSnapshot($process);
                    } catch (ProcessFailedException $e) {
                        if ($console?->isVerbose()) {
                            $console?->error($e->getMessage() . ' - Errno ' . $e->getCode());
                        }

                        throw new \RuntimeException("Unable to dump snapshot: $command");
                    }

                    $this->addContentToSnapshot("\n\n");
                    $bar?->advance();
                }
            }

            if ($this->endText) {
                $this->addContentToSnapshot($this->formatText($this->endText));
            }

            $this->encryptedWriter?->finish();
        } finally {
            fclose($this->snapshotHandle);
            $this->snapshotHandle  = null;
            $this->encryptedWriter = null;
        }

        return $this;
    }


    /**
     * Stream the dump output into the snapshot.
     *
     * The output is compressed in-process, so the dump exit code is not
     * masked by a shell pipe and stderr never ends up in the snapshot.
     *
     * @param Process $process
     * @return void
     * @throws ProcessFailedException
     */
    protected function dumpToSnapshot(Process $process): void
    {
        // Each dump is a gzip member, it's possible to concatenate gzipped files.
        $deflate = $this->compress
            ? deflate_init(ZLIB_ENCODING_GZIP, ['level' => $this->compressionLevel])
            : null;

        $process->start();

        foreach ($process->getIterator(Process::ITER_SKIP_ERR) as $data) {
            $this->writeToSnapshot($deflate ? deflate_add($deflate, $data, ZLIB_NO_FLUSH) : $data);
        }

        if ($deflate) {
            $this->writeToSnapshot(deflate_add($deflate, '', ZLIB_FINISH));
        }

        $process->wait();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * Write content to the snapshot, encrypting it when required.
     *
     * @param string $content
     * @return void
     */
    protected function writeToSnapshot(string $content): void
    {
        if ($content === '') {
            return;
        }

        if ($this->encryptedWriter) {
            $this->encryptedWriter->write($content);
        } elseif (fwrite($this->snapshotHandle, $content) === false) {
            throw new \RuntimeException('Unable to write file ' . $this->snapshotFile);
        }
    }

    /**
     * Append content to snapshot.
     *
     * @param string $content
     * @return void
     */
    protected function addContentToSnapshot(string $content): void
    {
        if ($this->compress) {
            // It's possible to concatenate gzipped files.
            $content = gzencode($content, 0);
        }

        $this->writeToSnapshot($content);
    }

    /**
     * Format text lines replacing the placeholders.
     *
     * @param array $lines
     * @return string
     */
    protected function formatText(array $lines): string
    {
        return implode('', array_map(
            fn($line) => (new Placeholder)->replace((string) $line) . "\n",
            $lines
        ));
    }

    /**
     * Generate commands for tables and views.
     */
    protected function generateCommandForTables(): array
    {
        if (!$this->tables && !$this->views) {
            return [];
        }

        $command = $this->generateBaseCommand();

        $commands = [];

        foreach ($this->tables as $k => $tableRecord) {
            $commands[$tableRecord['database']][$k] = $command;
            $currentCommand                         = &$commands[$tableRecord['database']][$k];

            if ($this->databaseOptions[$tableRecord['database']] ?? null) {
                $currentCommand .= ' ' . implode(' ', $this->databaseOptions[$tableRecord['database']]);
            }

            // The where condition is not required when the data is not dumped
            if ($tableRecord['only_struct']) {
                $currentCommand .= ' --no-data';
            } elseif ($tableRecord['where']) {
                $currentCommand .= ' --where=' . escapeshellarg((new Placeholder)->replace($tableRecord['where']));
            }

            $currentCommand .= ' ' . $tableRecord['database'] . ' ' . $tableRecord['table'];
        }

        // Views are dumped by a single command after the tables of the same database
        foreach ($this->views as $database => $views) {
            $commands[$database]['views'] = $command;
            $currentCommand               = &$commands[$database]['views'];

            if ($this->databaseOptions[$database] ?? null) {
                $currentCommand .= ' ' . implode(' ', $this->databaseOptions[$database]);
            }

            $currentCommand .= ' ' . $database . ' ' . implode(' ', $views);
        }

        return $commands;
    }

    /**
     * Generates command for databases.
     */
    protected function generateCommandForDatabases(): array
    {
        if (!$this->databases) {
            return [];
        }

        $command = $this->generateBaseCommand();

        $commands = [];

        foreach ($this->databases as $k => $database) {
            $commands[$database][$k] = $command;
            $currentCommand          = &$commands[$database][$k];

            if ($this->databaseOptions[$database] ?? null) {
                $currentCommand .= ' ' . implode(' ', $this->databaseOptions[$database]);
            }

            foreach (($this->ignoreTables[$database] ?? []) as $ignoreTable) {
                $currentCommand .= ' --ignore-table=' . $ignoreTable;
            }

            $currentCommand .= ' --databases ' . $database;
        }

        return $commands;
    }

    /**
     * Generate the base command.
     *
     * @return string
     */
    protected function generateBaseCommand(): string
    {
        $command = $this->mysqldumpPath->path();

        // Add options
        foreach ($this->options as $option => $value) {
            if ($value === false) {
                continue;
            }

            $command .= ' ' . $option;

            if ($option[strlen($option) - 1] !== '=') {
                $command .= ' ';
            }

            if ($value !== true) {
                $command .= $value;
            }
        }

        return $command;
    }
}
