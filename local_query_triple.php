<?php

include_once( "arc/ARC2.php" );
include_once( "config.php" );

$store = ARC2::getStore( $arc_config );

if( !$store->isSetUp() ) {
	$store->setUp();
}

$q = '
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX owl: <http://www.w3.org/2002/07/owl#>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX ontra: <http://www.semanticweb.org/root/ontologies/2014/7/OnTraNetBD#>
		
	SELECT DISTINCT ?label
	WHERE { 
		?sub rdfs:label ?label .
	  	?sub rdfs:subClassOf ?super .
   		?super rdfs:label "Garden"
	} 
';

$rows = $store->query( $q, 'raw' );
	/*print "<pre>";
	print_r( $rows );
	print "</pre>";*/
$r = '';
if ($rows = $store->query( $q, 'rows' ) ) {
	print "<pre>";
	print_r( $rows );
	print "</pre>";
	/*$r = '<table border=1><th>Class</th><th>Sub-Class</th>'."\n";
	foreach ($rows as $row) {
		$r .= '<tr><td>'.$row['cl'] .
		'</td><td>'.$row['scl'] . '</td></tr>'."\n";
	}
	$r .='</table>'."\n";*/
}
else{
	//echo count( $rows );
	$r = '<em>No data returned</em>';
}
echo $r;

?>