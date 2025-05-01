<?php

namespace App\Commands\Concerns;

use App\Models\FilePath;
use Illuminate\Support\Facades\Process;

trait NeedSearchPath
{

    /**
     * Search for executable.
     *
     * @param FilePath|string $execPath
     * @param string|null $execName
     * @return FilePath|null
     */
    protected function searchPath(FilePath|string $execPath, ?string $execName = null): ?FilePath
    {
        $execPath = FilePath::fromPath($execPath);
        $execName = $execName ?: $execPath->basename();

        $this->newLine()->info("Checking $execName executable...");

        if ($execPath->exists()) {
            $this->line('Executable found!');
        } else {
            $this->line("Unable to locate $execName... trying to locate automatically");
            $execDumpPathSearch = Process::run(['which', $execPath->basename()]);

            if (! $execDumpPathSearch->successful()) {
                $this->error("Unable to find $execName");
                return null;
            }

            $execPath = FilePath::fromPath($execDumpPathSearch->output());
            unset($execDumpPathSearch);

            $this->line("Located at $execPath");
        }

        return $execPath;
    }

}
