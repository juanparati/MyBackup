<?php

namespace Tests\Unit;

use App\Models\MySQLDump;
use Tests\Concerns\RequiresTmpFilesystem;
use Tests\TestCase;

class MySQLDumpTest extends TestCase
{
    use RequiresTmpFilesystem;

    protected const DUMP_FILE = __DIR__ . '/../Fixtures/dump.sql';

    protected const STDERR_WARNING = 'WARNING: this line must not reach the snapshot';

    public function test_dump_plain()
    {
        $snapshot = $this->dump($this->fakeMysqldump(0), false);

        $content = file_get_contents($snapshot);
        $this->assertStringContainsString(file_get_contents(static::DUMP_FILE), $content);
        $this->assertStringNotContainsString(static::STDERR_WARNING, $content);
    }

    public function test_dump_compressed()
    {
        $snapshot = $this->dump($this->fakeMysqldump(0), true);

        $this->assertStringStartsWith("\x1f\x8b", file_get_contents($snapshot));

        $content = file_get_contents('compress.zlib://' . $snapshot);
        $this->assertStringContainsString(file_get_contents(static::DUMP_FILE), $content);
        $this->assertStringNotContainsString(static::STDERR_WARNING, $content);
    }

    public function test_dump_fails_when_mysqldump_fails()
    {
        $this->expectException(\RuntimeException::class);

        $this->dump($this->fakeMysqldump(2), true);
    }

    protected function dump(string $mysqldumpPath, bool $compress): string
    {
        $snapshot = static::$tmpDir . 'snapshot_' . uniqid() . '.sql';

        (new MySQLDump($snapshot, $mysqldumpPath, $compress))
            ->addDatabase('test')
            ->process();

        return $snapshot;
    }

    /**
     * Create a fake mysqldump that outputs the fixture dump and a warning to stderr.
     */
    protected function fakeMysqldump(int $exitCode): string
    {
        $path = static::$tmpDir . 'mysqldump_' . $exitCode;

        file_put_contents($path, sprintf(
            "#!/bin/sh\ncat %s\necho %s >&2\nexit %d\n",
            escapeshellarg(static::DUMP_FILE),
            escapeshellarg(static::STDERR_WARNING),
            $exitCode
        ));
        chmod($path, 0755);

        return $path;
    }
}
