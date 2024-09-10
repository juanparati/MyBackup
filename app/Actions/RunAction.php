<?php

namespace App\Actions;

use App\Actions\Contracts\ActionContract;
use Symfony\Component\Console\Command\Command;

class RunAction extends ActionBase implements ActionContract
{
    public function __invoke(): bool
    {
        $command = $this->placeholder->replace($this->instructions['command']);
        exec($command, result_code: $status);
        return $status == Command::SUCCESS;
    }
}
