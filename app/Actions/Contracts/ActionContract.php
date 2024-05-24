<?php

namespace App\Actions\Contracts;

use App\Models\Config;
use App\Models\Placeholder;

interface ActionContract
{
    public function __construct(Config $config, Placeholder $placeholder, array $instructions = []);

    public function __invoke(): bool;
}
