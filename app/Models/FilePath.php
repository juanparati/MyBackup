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

        $this->path = trim($path);
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
     * Copy file.
     */
    public function copy(
        string|FilePath $destination,
        bool $returnNew = false,
        bool $ignoreErrors = false
    ): FilePath|static {
        try {
            copy($this->path, $destination);
        } catch (\Exception $e) {
            if (! $ignoreError) {
                throw $e;
            }
        }

        return $returnNew ? FilePath::fromPath($destination) : $this;
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
        $fileExtension = '.'.$this->extension();
        $extension = $extension[0] === '.' ? $extension : ('.'.$extension);

        if (! $sensitive) {
            $fileExtension = Str::lower($fileExtension);
            $extension = Str::lower($extension);
        }

        return $fileExtension === $extension;
    }

    /**
     * Add extension to file.
     *
     * @return $this
     */
    public function addExtension(...$extensions): static
    {
        $extensions = is_array($extensions[0]) ? $extensions[0] : $extensions;

        foreach ($extensions as $extension) {
            $this->path .= ($extension[0] === '.' ? '' : '.').$extension;
        }

        return $this;
    }

    /**
     * Unwrap file extensions.
     *
     * @return $this
     */
    public function unwrapExtension(int $extensions = 1): static
    {
        $fileParts = str($this->path)
            ->explode('.');

        $this->path = $fileParts
            ->take($fileParts->count() - $extensions)
            ->implode('.');

        return $this;
    }

    /**
     * Expand directory path.
     *
     * @return $this
     */
    public function expand(...$subDirs): static
    {
        $subDirs = is_array($subDirs[0]) ? $subDirs[0] : $subDirs;

        if ($this->path[-1] !== DS) {
            $this->path .= DS;
        }

        $this->path .= implode(DS, $subDirs);
        $this->path = preg_replace('#'.DS.'+#', DS, $this->path);

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
     * Create a subdirectory.
     *
     * @return $this
     */
    public function mkdir(string $dir, int $permissions = 0777, bool $returnNew = false): FilePath
    {
        $recursive = str_word_count(DS) > 1;
        $target = str($this->path())->finish(DS).$dir;

        if (! mkdir($target, $permissions, $recursive)) {
            throw new \RuntimeException('Unable to create directory $dir');
        }

        return $returnNew ? FilePath::fromPath($target) : $this;
    }

    /**
     * Truncate file.
     */
    public function truncate(): bool
    {
        return ! (@file_put_contents($this->path, '') === false);
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
