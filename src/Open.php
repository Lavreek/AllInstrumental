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

$usdStream = fopen('http://fluid-line.ru/curr_usd.txt', "r");
$usd = stream_get_contents($usdStream);
fclose($usdStream);

$euroStream = fopen('http://fluid-line.ru/curr_eur.txt', "r");
$euro = stream_get_contents($euroStream);
fclose($euroStream);

$gbpStream = fopen('http://fluid-line.ru/curr_gbp.txt', "r");
$gbp = stream_get_contents($gbpStream);
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

        $warehouse = fopen(dirname(__DIR__) . "/resources/tovarnasklade.csv", "r");

        while ($data = fgetcsv($warehouse, separator: "\t")) {
            if (isset($data[0], $data[1]/*, $data[2], $data[3]*/)) {
                $product = ['title' => $data[0], 'count' => $data[1]/*, 'price' => $data[2], 'currency' => $data[3]*/];

                if ($product['title'] == $xslxProductName) {
                    $activeSheet->setCellValue($newCountCol . ($row + 1), $product['count']);
                    // $activeSheet->setCellValue($newPriceCol . ($row + 1), $product['price'] * $nds);
                }
            }
        }
    }
}

$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($writerOutputFile);
