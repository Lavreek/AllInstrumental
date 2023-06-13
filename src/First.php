<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;

$readerInputFile = dirname(__DIR__) . "/resources/SuperAFlow-HyLok-_08.06.23.xlsx";
$writerOutputFile = dirname(__DIR__) . "/resources/NEW-SuperAFlow-HyLok-_08.06.23.xlsx";

$reader = new Reader();
$reader = IOFactory::createReader('Xlsx');

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
$newCountCol = 0;

foreach ($sheetData as $row => $values) {
    if ($row === 0) {
        $header = array_unique($values);

        $newPriceCol = getRealCol(array_search('Новая РРЦ', $header));
        $newCountCol = getRealCol(array_search('Количество', $header));

    } else {
        $xslxProductName = $values[2];

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

                    $sum = str_replace(',', '.', $price) * $currencyArray[$currency] * $nds;
                    $format = number_format($sum, 2, decimal_separator: '.', thousands_separator: '');

                    $activeSheet->setCellValueExplicit(
                        $newPriceCol . ($row + 1),
                        str_replace(',', '.', $format),
                        PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }
            }
        }
    }
}

$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($writerOutputFile);
