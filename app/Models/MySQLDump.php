<?php

namespace App\Models;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Arr;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
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
     * GZIP path
     */
    protected FilePath|null $gzipPath = null;

    /**
     * GZIP compression level
     *
     * @var int
     */
    protected int $compressionLevel = 6;

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
     * Dump locations.
     */
    protected array $locations = [];

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
        FilePath|string|null $gzipPath = null
    )
    {
        $this->snapshotFile  = FilePath::fromPath($destFile);
        $this->mysqldumpPath = FilePath::fromPath($mysqldumpPath ?: '/usr/bin/mysqldump');
        $this->gzipPath      = $gzipPath ? FilePath::fromPath($gzipPath) : null;
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
     * Add tables to ignore.
     *
     * @return $this
     */
    public function addIgnoreTables(string $database, array $tables): static
    {
        $this->ignoreTables[$database] = array_merge(
            $this->ignoreTables,
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
    public function addTable(string $database, string $table, ?string $where = null): static
    {
        $this->tables[$database . ' ' . $table] = [
            'database' => $database,
            'table'    => $table,
            'where'    => $where,
        ];

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
     * Process and compress snapshot
     *
     * @param OutputStyle|null $output
     * @return $this
     */
    public function process(?OutputStyle $console = null): static
    {
        if (!$this->snapshotFile->exists()) {
            $this->initialize();
        }

        $dumpCommands = $this->generateCommandForTables() + $this->generateCommandForDatabases();
        $bar          = $console?->createProgressBar(count(Arr::flatten($dumpCommands)) + 1);
        $bar?->advance();

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
                    $process->mustRun();
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

        return $this;
    }


    /**
     * Append content to snapshot.
     *
     * @param string $content
     * @return void
     */
    protected function addContentToSnapshot(string $content): void
    {
        if ($this->gzipPath) {
            // It's possible to concatenate gzipped files.
            $content = gzencode($content, 0);
        }

        file_put_contents($this->snapshotFile->absolutePath(), $content, FILE_APPEND);
    }

    /**
     * Generate commands for tables.
     */
    protected function generateCommandForTables(): array
    {
        if (!$this->tables) {
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

            if ($tableRecord['where']) {
                $currentCommand .= ' --where=' . escapeshellarg((new Placeholder)->replace($tableRecord['where']));
            }

            $currentCommand .= ' ' . $tableRecord['database'] . ' ' . $tableRecord['table'];

            if ($this->gzipPath)
                $currentCommand .= ' | ' . $this->gzipPath->path() . " -{$this->compressionLevel} -c";

            // Add dump output
            $currentCommand .= ' >> ' . $this->snapshotFile->absolutePath();

            // Suppress errors and warnings
            $currentCommand .= ' 2>&1';
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

            if ($this->gzipPath)
                $currentCommand .= ' | ' . $this->gzipPath->path() . " -{$this->compressionLevel} -c";

            // Add dump output
            $currentCommand .= ' >> ' . $this->snapshotFile->absolutePath();

            // Suppress errors and warnings
            $currentCommand .= ' 2>&1';

            echo $currentCommand;
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
