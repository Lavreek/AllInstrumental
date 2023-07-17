<?php

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

$folders = ['currency', 'logs', 'price', 'processed', 'resources'];

foreach ($folders as $folder) {
    if (!is_dir(ROOT . "/" . $folder)) {
        mkdir(ROOT . "/" . $folder);
    }
}
