<?php

namespace App\Actions;

use App\Actions\Contracts\ActionContract;
use App\Helpers\DeclarativeHumanDate;
use Illuminate\Support\Facades\Storage;

class DeleteOldAction extends ActionBase implements ActionContract
{
    public function __invoke(): bool
    {
        if (empty($this->instructions['period'])) {
            return false;
        }

        $period = DeclarativeHumanDate::relative($this->instructions['period']);
        $disk = Storage::disk($this->instructions['filesystem']);

        foreach ($disk->files() as $file) {
            if ($this->instructions['pattern'] && ! fnmatch($this->instructions['pattern'], $file)) {
                continue;
            }

            if (! ($lastModified = $disk->lastModified($file))) {
                continue;
            }

            if ($period->gt(now()->parse($lastModified))) {
                $disk->delete($file);
            }
        }

        return true;
    }
}
