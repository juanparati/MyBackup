<?php

namespace Tests\Concerns;

trait FakesMysqldump
{
    use RequiresTmpFilesystem;

    /**
     * Create a fake mysqldump executable that runs the given shell script.
     */
    protected function fakeMysqldump(string $script): string
    {
        $path = static::$tmpDir.'mysqldump_'.md5($script);

        file_put_contents($path, "#!/bin/sh\n".$script."\n");
        chmod($path, 0755);

        return $path;
    }

    /**
     * Shell script that outputs a dump file and a warning into stderr.
     */
    protected function dumpScript(string $dumpFile, string $warning, int $exitCode = 0): string
    {
        return sprintf(
            "cat %s\necho %s >&2\nexit %d",
            escapeshellarg($dumpFile),
            escapeshellarg($warning),
            $exitCode
        );
    }
}
