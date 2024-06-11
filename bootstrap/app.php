<?php

use LaravelZero\Framework\Application;

defined('IS_PHAR') or define('IS_PHAR', ! empty(Phar::running()));
defined('DS') or define('DS', IS_PHAR ? '/' : DIRECTORY_SEPARATOR);
defined('EXECUTABLE') or define('EXECUTABLE', basename(Phar::running()));

return Application::configure(basePath: dirname(__DIR__))->create();
