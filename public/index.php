<?php
ini_set('memory_limit', '512M');

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
    $collapseBlock = str_replace(['[[+TITLE+]]', '[[+ID+]]'], $dir, $sample['dir']);

    if ($dir === 'processed') {
        $collapseBlock = str_replace(['[[+SHOW+]]'], 'show', $collapseBlock);
    } else {
        $collapseBlock = str_replace(['[[+SHOW+]]'], '', $collapseBlock);
    }

    foreach (array_reverse($files) as $file) {
        if (
            is_dir($path . $file)
            or preg_match('#vseinsrumenti#', $file)
        ) {
            continue;
        }

        $collapseFiles .= sprintf(
            file_get_contents(__DIR__ . "/html/file.html"),
            mime_content_type($path . "/" . $file),
            base64_encode(file_get_contents($path . "/" . $file)),
            $file,
            $file
        );
    }

    $endHTML .= sprintf($collapseBlock, $collapseFiles);
}

echo sprintf(file_get_contents(__DIR__ . "/html/index.html"), $styleHTML, $endHTML);
