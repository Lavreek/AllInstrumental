<?php

define('ROOT', dirname(__DIR__));

$resourcePath = ROOT . "/resources/";
$files = array_diff(scandir($resourcePath), ['.']);

$styleHTML = file_get_contents(__DIR__ . "/css/style.html");
$endHTML = "";

foreach ($files as $file) {
    if (is_dir($resourcePath . $file)) {
        continue;
    }

    $endHTML .= sprintf(file_get_contents(__DIR__ . "/html/file.html"), mime_content_type($resourcePath . $file), base64_encode(file_get_contents($resourcePath . $file)), $file, $file);
}

echo sprintf(file_get_contents(__DIR__ . "/html/index.html"), $styleHTML, $endHTML);
