<?php

namespace App\Commands;

use App\Commands\Concerns\NeedConfig;
use App\Helpers\FileEncrypt;
use App\Models\Enums\FilePathScope;
use App\Models\FilePath;
use Symfony\Component\Console\Command\Command;

class DecryptCommand extends CommandBase
{
    use NeedConfig;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'decrypt {config_file : configuration file} {backup_file : backup file}
        {--R|--remove : remove encrypted file}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Decrypt backup file';

    /**
     * Execute command.
     *
     * @return int|void
     */
    public function handle()
    {

        $this->title('Decrypt snapshot');

        if ($configErrorCode = $this->readConfig()) {
            return $configErrorCode;
        }

        // Read encrypted backup
        $backupFile = FilePath::fromPath($this->argument('backup_file'));

        if (! $backupFile->exists(FilePathScope::EXTERNAL)) {
            $this->error('Backup file doesn\'t exists');

            return Command::INVALID;
        }

        if (! $backupFile->hasExtension('.aes')) {
            $this->error('Backup file doesn\'t have .aes extension');

            return Command::FAILURE;
        }

        $this->newLine()->info('🔏 Decrypting file...');
        $outputFile = FilePath::fromPath($backupFile->dirname().DS.$backupFile->simpleName());

        if ($outputFile->exists()) {
            $this->error($outputFile.' already exists');

            return Command::FAILURE;
        }

        $result = FileEncrypt::decrypt(
            $backupFile->absolutePath(),
            $outputFile,
            $this->config['encryption']['key'],
            $this->config['encryption']['method'],
            $this->output
        );

        if (! $result) {
            $this->error('Unable to decrypt file');

            return Command::FAILURE;
        }

        $this->newLine();

        if ($this->option('remove') && $outputFile->exists()) {
            $backupFile->rm();
            $this->line('Original file was removed: '.$backupFile);
        }

        $this->line("Decrypted file as $outputFile");

        $this->output->success('👍 Backup decrypted!');

        return Command::SUCCESS;
    }
}
