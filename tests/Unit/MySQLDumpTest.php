<?php

namespace Tests\Unit;

use App\Helpers\FileEncrypt;
use App\Models\MySQLDump;
use Tests\Concerns\FakesMysqldump;
use Tests\TestCase;

class MySQLDumpTest extends TestCase
{
    use FakesMysqldump;

    protected const DUMP_FILE = __DIR__ . '/../Fixtures/dump.sql';

    protected const STDERR_WARNING = 'WARNING: this line must not reach the snapshot';

    public function test_dump_plain()
    {
        $snapshot = $this->dump($this->fakeMysqldump($this->dumpScript(static::DUMP_FILE, static::STDERR_WARNING)), false);

        $content = file_get_contents($snapshot);
        $this->assertStringContainsString(file_get_contents(static::DUMP_FILE), $content);
        $this->assertStringNotContainsString(static::STDERR_WARNING, $content);
    }

    public function test_dump_compressed()
    {
        $snapshot = $this->dump($this->fakeMysqldump($this->dumpScript(static::DUMP_FILE, static::STDERR_WARNING)), true);

        $this->assertStringStartsWith("\x1f\x8b", file_get_contents($snapshot));

        $content = file_get_contents('compress.zlib://' . $snapshot);
        $this->assertStringContainsString(file_get_contents(static::DUMP_FILE), $content);
        $this->assertStringNotContainsString(static::STDERR_WARNING, $content);
    }

    public function test_dump_encrypted()
    {
        $snapshot = static::$tmpDir . 'snapshot_' . uniqid() . '.sql.aes';
        $dump     = file_get_contents(static::DUMP_FILE);

        $mysqldump = (new MySQLDump($snapshot, $this->fakeMysqldump($this->dumpScript(static::DUMP_FILE, static::STDERR_WARNING))))
            ->setEncryption('secret')
            ->addDatabase('db1')
            ->addDatabase('db2');

        $mysqldump->addLocation('db2', 'restored_db2');
        $mysqldump->process();

        $this->assertStringNotContainsString(strtok($dump, "\n"), file_get_contents($snapshot));

        FileEncrypt::decrypt($snapshot, $snapshot . '.decrypted', 'secret');
        $this->assertSame(
            $dump . "\n\n" . "USE restored_db2;\n" . $dump . "\n\n",
            file_get_contents($snapshot . '.decrypted')
        );
    }

    public function test_dump_fails_when_mysqldump_fails()
    {
        $this->expectException(\RuntimeException::class);

        $this->dump($this->fakeMysqldump($this->dumpScript(static::DUMP_FILE, static::STDERR_WARNING, 2)), true);
    }

    public function test_ignore_tables_per_database()
    {
        $snapshot = static::$tmpDir . 'snapshot_' . uniqid() . '.sql';

        // Output the received arguments instead of a dump
        (new MySQLDump($snapshot, $this->fakeMysqldump('echo "$@"')))
            ->addDatabase('db1')
            ->addDatabase('db2')
            ->addIgnoreTables('db1', ['logs'])
            ->addIgnoreTables('db2', ['sessions', 'cache'])
            ->process();

        $this->assertEquals(
            [
                '--ignore-table=db1.logs --databases db1',
                '--ignore-table=db2.sessions --ignore-table=db2.cache --databases db2',
            ],
            array_values(array_filter(explode("\n", file_get_contents($snapshot))))
        );
    }

    protected function dump(string $mysqldumpPath, bool $compress): string
    {
        $snapshot = static::$tmpDir . 'snapshot_' . uniqid() . '.sql';

        (new MySQLDump($snapshot, $mysqldumpPath, $compress))
            ->addDatabase('test')
            ->process();

        return $snapshot;
    }
}
