<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use JLaso\ToolsLib\ProgressBar;
use JLaso\ToolsLib\RandomTokenizer;

$options = getopt("h::u::p::d::n::");

$host = isset($options['h']) ? $options['h'] : 'localhost';
$user = isset($options['u']) ? $options['u'] : 'root';
$password = isset($options['p']) ? $options['p'] : '';
$database = isset($options['d']) ? $options['d'] : 'test';
$nFamilies = isset($options['n']) ? intval($options['n']) : 1000;
$nRecords = isset($options['m']) ? intval($options['m']) : 100000;
$nProducts = isset($options['o']) ? intval($options['o']) : 10000;

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die(mysqli_error($conn));
}

print "Table: family\n";
$pBar = new ProgressBar($nFamilies);

for ($i = 0; $i < $nFamilies; $i++) {

    mysqli_query(
        $conn,
        sprintf(
            "INSERT IGNORE INTO `family` (`id`, `name`, `ratio`) VALUES ('%d', '%s', '%f')",
            $i, 'family #' . $i, rand(0, 1000) / 1000
        )
    );
    $pBar->updateValue($i);
}

$productNameGenerator = new RandomTokenizer();
print "Table: product\n";
$pBar = new ProgressBar($nProducts);

for ($i = 0; $i < $nProducts; $i++) {

    mysqli_query(
        $conn,
        sprintf(
            "INSERT IGNORE INTO `product` (`id`, `name`, `family_id`, `ratio`) VALUES ('%d', '%s', '%d', '%f')",
            $i, $productNameGenerator->getPhrase(), rand(0, $nFamilies), rand(0, 1000) / 1000
        )
    );
    $pBar->updateValue($i);
}

print "\nTable: data\n";
$pBar = new ProgressBar($nRecords);

for ($i = 0; $i < $nRecords; $i++) {

    mysqli_query(
        $conn,
        sprintf(
            "INSERT IGNORE INTO `data` (`id`, `product_id`, `cost`) VALUES ('%d', '%d', '%f')",
            $i, rand(0, $nProducts), rand(0, 100) / 1000
        )
    );
    $pBar->updateValue($i);

}

print "\nDone!\n";
