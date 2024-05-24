<?php

namespace App\Actions;

use App\Actions\Contracts\ActionContract;
use Illuminate\Support\Facades\Storage;

class CopyAction extends ActionBase implements ActionContract
{
    public function __invoke(): bool
    {
        $source = $this->placeholder->replace($this->instructions['source']);
        $destination = $this->placeholder->replace($this->instructions['destination']);

        return (bool) Storage::disk($this->instructions['filesystem'])
            ->putFileAs($source, $destination);
    }
}
