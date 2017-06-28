<?php
	require_once __DIR__ . '/../vendor/autoload.php';
	$RSSFeed = new RSSFeed();

	// Get shows
	$file = json_decode(file_get_contents("inc/shows.json"),true);

	header('Content-type: text/xml'); 

	// Creating RSS header
	echo $RSSFeed->createRSSHead();
		
	// Creating RSS Body
	foreach ($file as $key => $value) {
		$data = array('query' => $value['query'],
				 	  'ignore' => $value['ignore']);

		echo $RSSFeed->createRSS($data);
	}
				  
	// Creating RSS Footer
	echo $RSSFeed->createRSSFooter();