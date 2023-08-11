<?php

define('ROOT', dirname(__DIR__));

require_once ROOT . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;

$readerInputFile = ROOT . "/resources/SuperAFlow-HyLok-_08.06.23.xlsx";
$writerOutputFile = ROOT . "/processed/". time() ."-prikat-vseinsrumenti-" . date("d-m-Y--H-i-s") . ".xlsx";

$editLog = file_put_contents(
    ROOT . "/logs/vseinsrumenti.log", "Start: " . date("H:i:s d-m-Y") . "\n"
);

$reader = new Reader();
$reader = IOFactory::createReader('Xlsx');

$spreadsheet = $reader->load($readerInputFile);

$activeSheet = $spreadsheet->getActiveSheet();
$sheetData = $activeSheet->toArray();

$nds = 1.2;

$header = [];
$priceCol = 0;

$currencyFolder = ROOT . "/currency/";

$currencyArray = ['RUB' => 1];
$currencyArray += ['USD' => file_get_contents($currencyFolder . "curr_usd.txt")];
$currencyArray += ['EUR' => file_get_contents($currencyFolder . "curr_eur.txt")];
$currencyArray += ['GBP' => file_get_contents($currencyFolder . "curr_gbp.txt")];

function getRealCol(int $index) : string
{
    $default = "A";

    for ($i = 0; $i < $index; $i++) {
        $default++;
    }

    return $default;
}

$newPriceCol = 0;
$newCountCol = 0;
$newTitleCol = 0;

foreach ($sheetData as $row => $values) {
    if ($row === 0) {
        $header = array_unique($values);

        $newPriceCol = getRealCol(array_search('Новая РРЦ', $header));
        $newCountCol = getRealCol(array_search('Количество', $header));
        $newTitleCol = array_search('Артикул', $header);

    } else {
        $xslxProductName = $values[$newTitleCol];

        $warehouse = fopen(ROOT . "/price/tovarnaskladeprice.csv", "r");

        $founded = false;

        while ($data = fgetcsv($warehouse, separator: "\t")) {
            if (isset($data[0], $data[1])) {
                if (!isset($data[3])) {
                    $currency = "RUB";
                } else {
                    $currency = $data[3];

                    if (empty($data[3])) {
                        $currency = "RUB";
                    }
                }

                if (!isset($data[2])) {
                    $price = 0;
                } else {
                    $price = $data[2];
                    if (empty($data[2])) {
                        $price = 0;
                    }
                }

                $product = ['title' => $data[0], 'count' => $data[1]];

                if ($product['title'] == $xslxProductName) {
                    $founded = true;

                    $editLog = file_put_contents(ROOT . "/logs/vseinsrumenti.log", "ENTERED: " . $xslxProductName . "\n", FILE_APPEND);

                    $activeSheet->setCellValue($newCountCol . ($row + 1), $product['count']);

                    $sum = str_replace(',', '.', $price) * $currencyArray[$currency] * $nds;
                    $format = number_format($sum, 2, decimal_separator: '.', thousands_separator: '');

                    $activeSheet->setCellValueExplicit(
                        $newPriceCol . ($row + 1),
                        str_replace(',', '.', $format),
                        PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );

                    $editLog = file_put_contents(ROOT . "/logs/vseinsrumenti.log", "EDITED: " . $xslxProductName . "\n", FILE_APPEND);
                    break;
                }
            }
        }

        fclose($warehouse);

        if (!$founded) {
            $default = fopen(ROOT . "/price/defaultprice.csv", "r");

            while ($data = fgetcsv($default, separator: "\t")) {
                if (isset($data[0], $data[1])) {
                    if (!isset($data[3])) {
                        $currency = "RUB";
                    } else {
                        $currency = $data[3];

                        if (empty($data[3])) {
                            $currency = "RUB";
                        }
                    }

                    if (!isset($data[2])) {
                        $price = 0;
                    } else {
                        $price = $data[2];
                        if (empty($data[2])) {
                            $price = 0;
                        }
                    }

                    $product = ['title' => $data[0], 'count' => $data[1]];

                    if ($product['title'] == $xslxProductName) {
                        $founded = true;
                        $editLog = file_put_contents(ROOT . "/logs/vseinsrumenti.log", "ENTERED FROM DEFAULT: " . $xslxProductName . "\n", FILE_APPEND);

                        $activeSheet->setCellValue($newCountCol . ($row + 1), $product['count']);

                        $sum = str_replace(',', '.', $price) * $currencyArray[$currency] * $nds;
                        $format = number_format($sum, 2, decimal_separator: '.', thousands_separator: '');

                        $activeSheet->setCellValueExplicit(
                            $newPriceCol . ($row + 1),
                            str_replace(',', '.', $format),
                            PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );

                        $editLog = file_put_contents(ROOT . "/logs/vseinsrumenti.log", "EDITED FROM DEFAULT: " . $xslxProductName . "\n", FILE_APPEND);
                        break;
                    }
                }
            }

            fclose($default);
        }
    }
}

$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($writerOutputFile);
