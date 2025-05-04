<?php

namespace Tests\Feature;

use Symfony\Component\Yaml\Yaml;
use Tests\Concerns\RequiresTmpFilesystem;
use Tests\TestCase;

class InitCommandTest extends TestCase
{
    use RequiresTmpFilesystem;

    public function test_init_command(): void
    {
        $this->artisan(
            'init',
            ['config_file' => static::$tmpDir.'config.yaml']
        )->assertOk();

        $this->assertFileExists(static::$tmpDir.'config.yaml');
        $originalConfig = Yaml::parseFile(static::$tmpDir.'config.yaml');

        $this->artisan(
            'init',
            [
                'config_file' => static::$tmpDir.'config.yaml',
                '--overwrite' => true,
            ]
        )->assertOk();

        $overwriteConfig = Yaml::parseFile(static::$tmpDir.'config.yaml');

        $this->assertNotEquals($originalConfig['name'], $overwriteConfig['name']);

        $this->artisan(
            'init',
            [
                'config_file' => static::$tmpDir.'config.yaml',
            ]
        )->assertFailed();
    }
}
