<?php

namespace App\Helpers;

use App\Models\FilePath;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;

/**
 * Encryption/Decryption helper
 */
class GzipCompressor
{
    /**
     * Source of the file to read.
     */
    protected FilePath $fileSource;

    /**
     * Destination file.
     */
    protected FilePath $fileDest;

    /**
     * Constructor.
     */
    public function __construct(FilePath|string $fileSource, FilePath|string $fileDest)
    {
        $this->fileSource = FilePath::fromPath($fileSource);
        $this->fileDest = FilePath::fromPath($fileDest);
    }

    public function gzip(
        int $compressionLevel = 6,
        ?OutputStyle $output = null
    ): void {
        $bar = $output ? FileProgressReader::make($this->fileSource, $output) : null;

        $fpIn = fopen($this->fileSource->absolutePath(), 'r');
        $fpOut = gzopen($this->fileDest, 'wb'.max(min($compressionLevel, 9), 1));

        if (! $fpIn) {
            throw new \RuntimeException('Unable to open '.$this->fileSource->absolutePath());
        }

        if (! $fpOut) {
            throw new \RuntimeException('Unable to write to '.$this->fileDest);
        }

        while (! feof($fpIn)) {
            gzwrite($fpOut, FileProgressReader::progressRead($fpIn, 10240, $bar));
        }

        @fclose($fpIn);
        @gzclose($fpOut);
    }
}
