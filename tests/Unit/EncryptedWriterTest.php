<?php

namespace Tests\Unit;

use App\Helpers\EncryptedWriter;
use App\Helpers\FileEncrypt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\RequiresTmpFilesystem;
use Tests\TestCase;

class EncryptedWriterTest extends TestCase
{
    use RequiresTmpFilesystem;

    /**
     * Content sizes around the encryption chunk size (160000 bytes).
     */
    public static function sizes(): array
    {
        return [[0], [1], [159999], [160000], [160001], [480000], [1048583]];
    }

    #[DataProvider('sizes')]
    public function test_written_content_can_be_decrypted(int $size): void
    {
        $plain = $size ? random_bytes($size) : '';
        $encrypted = static::$tmpDir.'encrypted_'.$size;

        $handle = fopen($encrypted, 'wb');
        $writer = new EncryptedWriter($handle, 'secret');

        // Write pieces of different sizes, like the ones produced by a dump
        $offset = 0;

        while ($offset < $size) {
            $length = random_int(1, 70000);
            $writer->write(substr($plain, $offset, $length));
            $offset += $length;
        }

        $writer->finish();
        fclose($handle);

        FileEncrypt::decrypt($encrypted, $encrypted.'.decrypted', 'secret');
        $this->assertSame($plain, file_get_contents($encrypted.'.decrypted'));
    }
}
