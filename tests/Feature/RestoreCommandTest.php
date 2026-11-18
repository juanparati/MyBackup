<?php

namespace Tests\Feature;


use App\Helpers\FileEncrypt;
use App\Models\FilePath;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Yaml\Yaml;
use Tests\Concerns\RequiresTmpFilesystem;
use Tests\TestCase;

class RestoreCommandTest extends TestCase
{
    use RequiresTmpFilesystem;

    public function test_simple_restore_command(): void
    {

        $confFile = FilePath::fromPath(__DIR__ . '/../Fixtures/config_simple_backup.yaml');
        $backupFile = FilePath::fromPath(__DIR__ . '/../Fixtures/test.sql');

        $this->artisan(
            'restore',
            [
                '--config_file' => $confFile->absolutePath(),
                'backup_file' => $backupFile->absolutePath(),
            ]
        )->assertOk();

        $this->assertEquals(101, DB::connection('target')->table('DATABASE1.table1')->count());
        $this->assertEquals(
            '630735ce-2812-11f0-aadc-3af90aa201f8',
            DB::connection('target')->table('DATABASE1.table1')->find(1)->name
        );

        $this->assertEquals(
            'NcYMMBzMneFRtrCPpcwVrTxEElqTwBtnjhhqgJes zSSMiDsbe',
            DB::connection('target')->table('DATABASE1.table2')->find(100)->name
        );

        $this->assertEquals(
            'oWPoXXT VeHbkVWZfArevRVdDIGgLmczqgJbgyaFcxaNOHSrds',
            DB::connection('target')->table('DATABASE2.table1')->find(100)->name
        );
    }

    public function test_encrypted_restore_command(): void
    {
        $backupFile = static::$tmpDir . 'encrypted_backup.sql.gz';
        file_put_contents($backupFile, gzencode(file_get_contents(__DIR__ . '/../Fixtures/test.sql')));
        FileEncrypt::encrypt($backupFile, $backupFile . '.aes', 'secret');
        unlink($backupFile);

        $this->artisan('restore', [
            '--config_file' => $this->makeConfig(['encryption' => ['key' => 'secret']]),
            'backup_file' => $backupFile . '.aes',
        ])->assertOk();

        $this->assertEquals(101, DB::connection('target')->table('DATABASE1.table1')->count());
        $this->assertEmpty(glob(static::$tmpDir . 'restore_*'), 'Decrypted backup file was not removed');
    }

    public function test_encrypted_restore_command_without_key(): void
    {
        $backupFile = static::$tmpDir . 'encrypted_without_key.sql';
        FileEncrypt::encrypt(__DIR__ . '/../Fixtures/test.sql', $backupFile . '.aes', 'secret');

        $this->artisan('restore', [
            '--config_file' => $this->makeConfig(),
            'backup_file' => $backupFile . '.aes',
        ])->assertFailed();
    }

    public function test_restore_command_without_statements(): void
    {
        $backupFile = static::$tmpDir . 'no_statements.sql';
        file_put_contents($backupFile, "This is not a SQL dump\n");

        $this->artisan('restore', [
            '--config_file' => $this->makeConfig(),
            'backup_file' => $backupFile,
        ])->assertFailed();
    }

    protected function makeConfig(array $config = []): string
    {
        $configFile = static::$tmpDir . uniqid('config_') . '.yaml';

        file_put_contents($configFile, Yaml::dump(array_merge(
            Yaml::parseFile(__DIR__ . '/../Fixtures/config_simple_backup.yaml'),
            $config
        )));

        return $configFile;
    }
}
