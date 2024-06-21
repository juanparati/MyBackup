<?php

namespace App\Commands\Concerns;

trait NeedTargetConnection
{
    use NeedConfig;

    protected function setTargetConnection(): void
    {
        config([
            'database.connections.target' => array_merge(
                config('database.connections.target'),
                $this->config->connection
            ),
        ]);
    }
}
