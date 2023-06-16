<?php

define('ROOT', dirname(__DIR__));

require_once dirname(__DIR__) . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;

$readerInputFile = ROOT . "/resources/lerdata.xls";
$writerOutputFile = ROOT . "/processed/prikat-lerdata-" . date("d-m-Y--H-i-s") . ".xlsx";
$editLog = file_put_contents(ROOT . "/logs/lerdata.log", "Start: " . date("H:i:s d-m-Y") . "\n");

$reader = new Reader();
$reader = IOFactory::createReader('Xls');

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

$newCountCol = 0;
$newPriceWithoutNDS = 0;
$newPriceNDS = 0;
$newPriceRecommended = 0;
$newTitleCol = 0;

foreach ($sheetData as $row => $values) {
    if ($row === 0) {
        $header = array_unique($values);

        $newCountCol = getRealCol(array_search('Максимальное кол-во заказа', $header));

        $newPriceWithoutNDS = getRealCol(array_search('Цена продукта без НДС', $header));
        $newPriceNDS = getRealCol(array_search('Цена продукта c НДС', $header));
        $newPriceRecommended = getRealCol(array_search('Рекомендованная розничная цена ', $header));

        $newTitleCol = array_search('Артикул поставщика', $header);

    } else {
        $xslxProductName = $values[$newTitleCol];

        $warehouse = fopen(ROOT . "/price/tovarnaskladeprice.csv", "r");

        $founded = false;

        if (!$founded) {
            while ($data = fgetcsv($warehouse, separator: "\t")) {
                if (isset($data[0], $data[1])) {
                    if (!isset($data[2])) {
                        $price = 0;
                    } else {
                        $price = $data[2];
                        if (empty($data[2])) {
                            $price = 0;
                        }
                    }

                    if (!isset($data[3])) {
                        $currency = "RUB";
                    } else {
                        $currency = $data[3];

                        if (empty($data[3])) {
                            $currency = "RUB";
                        }
                    }

                    $product = ['title' => $data[0], 'count' => $data[1]];

                    if ($product['title'] == $xslxProductName) {
                        $founded = true;

                        $editLog = file_put_contents(ROOT . "/logs/lerdata.log", "ENTERED: " . $xslxProductName . "\n", FILE_APPEND);

                        $activeSheet->setCellValue($newCountCol . ($row + 1), $product['count']);

                        $recommendedPrice = str_replace(',', '.', $price) * $currencyArray[$currency];

                        $activeSheet->setCellValueExplicit(
                            $newPriceWithoutNDS . ($row + 1),
                            str_replace(',', '.', number_format(($recommendedPrice / 1.24) / 1.2, 2, thousands_separator: '')),
                            PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );

                        $activeSheet->setCellValueExplicit(
                            $newPriceNDS . ($row + 1),
                            str_replace(',', '.', number_format($recommendedPrice / 1.24, 2, thousands_separator: '')),
                            PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );

                        $activeSheet->setCellValueExplicit(
                            $newPriceRecommended . ($row + 1),
                            str_replace(',', '.', number_format($recommendedPrice, 2, thousands_separator: '')),
                            PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );

                        $editLog = file_put_contents(ROOT . "/logs/lerdata.log", "EDITED: " . $xslxProductName . "\n", FILE_APPEND);
                        break;
                    }
                }
            }

            fclose($warehouse);

            if ($founded) {
                continue;
            }
        }

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
                        $editLog = file_put_contents(ROOT . "/logs/lerdata.log", "ENTERED FROM DEFAULT: " . $xslxProductName . "\n", FILE_APPEND);

                        $activeSheet->setCellValue($newCountCol . ($row + 1), $product['count']);

                        $recommendedPrice = str_replace(',', '.', $price) * $currencyArray[$currency];

                        $activeSheet->setCellValueExplicit(
                            $newPriceWithoutNDS . ($row + 1),
                            str_replace(',', '.', number_format(($recommendedPrice / 1.24) / 1.2, 2, thousands_separator: '')),
                            PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );

                        $activeSheet->setCellValueExplicit(
                            $newPriceNDS . ($row + 1),
                            str_replace(',', '.', number_format($recommendedPrice / 1.24, 2, thousands_separator: '')),
                            PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );

                        $activeSheet->setCellValueExplicit(
                            $newPriceRecommended . ($row + 1),
                            str_replace(',', '.', number_format($recommendedPrice, 2, thousands_separator: '')),
                            PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );

                        $editLog = file_put_contents(ROOT . "/logs/lerdata.log", "EDITED FROM DEFAULT: " . $xslxProductName . "\n", FILE_APPEND);
                        break;
                    }
                }
            }
            fclose($default);

            if ($founded) {
                continue;
            }
        }

        $editLog = file_put_contents(ROOT . "/logs/lerdata.log", "NOT EDITED: " . $xslxProductName . "\n", FILE_APPEND);
    }
}

$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($writerOutputFile);
