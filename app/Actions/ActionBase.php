<?php

namespace App\Actions;

use App\Models\Config;
use App\Models\Placeholder;

abstract class ActionBase
{
    public function __construct(
        protected Config $config,
        protected Placeholder $placeholder,
        protected array $instructions = []) {}
}
