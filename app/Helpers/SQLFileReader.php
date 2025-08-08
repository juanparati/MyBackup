<?php

namespace App\Helpers;

use App\Models\FilePath;


/**
 * Read a SQL file statements
 */
class SQLFileReader
{

    protected const STATEMENT_END = ";\n";

    /**
     * Source of the file to read.
     */
    protected FilePath $sqlFile;


    /**
     * Resource file.
     *
     * @var false|resource
     */
    protected mixed $fp;


    /**
     * Total bytes read.
     *
     * @var int
     */
    protected int $totalRead = 0;


    /**
     * Total bytes in the file.
     *
     * @var int
     */
    protected int $fileSize;

    /**
     * Constructor.
     */
    public function __construct(FilePath|string $sqlFile)
    {
        $this->sqlFile = $sqlFile instanceof FilePath ? $sqlFile : FilePath::fromPath($sqlFile);

        if (!($this->fileSize = $this->sqlFile->size())) {
            throw new \RuntimeException('File is empty: ' . $this->sqlFile->path());
        }

        $this->fp = $this->sqlFile->hasExtension('gz') ?
            gzopen($this->sqlFile->path(), 'r') : fopen($this->sqlFile->path(), 'r');

        if (!$this->fp) {
            throw new \RuntimeException('Unable to read: ' . $this->sqlFile->path());
        }
    }


    /**
     * Read SQL statement.
     *
     * @return \Generator
     */
    public function readStatement(): \Generator
    {
        $buffer = '';

        while (!feof($this->fp)) {
            $line   = fgets($this->fp);
            $buffer .= $line;

            if (str($line)->endsWith(static::STATEMENT_END)) {
                $this->totalRead += strlen($line);
                yield $buffer;
                $buffer = '';
            }
        }
    }

    /**
     * Obtain the total file size.
     *
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * Obtain the total file read in bytes.
     *
     * @return int
     */
    public function getTotalRead(): int
    {
        return $this->totalRead;
    }
}
