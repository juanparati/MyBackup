<?php

namespace App\Commands;

use App\Models\Catalog;
use App\Models\Config;
use App\Models\Enums\FilePathScope;
use App\Models\FilePath;
use App\Models\Lock;
use Faker\Core\File;
use Illuminate\Console\Concerns\PromptsForMissingInput;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Yaml\Yaml;

class InitCommand extends BackupCommand implements \Illuminate\Contracts\Console\PromptsForMissingInput
{
    use PromptsForMissingInput;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'init {config_file : configuration file}      
        {--overwrite : overwrite old configuration file}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Initialize backup plan configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->title('Initialize backup plan configuration');

        $this->newLine()->info('Checking for old configuration file...');

        $configFile = FilePath::fromPath($this->argument('config_file'));

        if ($configFile->exists(FilePathScope::EXTERNAL)) {

            $this->line('Configuration file already exists');

            if (!$this->option('overwrite')) {
                $this->error('Unable to overwrite configuration file, please delete the file or use the --overwrite option');
                return EXIT_FAILURE;
            }
        }

        if (!$configFile->hasExtension('.yaml'))
            $configFile->addExtension('.yaml');

        //$this->newLine()->info('Creating new configuration file'$this->line('')

        $config = Config::generateConfig(
            uniqid('Backup '),
            getcwd() . '/catalog.sqlite',
            getcwd() . '/snapshot_{{numeric:{{datetime}}}}.sql'
        );

        file_put_contents($configFile, Yaml::dump($config));
        $this->output->success('📝 Plan created');
    }



}
