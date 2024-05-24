<?php

namespace App\Helpers;

use App\Models\FilePath;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\ProgressBar;

class FileProgressReader
{
    /**
     * Make progress bar.
     */
    public static function make(FilePath|string $file, OutputStyle $output, int $div = 1024): ProgressBar
    {
        return $output->createProgressBar(File::size(FilePath::fromPath($file)->absolutePath()) / $div);
    }

    /**
     * Read from file pointer and report to progress bar if proceeds.
     *
     * @param  resource  $fp
     */
    public static function progressRead($fp, int $block, ?ProgressBar $bar = null, int $div = 1024): false|string
    {
        $stream = fread($fp, $block);

        if ($bar) {
            if (feof($fp)) {
                $bar->finish();
            } else {
                $bar->advance(strlen($stream) / 1024);
                $progress = $bar->getProgress();

                if ($progress && $progress % ($block * 100)) {
                    $bar->display();
                }
            }
        }

        return $stream;
    }
}
