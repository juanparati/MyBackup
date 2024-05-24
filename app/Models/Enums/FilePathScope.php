<?php

namespace App\Models\Enums;

enum FilePathScope
{
    case AUTO;          // Autodetect
    case PHAR;          // Inside PHAR file
    case EXTERNAL;      // External file
}
