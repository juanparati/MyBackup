<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lock extends Model
{
    /**
     * Error number returned when a process doesn't exist.
     */
    protected const ESRCH = 3;

    /**
     * Guarded properties.
     *
     * @var string[]
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public static function hasLock(): bool
    {
        return static::query()->exists();
    }

    public static function lock(): void
    {
        if (! static::hasLock()) {
            static::create([
                'pid' => getmypid(),
                'hostname' => gethostname(),
            ]);
        }
    }

    public static function unlock(): void
    {
        static::query()->delete();
    }

    /**
     * Check if the lock was left by a process that is no longer running.
     *
     * Only locks created on the current host can be verified.
     */
    public static function isStale(): bool
    {
        $lock = static::query()->first();

        if (! $lock?->pid || $lock->hostname !== gethostname() || ! function_exists('posix_kill')) {
            return false;
        }

        // Signal 0 doesn't kill, it only checks if the process exists
        return ! posix_kill((int) $lock->pid, 0) && posix_get_last_error() === static::ESRCH;
    }
}
