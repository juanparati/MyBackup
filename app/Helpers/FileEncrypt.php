<?php

namespace App\Helpers;

use Illuminate\Console\OutputStyle;

/**
 * Encryption/Decryption helper
 */
class FileEncrypt
{
    /**
     * Encryption block sizes according to the different encryption methods.
     */
    const ENCRYPTION_BLOCK_SIZES = [
        'AES-128-CBC' => 10000,
    ];

    /**
     * Derive the encryption key from the passphrase.
     */
    public static function deriveKey(string $key): string
    {
        return substr(sha1($key, true), 0, 16);
    }

    /**
     * Encrypt file
     */
    public static function encrypt(
        string $filePath,
        string $dest,
        string $key,
        string $method = 'AES-128-CBC',
        ?OutputStyle $output = null
    ): bool {
        $bar = $output ? FileProgressReader::make($filePath, $output) : null;
        $error = false;

        if ($fpOut = fopen($dest, 'w')) {
            $writer = new EncryptedWriter($fpOut, $key, $method);

            if ($fpIn = fopen($filePath, 'rb')) {
                $block = 16 * static::ENCRYPTION_BLOCK_SIZES[$method];

                while (! feof($fpIn)) {
                    $writer->write(FileProgressReader::progressRead($fpIn, $block, $bar));
                }

                $writer->finish();
                fclose($fpIn);
            } else {
                $error = true;
            }

            fclose($fpOut);
        } else {
            $error = true;
        }

        return $error ? false : $dest;
    }

    /**
     * Decrypt file
     */
    public static function decrypt(
        string $filePath,
        string $dest,
        string $key,
        string $method = 'AES-128-CBC',
        ?OutputStyle $output = null
    ): bool {
        $key = static::deriveKey($key);

        $bar = $output ? FileProgressReader::make($filePath, $output) : null;

        $error = false;
        if ($fpOut = fopen($dest, 'w')) {

            if ($fpIn = fopen($filePath, 'rb')) {
                // Get the initialization vector from the beginning of the file
                $iv = FileProgressReader::progressRead($fpIn, 16, $bar);
                $blockSize = 16 * (static::ENCRYPTION_BLOCK_SIZES[$method] + 1);

                while (! feof($fpIn)) {
                    // we have to read one block more for decrypting than for encrypting
                    $ciphertext = FileProgressReader::progressRead($fpIn, $blockSize, $bar);
                    $plaintext = openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);

                    // Wrong key or corrupted file
                    if ($plaintext === false) {
                        $error = true;
                        break;
                    }

                    // Use the first 16 bytes of the ciphertext as the next initialization vector
                    $iv = substr($ciphertext, 0, 16);
                    fwrite($fpOut, $plaintext);
                }

                fclose($fpIn);

            } else {
                $error = true;
            }

            fclose($fpOut);

        } else {
            $error = true;
        }

        return $error ? false : $dest;
    }
}
