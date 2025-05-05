<?php

namespace Tests\Feature;


use App\Models\FilePath;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestoreCommandTest extends TestCase
{

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

}
