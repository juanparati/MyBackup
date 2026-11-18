<?php

namespace Tests\Feature;

use App\Models\Config;
use App\Models\Lock;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Yaml\Yaml;
use Tests\Concerns\RequiresTmpFilesystem;
use Tests\TestCase;

class LockTest extends TestCase
{
    use RequiresTmpFilesystem;

    public function test_lock_registers_owner(): void
    {
        $this->prepareCatalog();

        Lock::lock();

        $lock = Lock::query()->first();
        $this->assertEquals(getmypid(), $lock->pid);
        $this->assertEquals(gethostname(), $lock->hostname);
        $this->assertFalse(Lock::isStale());
    }

    public function test_lock_is_stale_when_owner_is_not_running(): void
    {
        $this->prepareCatalog();

        Lock::create(['pid' => $this->finishedProcessPid(), 'hostname' => gethostname()]);

        $this->assertTrue(Lock::isStale());
    }

    public function test_lock_from_another_host_is_not_stale(): void
    {
        $this->prepareCatalog();

        Lock::create(['pid' => $this->finishedProcessPid(), 'hostname' => 'another-host']);

        $this->assertFalse(Lock::isStale());
    }

    public function test_lock_without_owner_is_not_stale(): void
    {
        $this->prepareCatalog();

        Lock::create();

        $this->assertFalse(Lock::isStale());
    }

    public function test_catalog_upgrade_registers_lock_owner(): void
    {
        $dir = $this->makeDir();

        // Catalog created by previous versions
        $pdo = new \PDO('sqlite:'.$dir.'catalog.sqlite');
        $pdo->exec('CREATE TABLE catalogs (id integer primary key autoincrement, snapshot text, crc varchar, size integer, created_at datetime, updated_at datetime)');
        $pdo->exec('CREATE TABLE locks (id integer primary key autoincrement, created_at datetime, updated_at datetime)');
        unset($pdo);

        $this->prepareCatalog($dir);

        $this->assertTrue(Schema::hasColumns('locks', ['pid', 'hostname']));
    }

    /**
     * Prepare the catalog through the unlock command.
     */
    protected function prepareCatalog(?string $dir = null): void
    {
        $dir = $dir ?: $this->makeDir();

        file_put_contents($dir.'config.yaml', Yaml::dump(
            Config::generateConfig('Lock test', $dir.'catalog.sqlite', $dir.'snapshot.sql')
        ));

        $this->artisan('unlock', ['config_file' => $dir.'config.yaml'])->assertOk();
    }

    protected function makeDir(): string
    {
        $dir = static::$tmpDir.uniqid().'/';
        mkdir($dir);

        return $dir;
    }

    /**
     * Retrieve the PID of a process that is no longer running.
     */
    protected function finishedProcessPid(): int
    {
        $process = proc_open(['true'], [], $pipes);
        $pid = proc_get_status($process)['pid'];
        proc_close($process);

        return $pid;
    }
}
