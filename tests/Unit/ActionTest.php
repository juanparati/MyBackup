<?php

namespace Tests\Unit;

use App\Actions\CopyAction;
use App\Models\Config;
use App\Models\FilePath;
use App\Models\Placeholder;
use Tests\RequiresTmpFilesystem;
use Tests\TestCase;

class ActionTest extends TestCase
{
    use RequiresTmpFilesystem;


    public function testCopy()
    {

        $local = FilePath::fromPath(static::$tmpDir)->mkdir('localTest', returnNew: true);

        $dictionary = [
            'snapshot_file' => static::$tmpDir . 'test.txt'
        ];

        file_put_contents($dictionary['snapshot_file'], 'test');

        $filesystems = [
            'localTest' => [
                'driver' => 'local',
                'root'   => $local->absolutePath(),
            ],
        ];

        $config = new Config(['filesystems' => $filesystems]);
        config(['filesystems.disks' => $filesystems]);

        $success = (new CopyAction(
            $config,
            new Placeholder($dictionary),
            [
                'filesystem'  => 'localTest',
                'source'      => '{{snapshot_file}}',
                'destination' => '{{basename:{{snapshot_file}}}}'
            ]
        ))();

        $this->assertTrue($success);
        $this->assertFileExists($local->absolutePath() . '/test.txt');
    }

}
