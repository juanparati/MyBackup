<?php

namespace App\Commands;

use App\Helpers\FileEncrypt;
use App\Models\Enums\FilePathScope;
use App\Models\FilePath;
use Illuminate\Console\Concerns\PromptsForMissingInput;

class DecryptCommand extends CommandBase
{
    use PromptsForMissingInput;

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

        $this->readConfig();

        // Read encrypted backup
        $backupFile = FilePath::fromPath($this->argument('backup_file'));

        if (! $backupFile->exists(FilePathScope::EXTERNAL)) {
            $this->error('Backup file doesn\'t exists');

            return EXIT_DATAERR;
        }

        if ($backupFile->hasExtension('.aes')) {
            $this->error('Backup file doesn\'t have .aes extension');

            return EXIT_NOINPUT;
        }

        $this->newLine()->info('🔏 Decrypting file...');
        $outputFile = FilePath::fromPath($backupFile->dirname().DS.$backupFile->simpleName());

        if ($outputFile->exists()) {
            $this->error($outputFile.' already exists');

            return EXIT_FAILURE;
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

            return EXIT_CANTCREAT;
        }

        $this->newLine();

        if ($this->option('remove') && $outputFile->exists()) {
            $backupFile->rm();
            $this->line('Original file was removed: '.$backupFile);
        }

        $this->line("Decrypted file as $outputFile");

        $this->output->success('👍 Backup decrypted!');
    }
}
