<?php

$startTime = intval(date("U"));

require_once __DIR__ . '/../vendor/autoload.php';

use JLaso\ToolsLib\ProgressBar;

$analysis = array();

$options = getopt("h::u::p::d::");

$host = isset($options['h']) ? $options['h'] : 'localhost';
$user = isset($options['u']) ? $options['u'] : 'root';
$password = isset($options['p']) ? $options['p'] : '';
$database = isset($options['d']) ? $options['d'] : 'test';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die(mysqli_error($conn));
}

$nRecords = mysqli_query($conn, 'SELECT COUNT(*) AS `qty` FROM `data`;')
    ->fetch_object()
    ->qty;

$xml = new DOMDocument("1.0");
$xml->formatOutput = true;
$xml->encoding = 'UTF-8';

$root = $xml->appendChild(new DOMElement('records'));

$pBar = new ProgressBar($nRecords);

$totalCost = 0;
$offset = 0;
while ($offset < $nRecords) {

    $record = mysqli_query($conn, 'SELECT * FROM `data` LIMIT 1 OFFSET ' . $offset)->fetch_assoc();
    $pid = $record['product_id'];

    $xmlRecord = $root->appendChild(new DOMElement('record'));
    $xmlRecord->setAttribute('id', $record['id']);
    $xmlRecord->appendChild(new DOMElement('product_id', $pid));
    $xmlRecord->appendChild(new DOMElement('cost', $record['cost']));

    $product = mysqli_query(
        $conn,
        sprintf(
            'SELECT `p`.`name` AS `product_name`, `p`.`ratio` AS `product_ratio`, '.
            '`p`.`family_id` AS `family_id`, `f`.`name` AS `family_name`, `f`.`ratio` AS `family_ratio` ' .
            'FROM `product` as `p` '.
            'LEFT JOIN `family` AS `f` ON `p`.`family_id`=`f`.`id` '.
            'WHERE `p`.`id`= %d',
            $pid
        )
    )->fetch_assoc();
    $analysis[$pid] = isset($analysis[$pid]) ? $analysis[$pid] + 1 : 1;

    $productRatio = isset($product['product_ratio']) ? $product['product_ratio'] : 0;
    $familyRatio = isset($product['family_ratio']) ? $product['family_ratio'] : 0;
    $realCost = $record['cost'] * $productRatio * $familyRatio;

    $xmlRecord->appendChild(new DOMElement('relative_cost', $realCost));
    $xmlRecord->appendChild(new DOMElement('product_name', $product['product_name']));
    $xmlRecord->appendChild(new DOMElement('family_id', $product['family_id']));
    $xmlRecord->appendChild(new DOMElement('family_name', $product['family_name']));

    $totalCost += $realCost;
    $offset++;

    $pBar->updateValue($offset);

}

print "\n";

$xml->appendChild(new DOMElement('total_cost', $totalCost));
print $xml->saveXML();

print sprintf("this script lasted %d seconds !\n", intval(date("U") - $startTime));

// analysis part

function analyse($info)
{
    $total = $saved = $repeated = 0;
    foreach ($info as $id => $count) {
        if ($count > 1) {
            $saved += $count - 1;
            $repeated++;
        }
        $total += $count;
    }

    print_r(array(
        'total' => $total,
        'repeated' => $repeated,
        'saved' => $saved,
        'count' => count($info),
    ));
}

analyse($analysis);

$content = "";
foreach ($analysis as $id => $count) {
    $content .= sprintf("%s,%d\n", $id, $count);
}

file_put_contents('analysis.csv', $content);
