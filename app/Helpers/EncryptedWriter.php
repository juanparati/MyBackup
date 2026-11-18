<?php

namespace App\Helpers;

/**
 * Encrypt content while it's written into a file handle.
 *
 * The content is encrypted in chunks using the FileEncrypt format, so it can be decrypted with FileEncrypt::decrypt.
 */
class EncryptedWriter
{
    /**
     * Encryption key.
     */
    protected string $key;

    /**
     * Current initialization vector.
     */
    protected string $iv;

    /**
     * Size of the plain chunks.
     */
    protected int $chunkSize;

    /**
     * Content pending to encrypt.
     */
    protected string $buffer = '';

    /**
     * Constructor.
     *
     * @param  resource  $handle
     */
    public function __construct(
        protected $handle,
        string $key,
        protected string $method = 'AES-128-CBC'
    ) {
        $this->key = FileEncrypt::deriveKey($key);
        $this->chunkSize = 16 * FileEncrypt::ENCRYPTION_BLOCK_SIZES[$method];
        $this->iv = openssl_random_pseudo_bytes(16);

        // Put the initialization vector to the beginning of the file
        $this->writeToHandle($this->iv);
    }

    /**
     * Write content.
     */
    public function write(string $content): void
    {
        $this->buffer .= $content;
        $length = strlen($this->buffer);
        $offset = 0;

        while ($length - $offset >= $this->chunkSize) {
            $this->encryptChunk(substr($this->buffer, $offset, $this->chunkSize));
            $offset += $this->chunkSize;
        }

        if ($offset) {
            $this->buffer = substr($this->buffer, $offset);
        }
    }

    /**
     * Encrypt the remaining content, it must be called once all the content was written.
     */
    public function finish(): void
    {
        $this->encryptChunk($this->buffer);
        $this->buffer = '';
    }

    /**
     * Encrypt a chunk of content.
     */
    protected function encryptChunk(string $plaintext): void
    {
        $ciphertext = openssl_encrypt($plaintext, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv);

        // Use the first 16 bytes of the ciphertext as the next initialization vector
        $this->iv = substr($ciphertext, 0, 16);
        $this->writeToHandle($ciphertext);
    }

    /**
     * Write into the file handle.
     */
    protected function writeToHandle(string $content): void
    {
        if (fwrite($this->handle, $content) === false) {
            throw new \RuntimeException('Unable to write encrypted content');
        }
    }
}
