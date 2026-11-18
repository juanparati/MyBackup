<?php

namespace Tests\Feature;

use App\Helpers\FileEncrypt;
use App\Models\Catalog;
use App\Models\Config;
use App\Models\Lock;
use App\Notifications\BackupFailedNotification;
use App\Notifications\BackupFinishedNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Yaml\Yaml;
use Tests\Concerns\FakesMysqldump;
use Tests\TestCase;

/**
 * Requires a MySQL/MariaDB server, mysqldump is replaced by a fake executable.
 */
class BackupCommandTest extends TestCase
{
    use FakesMysqldump;

    protected const DUMP_FILE = __DIR__.'/../Fixtures/dump.sql';

    protected const STDERR_WARNING = 'WARNING: this line must not reach the snapshot';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_compressed_backup(): void
    {
        $dir = $this->makeBackupPlan(['compress' => true]);

        $this->artisan('backup', ['config_file' => $dir.'config.yaml'])->assertOk();

        $this->assertEquals(['catalog.sqlite', 'config.yaml', 'snapshot.sql.gz'], $this->files($dir));

        $content = file_get_contents('compress.zlib://'.$dir.'snapshot.sql.gz');
        $this->assertStringContainsString(file_get_contents(static::DUMP_FILE), $content);
        $this->assertStringNotContainsString(static::STDERR_WARNING, $content);

        $this->assertEquals(realpath($dir.'snapshot.sql.gz'), Catalog::query()->value('snapshot'));
        $this->assertFalse(Lock::hasLock());
        Notification::assertSentOnDemand(BackupFinishedNotification::class);
        Notification::assertNotSentTo(new AnonymousNotifiable, BackupFailedNotification::class);
    }

    public function test_encrypted_backup_does_not_keep_unencrypted_snapshot(): void
    {
        $dir = $this->makeBackupPlan([
            'compress' => true,
            'encryption' => ['key' => 'secret'],
        ]);

        $this->artisan('backup', ['config_file' => $dir.'config.yaml'])->assertOk();

        $this->assertEquals(['catalog.sqlite', 'config.yaml', 'snapshot.sql.gz.aes'], $this->files($dir));

        FileEncrypt::decrypt($dir.'snapshot.sql.gz.aes', $dir.'decrypted.sql.gz', 'secret');
        $this->assertStringContainsString(
            file_get_contents(static::DUMP_FILE),
            file_get_contents('compress.zlib://'.$dir.'decrypted.sql.gz')
        );
    }

    public function test_failed_backup_removes_partial_snapshot_and_notifies(): void
    {
        $dir = $this->makeBackupPlan(['compress' => true], 2);

        $this->artisan('backup', ['config_file' => $dir.'config.yaml'])->assertFailed();

        $this->assertEquals(['catalog.sqlite', 'config.yaml'], $this->files($dir));
        $this->assertEquals(0, Catalog::query()->count());
        $this->assertFalse(Lock::hasLock());

        Notification::assertSentOnDemand(
            BackupFailedNotification::class,
            fn (BackupFailedNotification $notification) => str_contains($notification->toSlack(null)->content, 'Unable to perform backup')
        );
    }

    public function test_backup_fails_when_locked_by_running_process(): void
    {
        $dir = $this->makeBackupPlan();
        $this->artisan('unlock', ['config_file' => $dir.'config.yaml'])->assertOk();
        Lock::create(['pid' => getmypid(), 'hostname' => gethostname()]);

        $this->artisan('backup', ['config_file' => $dir.'config.yaml'])->assertFailed();

        $this->assertTrue(Lock::hasLock());
        $this->assertEquals(['catalog.sqlite', 'config.yaml'], $this->files($dir));
        Notification::assertSentOnDemand(BackupFailedNotification::class);
    }

    public function test_backup_removes_stale_lock(): void
    {
        $dir = $this->makeBackupPlan();
        $this->artisan('unlock', ['config_file' => $dir.'config.yaml'])->assertOk();

        $process = proc_open(['true'], [], $pipes);
        Lock::create(['pid' => proc_get_status($process)['pid'], 'hostname' => gethostname()]);
        proc_close($process);

        $this->artisan('backup', ['config_file' => $dir.'config.yaml'])->assertOk();

        $this->assertFalse(Lock::hasLock());
        $this->assertEquals(['catalog.sqlite', 'config.yaml', 'snapshot.sql'], $this->files($dir));
    }

    /**
     * Create a backup plan into a new directory.
     */
    protected function makeBackupPlan(array $config = [], int $mysqldumpExitCode = 0): string
    {
        $dir = static::$tmpDir.uniqid().'/';
        mkdir($dir);

        $config = array_merge(
            Config::generateConfig('Backup test', $dir.'catalog.sqlite', $dir.'snapshot.sql'),
            [
                'mysqldump_path' => $this->fakeMysqldump(
                    $this->dumpScript(static::DUMP_FILE, static::STDERR_WARNING, $mysqldumpExitCode)
                ),
                'databases' => ['mybackup_test'],
                'notifications' => ['slack' => ['webhook' => 'https://hooks.slack.com/services/test']],
            ],
            $config
        );

        $config['connection']['host'] = '127.0.0.1';
        file_put_contents($dir.'config.yaml', Yaml::dump($config));

        return $dir;
    }

    /**
     * List the files of a directory.
     */
    protected function files(string $dir): array
    {
        return array_values(array_diff(scandir($dir), ['.', '..']));
    }
}
