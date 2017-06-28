<?php
use mndesign\ToRSS;

require_once __DIR__ . '/../vendor/autoload.php';

$RSSFeed = new ToRSS\RSSFeed();

$configLocation = __DIR__ . '/../config/shows.json';

// Get shows
$file = json_decode(file_get_contents($configLocation), true);

header('Content-type: text/xml');

// Creating RSS header
echo $RSSFeed->createRSSHead();

// Creating RSS Body
foreach ($file as $key => $value) {
    $data = array(
        'query' => $value['query'],
        'ignore' => $value['ignore']
    );

    echo $RSSFeed->createRSS($data);
}

// Creating RSS Footer
echo $RSSFeed->createRSSFooter();