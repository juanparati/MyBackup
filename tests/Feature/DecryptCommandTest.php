<?php

namespace Tests\Feature;

use App\Helpers\FileEncrypt;
use App\Models\FilePath;
use Symfony\Component\Yaml\Yaml;
use Tests\Concerns\RequiresTmpFilesystem;
use Tests\TestCase;

class DecryptCommandTest extends TestCase
{
    use RequiresTmpFilesystem;

    public function test_decrypt_command(): void
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

    public function test_decrypt_command_without_method_in_config(): void
    {
        $plainFile = static::$tmpDir.'no_method.sql';
        file_put_contents($plainFile, 'SELECT 1;');
        FileEncrypt::encrypt($plainFile, $plainFile.'.aes', 'secret');
        unlink($plainFile);

        $this->artisan('decrypt', [
            'config_file' => $this->makeConfig(['key' => 'secret']),
            'backup_file' => $plainFile.'.aes',
        ])->assertOk();

        $this->assertStringEqualsFile($plainFile, 'SELECT 1;');
    }

    public function test_decrypt_command_with_wrong_key(): void
    {
        $plainFile = static::$tmpDir.'wrong_key.sql';
        file_put_contents($plainFile, 'SELECT 1;');
        FileEncrypt::encrypt($plainFile, $plainFile.'.aes', 'secret');
        unlink($plainFile);

        $this->artisan('decrypt', [
            'config_file' => $this->makeConfig(['key' => 'wrong', 'method' => 'AES-128-CBC']),
            'backup_file' => $plainFile.'.aes',
        ])->assertFailed();

        $this->assertFileDoesNotExist($plainFile);
    }

    protected function makeConfig(array $encryption): string
    {
        $configFile = static::$tmpDir.uniqid('config_').'.yaml';
        file_put_contents($configFile, Yaml::dump(['name' => 'Decrypt test', 'encryption' => $encryption]));

        return $configFile;
    }
}
