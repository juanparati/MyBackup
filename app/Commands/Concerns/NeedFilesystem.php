<?php

namespace App\Commands\Concerns;

trait NeedFilesystem
{
    use NeedConfig;

    /**
     * Setup filesystems.
     */
    protected function setupFilesystems(): void
    {
        $filesystems = $this->config->filesystems ?? [];

        $filesystems['local'] = [
            'driver' => 'local',
            'root' => dirname($this->config->snapshot_file),
        ];

        config(['filesystems.disks' => $filesystems]);
    }
}
