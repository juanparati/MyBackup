<?php

namespace App\Models;

use App\Models\Enums\FilePathScope;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FilePath
{
    /**
     * Passed path.
     */
    protected string $path;

    /**
     * Contructor.
     */
    public function __construct(string $path)
    {
        // Replace home symbol
        if ($path[0] === '~') {
            $path = preg_replace('/~/', getenv('HOME'), $path, 1);
        }

        $this->path = $path;
    }

    /**
     * Factory method.
     */
    public static function fromPath(FilePath|string $path): static
    {
        return $path instanceof FilePath ? $path : new static($path);
    }

    /**
     * Retrieve the absolute path of a file path.
     */
    public function absolutePath(): string
    {
        return realpath($this->path);
    }

    /**
     * Retrieve the passed path.
     *
     * @return array|string|string[]|null
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Return file extension.
     */
    public function extension(): string
    {
        return File::extension($this->path);
    }

    /**
     * Check if file has a specific extension.
     */
    public function hasExtension(string $extension, bool $sensitive = false): bool
    {
        $fileExtension = $this->extension();

        if (! $sensitive) {
            $fileExtension = Str::lower($fileExtension);
            $extension = Str::lower($extension);
        }

        return $fileExtension === $extension;
    }


    /**
     * Add extension to file.
     *
     * @param ...$extensions
     * @return $this
     */
    public function addExtension(...$extensions): static
    {
        $extensions = is_array($extensions[0]) ? $extensions[0] : $extensions;

        foreach ($extensions as $extension) {
            $this->path .= (str($extension)->start('.') ? '' : '.') . $extension;
        }

        return $this;
    }

    /**
     * Check if file exists.
     */
    public function exists(FilePathScope $scope = FilePathScope::AUTO): bool
    {
        if ($scope === FilePathScope::EXTERNAL && IS_PHAR) {
            if ($this->path[0] != DS) {
                return file_exists(getcwd().DS.$this->path);
            }
        }

        if (file_exists($this->path)) {
            return true;
        }

        if ($scope === FilePathScope::AUTO) {
            if (IS_PHAR) {
                if (file_exists(dirname(\Phar::running(false)).DS.$this->path)) {
                    return true;
                }

                if (file_exists(getcwd().DS.$this->path)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * Retrieve the absolute directory path.
     */
    public function dirname(): string
    {
        return dirname($this->absolutePath());
    }

    /**
     * Retrieve the file base name.
     */
    public function basename(): string
    {
        return basename($this->path);
    }

    /**
     * Retrieve the file simple name.
     */
    public function simpleName(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Get file content.
     */
    public function content(): string
    {
        return File::get($this->absolutePath());
    }

    /**
     * Truncate file.
     */
    public function truncate(): void
    {
        @file_put_contents($this->path, '');
    }

    /**
     * Return the file md5 checksum.
     */
    public function md5(): ?string
    {
        return md5_file($this->absolutePath()) ?: null;
    }

    /**
     * Return the file size in bytes.
     */
    public function size(): int
    {
        return filesize($this->absolutePath());
    }

    /**
     * Remove file.
     */
    public function rm(): void
    {
        @unlink($this->absolutePath());
    }

    /**
     * Retrieve as string.
     */
    public function __toString(): string
    {
        return $this->path;
    }
}
