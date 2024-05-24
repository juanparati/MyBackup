<?php

use LaravelZero\Framework\Application;

// List of exit codes
define('IS_PHAR', ! empty(Phar::running()));
define('DS', IS_PHAR ? '/' : DIRECTORY_SEPARATOR);
define('EXECUTABLE', basename(Phar::running()));

// Default exit codes
// @See: https://www.apt-browse.org/browse/ubuntu/trusty/main/amd64/libc6-dev/2.19-0ubuntu6/file/usr/include/sysexits.h
const EXIT_SUCCESS = 0;    // Successful termination (Everything is ok)
const EXIT_FAILURE = 1;    // Catchall for general errors
const EXIT_USAGE = 64;   // Command line usage error
const EXIT_DATAERR = 65;   // Data format error
const EXIT_NOINPUT = 66;   // Cannot open input
const EXIT_NOUSER = 67;   // Address unknown
const EXIT_NOHOST = 68;   // Host name unknown
const EXIT_UNAVAILABLE = 69;   // Service unavailable
const EXIT_SOFTWARE = 70;   // Internal software error
const EXIT_OSERR = 71;   // System error
const EXIT_OSFILE = 72;   // Critical OS file missing
const EXIT_CANTCREAT = 73;   // Can't create output file
const EXIT_IOERR = 74;   // Input/Output error
const EXIT_TEMPFAIL = 75;   // Temp failure
const EXIT_PROTOCOL = 76;   // Remote error in protocol
const EXIT_NOPERM = 77;   // Permission denied
const EXIT_CONFIG = 78;   // Configuration error

return Application::configure(basePath: dirname(__DIR__))->create();
