<?php

namespace Tests\Unit;

use App\Models\FilePath;
use Tests\TestCase;

class FilePathTest extends TestCase
{
    public function test_replace_by_home()
    {
        $home = getenv('HOME');

        $this->assertEquals(
            $home,
            FilePath::fromPath('~')->path()
        );

        $this->assertEquals(
            $home.'/foo/bar',
            FilePath::fromPath('~')->expand('foo', 'bar')->path()
        );
    }

    public function test_add_extension()
    {
        $this->assertEquals(
            'file.txt.gz',
            FilePath::fromPath('file.txt')->addExtension('gz')->path()
        );

        $this->assertEquals(
            'file.txt.gz',
            FilePath::fromPath('file.txt')->addExtension('.gz')->path()
        );
    }
}
