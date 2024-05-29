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
     * Encrypt file
     *
     * @param string $filePath
     * @param string $dest
     * @param string $key
     * @param string $method
     * @param OutputStyle|null $output
     * @return bool
     */
    public static function encrypt(
        string $filePath,
        string $dest,
        string $key,
        string $method = 'AES-128-CBC',
        ?OutputStyle $output = null
    ): bool {
        $key = substr(sha1($key, true), 0, 16);

        $iv = openssl_random_pseudo_bytes(16);

        $bar = $output ? FileProgressReader::make($filePath, $output) : null;
        $error = false;

        if ($fpOut = fopen($dest, 'w')) {
            // Put the initialization vector to the beginning of the file
            fwrite($fpOut, $iv);

            if ($fpIn = fopen($filePath, 'rb')) {
                $block = 16 * static::ENCRYPTION_BLOCK_SIZES[$method];

                while (! feof($fpIn)) {
                    $plaintext = FileProgressReader::progressRead($fpIn, $block, $bar);
                    $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);

                    // Use the first 16 bytes of the ciphertext as the next initialization vector
                    $iv = substr($ciphertext, 0, 16);
                    fwrite($fpOut, $ciphertext);
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
        $key = substr(sha1($key, true), 0, 16);

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
