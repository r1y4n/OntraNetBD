<?php

include_once('./config.php');

$store = ARC2::getStore($arc_config);

if (!$store->isSetUp()) {
 	$store->setUp(); 
}

$store->reset();

$filePath = dirname(__FILE__);
$fileName = '/ontranetbd.rdf';
$verdict =  $store->query('LOAD <file:///'.$filePath.$fileName.'>');

/*print "<pre>";
print_r( $verdict );
print "</pre>";*/

print "<pre>";
print_r( $verdict );
print "</pre>";
