<?php

namespace App\Models;

use App\Models\Enums\FilePathScope;
use App\Models\Exceptions\ConfigFileException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Config implements \ArrayAccess
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Factory method.
     *
     * Read configuration from file.
     */
    public static function readFromFile(string $filePath): static
    {
        $filePath = FilePath::fromPath($filePath);

        if (! $filePath->exists(FilePathScope::EXTERNAL)) {
            throw new ConfigFileException('Unable to find config file', Command::FAILURE);
        }

        try {
            $config = Yaml::parseFile($filePath->absolutePath());
        } catch (ParseException $e) {
            throw new ConfigFileException($e->getMessage(), Command::FAILURE);
        }

        return new static($config);
    }

    public function __get(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->config[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->config[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->config[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->config[$offset]);
    }

    /**
     * Generate a basic configuration.
     */
    public static function generateConfig(
        string $name,
        string $catalogPath,
        string $snapshotPath,
    ): array {
        return [
            'name' => $name,
            'catalog_file' => $catalogPath,
            'snapshot_file' => $snapshotPath,
            'connection' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'username' => 'root',
                'password' => '',
            ],
            'mysqldump_path' => 'mysqldump',
            'backup_rotation' => 1,
            'compress' => false,
            'is_replica' => false,
            'databases' => [],
        ];
    }
}
