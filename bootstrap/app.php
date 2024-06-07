<?php

use LaravelZero\Framework\Application;

defined('IS_PHAR') OR define('IS_PHAR', ! empty(Phar::running()));
defined('DS') OR define('DS', IS_PHAR ? '/' : DIRECTORY_SEPARATOR);
defined('EXECUTABLE') OR define('EXECUTABLE', basename(Phar::running()));


return Application::configure(basePath: dirname(__DIR__))->create();
