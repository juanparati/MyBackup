<?php

namespace App\Commands;

use Illuminate\Console\Concerns\PromptsForMissingInput;
use LaravelZero\Framework\Commands\Command;

abstract class CommandBase extends Command implements \Illuminate\Contracts\Console\PromptsForMissingInput
{
    use PromptsForMissingInput;
}
