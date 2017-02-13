<?php

$store = "";
$prefix = "	
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX owl: <http://www.w3.org/2002/07/owl#>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX  ontra: <http://www.semanticweb.org/root/ontologies/2014/7/OnTraNetBD#>
";

function initializeStore() {	
	include( "arc/ARC2.php" );
	include( "config.php" );
	$st = ARC2::getStore( $arc_config );

	if( !$st->isSetUp() ) {
		$st->setUp();
	}
	$GLOBALS['store'] = $st;
}

function isIndividualOrClass( $key, $load ) {
	if( $load ) initializeStore();
	$query = $GLOBALS['prefix'] . '
		ASK  { ?x rdfs:label "'. $key . '" . ?x a owl:NamedIndividual }
	';
	$result = $GLOBALS['store']->query( $query, 'raw' );
	if( $result ) {
		$retArray = array(
			"verdict"	=> 		1,
			"message"	=>		"Individual"
		);
		return $retArray;
	}
	else {
		$query = $GLOBALS['prefix'] . '
			ASK  { ?x rdfs:label "' . $key . '" . ?x a owl:Class }
		';
		$result = $GLOBALS['store']->query( $query, 'raw' );
		if( $result ) {
			$retArray = array(
				"verdict"	=> 		2,
				"message"	=>		"Category"
			);
			return $retArray;
		}
		else {
			$retArray = array(
				"verdict"	=> 		0,
				"message"	=>		"I don't know it."
			);
			return $retArray;
		}
	}
}

function getTypeOfIndividual( $key ) {
	//initializeStore();
	$query = $GLOBALS['prefix'] . '
		SELECT DISTINCT ?typeLabel ?subLabel 
		WHERE {  
			?x rdfs:label "' . $key . '" .  
			?type rdfs:label ?typeLabel . 
			?x rdf:type ?type . 
			OPTIONAL { 
				?x rdf:type ?sub . 
				?sub rdfs:subClassOf ?type . 
				?sub rdfs:label ?subLabel .
				FILTER( ?sub != ?type ) 
			} 
		}
	';
	$result = $GLOBALS['store']->query( $query, 'rows' );
	//echo "<br>" . $query . "<br>";
	/*print "<pre>";
	print_r( $result );
	print "</pre>";*/
	if( $result ) {
		$tmp = array();
		$tmp2 = array();
		foreach ( $result as $r ) {
			$tmp[$r["typeLabel"]] = $r["subLabel"];
			$tmp2[$r["typeLabel"]] = 1;
			if( !isset( $tmp2[$r["subLabel"]] ) ) {
				$tmp[$r["subLabel"]] = "";
				$tmp2[$r["typeLabel"]] = 1;
			}
		}
		/*print "<pre>";
		print_r( $tmp );
		print "</pre>";*/
		$tmp2 = array();
		$tmpCount = 0;
		foreach ($tmp as $key => $value) {
			if( $value == "" ) {
				$tmp2[$tmpCount] = $key;
				$tmpCount++;
			}
		}
		/*print "<pre>";
		print_r( $tmp2 );
		print "</pre>";*/
		return $tmp2;
	}
	else {
		return  "Not Found";
	}
}

function getAllMembers( $key ) {
	$query = $GLOBALS['prefix'] . '
		SELECT DISTINCT ?member
		WHERE { 
			?mem rdfs:label ?member .
		  	?cls rdfs:label "' . $key . '" .
		  	?mem rdf:type ?cls
		} 
	';
	$result = $GLOBALS['store']->query( $query, 'rows' );
	//echo "<br>" . $query . "<br>";
	/*print "<pre>";
	print_r( $result );
	print "</pre>";*/
	if( $result ) {
		return $result;
	}
	else {
		return  "Not Found";
	}
}

function getDistrict( $area ) {
	$query = $GLOBALS['prefix'] . '
		SELECT DISTINCT ?d
		WHERE { 
		  ?x rdfs:label ?d .  
		  ?y rdfs:label "' . $area . '" .
		  ?y ontra:isPartOf ?x
		}
	';
	$result = $GLOBALS['store']->query( $query, 'rows' );
	if( $result ) {
		return $result[0]['d'];
	}
	else {
		return  "Not Found";
	}
}

function getSpotsInArea( $area ) {
	$query = $GLOBALS['prefix'] . '
		SELECT DISTINCT ?spot
		WHERE { 
		  ?ts rdfs:label ?spot .  
		  ?ts rdf:type ?cls .
		  ?cls rdfs:label "Travel Attraction" .
		  ?ts ontra:isLocatedAt ?d .
		  ?d rdfs:label "'.$area.'"
		}
	';
	$result = $GLOBALS['store']->query( $query, 'rows' );
	//print "<pre>";  print_r($result);
	if( $result ) {
		$res = array(
			'count'		=>		count( $result ),
			'result'	=>		$result
		);			
	}
	else {
		$res = array(
			'count'		=>		count( $result ),
			'result'	=>		$result
		); 			
	}
	return $res;
}

function getSpotsInDistrict( $dist ) {
	$query = $GLOBALS['prefix'] . '
		SELECT DISTINCT ?spot
		WHERE { 
		  ?ts rdfs:label ?spot .  
		  ?ts rdf:type ?cls .
		  ?cls rdfs:label "Travel Attraction" .
		  ?ts ontra:isLocatedAt ?ar .
		  ?ar ontra:isPartOf ?d .
		  ?d rdfs:label "'.$dist.'"
		}
	';
	$result = $GLOBALS['store']->query( $query, 'rows' );
	//print "<pre>";  print_r($result);
	if( $result ) {
		$res = array(
			'count'		=>		count( $result ),
			'result'	=>		$result
		); 
	}
	else {
		$res = getSpotsInArea( $dist );
	}
	return $res;
}

function getLocation( $spot ) {
	$query = $GLOBALS['prefix'] . '
		SELECT DISTINCT ?loc
		WHERE { 
		  ?ts rdfs:label "'.$spot.'" .
		  ?ts ontra:isLocatedAt ?ar .
		  ?ar rdfs:label ?loc
		}  
	';
	$result = $GLOBALS['store']->query( $query, 'rows' );
	if( $result ) {
		$loc = $result[0]['loc'];
		$more = getDistrict( $loc );
		if( $more != "Not Found" ) $loc = $loc . ', ' . $more;
		return $loc;
	}
	else {
		return  "Not Found";
	}
}

function getSpotsOfType( $key, $type ) {
	$query = $GLOBALS['prefix'] . '
		SELECT DISTINCT ?otsp
		WHERE { 
		  ?ots rdfs:label ?otsp .
		  ?ots rdf:type ?ar .
		  ?ar rdfs:label "'.$type.'" .
		  FILTER( "'.$key.'" != ?otsp )
		} 
	';
	$result = $GLOBALS['store']->query( $query, 'rows' );
	//print "<pre>";  print_r($result);
	if( $result ) {
		$res = array(
			'count'		=>		count( $result ),
			'result'	=>		$result
		);
	}
	else {
		$res = array(
			'count'		=>		0,
			'result'	=>		$result
		);
	}
	return $res;
}

function getAllLabels() {
	//initializeStore();
	$query = $GLOBALS['prefix'] . '
		SELECT DISTINCT ?label
		WHERE { 
		  ?sub rdfs:label ?label
		}
	';
	$result = $GLOBALS['store']->query( $query, 'rows' );
	//print "<pre>";  print_r($result);
	if( $result ) {
		$res = array(
			'count'		=>		count( $result ),
			'result'	=>		$result
		); 
	}
	else {
		$res = array(
			'count'		=>		count( $result ),
			'result'	=>		$result
		); 
	}
	return $res;
}

function getLeafConcepts( $label ) {
	$query = $GLOBALS['prefix'] . '
		SELECT DISTINCT ?label
		WHERE { 
		  	?sub rdfs:label ?label .
		  	?sub rdfs:subClassOf ?super .
	   		?super rdfs:label "'.$label.'"
		}
	';
	if( $result = $GLOBALS['store']->query( $query, 'rows' ) ) {
		$ret = array();
		foreach( $result as $row ) {
			$tmp = getLeafConcepts( $row['label'] );
			if( $tmp == false ) $tmp = array( $row['label'] );
			$ret = array_merge( $ret, $tmp );
		}
		return $ret;
	}
	else {
		return false;
	}	
}