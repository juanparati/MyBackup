<?php

namespace Feature;

use App\Helpers\FileEncrypt;
use App\Models\FilePath;
use Symfony\Component\Yaml\Yaml;
use Tests\RequiresTmpFilesystem;
use Tests\TestCase;

class DecryptCommandTest extends TestCase
{
    use RequiresTmpFilesystem;

    public function testDecryptCommand(): void
    {
        $confFile = FilePath::fromPath(__DIR__.'/../Fixtures/config_base.yaml');
        $encryptedFile = FilePath::fromPath(static::$tmpDir.'file.sql.aes');

        $conf = Yaml::parseFile($confFile);

        $succesfullEncrypted = FileEncrypt::encrypt(
            $confFile,
            $encryptedFile,
            $conf['encryption']['key'],
            $conf['encryption']['method']
        );

        $this->assertTrue($succesfullEncrypted);

        $this->artisan(
            'decrypt',
            [
                'config_file' => $confFile->absolutePath(),
                'backup_file' => $encryptedFile->absolutePath(),
            ]
        )->assertOk();

        $this->assertFileExists($encryptedFile->unwrapExtension());
    }
}
