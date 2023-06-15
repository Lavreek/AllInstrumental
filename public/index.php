<?php

define('ROOT', dirname(__DIR__));

$styleHTML = file_get_contents(__DIR__ . "/css/style.html");
$endHTML = "";

$dirs = ['processed', 'resources', 'price', 'currency', 'logs'];

$sample = ['dir' => file_get_contents(__DIR__ . "/html/collapse-block.html")];
$sample += ['file' => file_get_contents(__DIR__ . "/html/file.html")];


foreach ($dirs as $dir) {
    $path = ROOT . "/" . $dir;
    $files = array_diff(scandir($path), ['.', '..']);
    $collapseFiles = "";

    $collapseBlock = $sample['dir'];
    $collapseBlock = str_replace(['[[+TITLE+]]', '[[+ID+]]'], $dir, $collapseBlock);

    $filesHTML = "";
    foreach ($files as $file) {
        if (is_dir($path . $file)) {
            continue;
        }

        $filesHTML .= sprintf(file_get_contents(__DIR__ . "/html/file.html"), mime_content_type($path . $file), base64_encode(file_get_contents($path . $file)), $file, $file);
    }

    $endHTML .= sprintf($collapseBlock, $filesHTML);
}



echo sprintf(file_get_contents(__DIR__ . "/html/index.html"), $styleHTML, $endHTML);
