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

$header = [];
$priceCol = 0;

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

function setRealPrice($money, $currency) {
    $currencyFolder = ROOT . "/currency/";

    $money = str_replace(',', '.', $money);

    switch ($currency) {
        case 'USD' : {
            return $money * file_get_contents($currencyFolder . "curr_usd.txt");
        }
        case 'EUR' : {
            return $money * file_get_contents($currencyFolder . "curr_eur.txt");
        }
        case 'GBP' : {
            return $money * file_get_contents($currencyFolder . "curr_gbp.txt");
        }
        default : {
            return $money;
        }
    }
}

function setPriceWithoutNDS($money) {
    return $money * 0.76;
}

function setPriceWithNDS($money) {
    $nds = 1.2;

    return $money * $nds;
}

function setPriceRecommended($money) {
    return $money * 1.2;
}

function setPriceCells(&$activeSheet, $row, $realPrice, $countColumn, $countValue, $priceWithoutNDSColumn, $priceNDSColumn, $PriceRecommendedColumn) {
    $activeSheet->setCellValue($countColumn . ($row + 1), $countValue);

    $priceWithoutNDS = setPriceWithoutNDS($realPrice);

    $activeSheet->setCellValueExplicit(
        $priceWithoutNDSColumn . ($row + 1),
        str_replace(',', '.', number_format($priceWithoutNDS, 2, thousands_separator: '')),
        PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
    );

    $activeSheet->setCellValueExplicit(
        $priceNDSColumn . ($row + 1),
        str_replace(',', '.', number_format(setPriceWithNDS($priceWithoutNDS), 2, thousands_separator: '')),
        PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
    );

    $priceRecommended = setPriceRecommended($realPrice);

    $activeSheet->setCellValueExplicit(
        $PriceRecommendedColumn . ($row + 1),
        str_replace(',', '.', number_format($priceRecommended, 2, thousands_separator: '')),
        PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
    );
}

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

        $founded = false;

        foreach ([
                ROOT . "/price/tovarnaskladeprice.csv",
                ROOT . "/price/new-stock-price.csv",
                ROOT . "/price/defaultprice.csv",
            ] as $filename) {

            $realPrice = 0;

            $file = fopen($filename, "r");

            while ($data = fgetcsv($file, separator: "\t")) {
                if (isset($data[0], $data[1], $data[2], $data[3])) {

                    $count = $data[1];

                    switch ($data[2]) {
                        case !isset($data[2]) :
                        case empty($data[2]) : {
                            $price = 0;
                            break;
                        }
                        default : {
                            $price = $data[2];
                            break;
                        }
                    }

                    switch ($data[3]) {
                        case !isset($data[3]) :
                        case empty($data[3]) : {
                            $currency = "RUB";
                            break;
                        }
                        default : {
                            $currency = $data[3];
                            break;
                        }
                    }

                    $realPrice = setRealPrice($price, $currency);

                    if ($data[0] == $xslxProductName) {
                        $founded = true;

                        $editLog = file_put_contents(ROOT . "/logs/lerdata.log", "ENTERED: in " . basename($filename) . " " . $xslxProductName . "\n", FILE_APPEND);

                        setPriceCells($activeSheet, $row, $realPrice, $newCountCol, $count, $newPriceWithoutNDS, $newPriceNDS, $newPriceRecommended);

                        $editLog = file_put_contents(ROOT . "/logs/lerdata.log", "EDITED: in " . basename($filename) . " " . $xslxProductName . "\n", FILE_APPEND);
                        break;
                    }
                }
            }

            fclose($file);

            if ($founded) {
                break;
            }
        }

        if (!$founded) {
            $editLog = file_put_contents(ROOT . "/logs/lerdata.log", "NOT EDITED: " . $xslxProductName . "\n", FILE_APPEND);
        }
    }
}

$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($writerOutputFile);
