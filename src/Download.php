<?php

define('ROOT', dirname(__DIR__));

$usdStream = fopen('http://fluid-line.ru/curr_usd.txt', "r");
file_put_contents(ROOT . "/currency/curr_usd.txt", stream_get_contents($usdStream));
fclose($usdStream);

$euroStream = fopen('http://fluid-line.ru/curr_eur.txt', "r");
file_put_contents(ROOT . "/currency/curr_eur.txt", stream_get_contents($euroStream));
fclose($euroStream);

$gbpStream = fopen('http://fluid-line.ru/curr_gbp.txt', "r");
file_put_contents(ROOT . "/currency/curr_gbp.txt", stream_get_contents($gbpStream));
fclose($gbpStream);
