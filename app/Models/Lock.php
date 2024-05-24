<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lock extends Model
{
    public static function hasLock(): bool
    {
        return static::query()->exists();
    }

    public static function lock(): void
    {
        if (! static::hasLock()) {
            static::create();
        }
    }

    public static function unlock(): void
    {
        static::query()->delete();
    }
}
