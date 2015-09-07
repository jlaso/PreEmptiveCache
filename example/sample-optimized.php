<?php

$startTime = intval(date("U"));

require_once __DIR__.'/../vendor/autoload.php';

use JLaso\ToolsLib\ProgressBar;
use JLaso\ToolsLib\PreEmptiveCache;

$options = getopt("h::u::p::d::");

$host = isset($options['h']) ?  $options['h'] : 'localhost';
$user = isset($options['u']) ?  $options['u'] : 'root';
$password = isset($options['p']) ?  $options['p'] : '';
$database = isset($options['d']) ?  $options['d'] : 'test';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn){
    die(mysqli_error($conn));
}

$nRecords = mysqli_query($conn, 'SELECT COUNT(*) AS `qty` FROM `data`;')
    ->fetch_object()
    ->qty;

$xml = new DOMDocument("1.0");
$xml->formatOutput = true;
$xml->encoding = 'UTF-8';

$root = $xml->appendChild(new DOMElement('records'));

$debug = false;

if (!$debug) {
    $pBar = new ProgressBar($nRecords);
}

$familyCache = new PreEmptiveCache(function($id) use ($conn){
    return mysqli_query(
        $conn,
        sprintf(
            'SELECT `p`.`name` AS `product_name`, `p`.`ratio` AS `product_ratio`, '.
            '`p`.`family_id` AS `family_id`, `f`.`name` AS `family_name`, `f`.`ratio` AS `family_ratio` ' .
            'FROM `product` as `p` '.
            'LEFT JOIN `family` AS `f` ON `p`.`family_id`=`f`.`id` '.
            'WHERE `p`.`id`= %d',
            $id
        )
    )->fetch_assoc();
}, array(
    'maxRecordsCached' => 1000,
    'mode' => PreEmptiveCache::LESS_OLDEST_MODE,
    'debug' => $debug,
));

$totalCost = 0;
$offset = 0;
while ($offset < $nRecords){

    $record = mysqli_query($conn, 'SELECT * FROM `data` LIMIT 1 OFFSET '.$offset)->fetch_assoc();

    $xmlRecord = $root->appendChild(new DOMElement('record'));
    $xmlRecord->setAttribute('id', $record['id']);
    $xmlRecord->appendChild(new DOMElement('family', $record['product_id']));
    $xmlRecord->appendChild(new DOMElement('cost', $record['cost']));

    $product = $familyCache->fetch($record['product_id']);

    $productRatio = isset($product['product_ratio']) ? $product['product_ratio'] : 0;
    $familyRatio = isset($product['family_ratio']) ? $product['family_ratio'] : 0;
    $realCost = $record['cost'] * $productRatio * $familyRatio;

    $xmlRecord->appendChild(new DOMElement('relative_cost', $realCost));
    $xmlRecord->appendChild(new DOMElement('product', $product['product_name']));

    $totalCost += $realCost;
    $offset++;

    if (!$debug){
        $pBar->updateValue($offset);
    }
}

print "\n";

$xml->appendChild(new DOMElement('total_cost', $totalCost));
print $xml->saveXML();

print sprintf("this script lasted %d seconds !\n", intval(date("U")-$startTime));
