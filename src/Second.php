<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;

$readerInputFile = dirname(__DIR__) . "/resources/New-10-05-lerdata.xls";
$writerOutputFile = dirname(__DIR__) . "/resources/NEW-New-10-05-lerdata.xlsx";

$reader = new Reader();
$reader = IOFactory::createReader('Xls');

$spreadsheet = $reader->load($readerInputFile);

$activeSheet = $spreadsheet->getActiveSheet();
$sheetData = $activeSheet->toArray();

$nds = 1.2;

$header = [];
$priceCol = 0;

$currencyArray = [];

$currencyArray += ['RUB' => 1];

$usdStream = fopen('http://fluid-line.ru/curr_usd.txt', "r");
$currencyArray += ['USD' => stream_get_contents($usdStream)];
fclose($usdStream);

$euroStream = fopen('http://fluid-line.ru/curr_eur.txt', "r");
$currencyArray += ['EUR' => stream_get_contents($euroStream)];
fclose($euroStream);

$gbpStream = fopen('http://fluid-line.ru/curr_gbp.txt', "r");
$currencyArray += ['GBP' => stream_get_contents($gbpStream)];
fclose($gbpStream);

function getRealCol(int $index) : string
{
    $default = "A";

    for ($i = 0; $i < $index; $i++) {
        $default++;
    }

    return $default;
}

$newPriceCol = 0;
$newPriceNDSCol = 0;
$newPriceRealCol = 0;
$newCountCol = 0;
$newTitleCol = 0;

foreach ($sheetData as $row => $values) {
    if ($row === 0) {
        $header = array_unique($values);

        $newPriceCol = getRealCol(array_search('Цена продукта без НДС', $header));
        $newPriceNDSCol = getRealCol(array_search('Цена продукта c НДС', $header));
        $newPriceRealCol = getRealCol(array_search('Рекомендованная розничная цена g', $header));
        $newCountCol = getRealCol(array_search('Кол-во в упаковке', $header));
        $newTitleCol = array_search('Артикул поставщика', $header);

    } else {
        $xslxProductName = $values[$newTitleCol];

        $warehouse = fopen(dirname(__DIR__) . "/resources/tovarnaskladeprice.csv", "r");

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
                    $activeSheet->setCellValue($newCountCol . ($row + 1), $product['count']);

                    $sum = str_replace(',', '.', $price) * $currencyArray[$currency];
                    $sumNDS = $sum * $nds;


                    $activeSheet->setCellValueExplicit(
                        $newPriceCol . ($row + 1),
                        str_replace(',', '.', number_format($sum, 2, thousands_separator: '')),
                        PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );

                    $activeSheet->setCellValueExplicit(
                        $newPriceNDSCol . ($row + 1),
                        str_replace(',', '.', number_format($sumNDS, 2, thousands_separator: '')),
                        PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );

                    $activeSheet->setCellValueExplicit(
                        $newPriceRealCol . ($row + 1),
                        str_replace(',', '.', number_format($sum, 2, thousands_separator: '')),
                        PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }
            }
        }
    }
}

$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($writerOutputFile);
