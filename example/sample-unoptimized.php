<?php

$startTime = intval(date("U"));

require_once __DIR__ . '/../vendor/autoload.php';

use JLaso\ToolsLib\ProgressBar;

/**
 * Class SampleUnoptimized
 *
 * This sample was converted to a class in order to obtain data with XDebug and analyze with qCacheGrind
 */
class SampleUnoptimized
{
    protected $conn;
    protected $startTime;

    /**
     * SampleUnoptimized constructor.
     */
    public function __construct()
    {
        $this->startTime = intval(date("U"));
        $options = getopt("h::u::p::d::");

        $host = isset($options['h']) ? $options['h'] : 'localhost';
        $user = isset($options['u']) ? $options['u'] : 'root';
        $password = isset($options['p']) ? $options['p'] : '';
        $database = isset($options['d']) ? $options['d'] : 'test';

        $this->conn = mysqli_connect($host, $user, $password, $database);

        if ( ! $this->conn)
        {
            die(mysqli_error($this->conn));
        }
    }

    /**
     * @param $id
     * @return array
     */
    protected function getRecord($id)
    {
        return mysqli_query(
            $this->conn,
            sprintf(
                'SELECT `p`.`name` AS `product_name`, `p`.`ratio` AS `product_ratio`, ' .
                '`p`.`family_id` AS `family_id`, `f`.`name` AS `family_name`, `f`.`ratio` AS `family_ratio` ' .
                'FROM `product` as `p` ' .
                'LEFT JOIN `family` AS `f` ON `p`.`family_id`=`f`.`id` ' .
                'WHERE `p`.`id`= %d',
                $id
            )
        )->fetch_assoc();
    }

    /**
     *
     */
    public function run()
    {
        $nRecords = mysqli_query($this->conn, 'SELECT COUNT(*) AS `qty` FROM `data`;')
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

            $record = mysqli_query($this->conn, 'SELECT * FROM `data` LIMIT 1 OFFSET ' . $offset)->fetch_assoc();
            $pid = $record['product_id'];

            $xmlRecord = $root->appendChild(new DOMElement('record'));
            $xmlRecord->setAttribute('id', $record['id']);
            $xmlRecord->appendChild(new DOMElement('product_id', $pid));
            $xmlRecord->appendChild(new DOMElement('cost', $record['cost']));

            $product = $this->getRecord($pid);

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

        print sprintf("this script lasted %d seconds !\n", intval(date("U") - $this->startTime));

    }
}

$sample = new SampleUnoptimized();
$sample->run();