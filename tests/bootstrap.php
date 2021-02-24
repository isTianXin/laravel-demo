<?php

// run prepare shell
$prepare_phpunit_script = env('PREPARE_PHPUNIT_SCRIPT', __DIR__ . './prepare_phpunit.sh');
if (!empty($prepare_phpunit_script)) {
    if (file_exists($prepare_phpunit_script)) {
        system($prepare_phpunit_script, $status);
        if ($status !== 0) {
            exit($status);
        }
    } else {
        echo 'prepare phpunit script file [' . $prepare_phpunit_script . '] does not exist' . PHP_EOL;
        exit(1);
    }
}
require_once __DIR__ . '/../vendor/autoload.php';
