<?php

namespace Tests\Feature;

use App\Commands\InitCommand;
use Tests\RequiresTmpFilesystem;
use Tests\TestCase;

class InitCommandTest extends TestCase
{
    use RequiresTmpFilesystem;


    public function testInitCommand(): void {
        $this->artisan(
            'init',
            ['config_file' => static::$tmpDir . '/config.yaml']
        )->assertOk();

        $this->assertFileExists(static::$tmpDir . '/config.yaml');

        $this->artisan(
            'init',
            [
                'config_file' => static::$tmpDir . '/config.yaml',
                '--overwrite' => true,
            ]
        )->assertOk();
    }

}
