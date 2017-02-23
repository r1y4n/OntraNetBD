<?php
	include_once( "sparql.php" );
	/* require the user as the parameter */
	if(isset($_GET['key'])) {

		/* soak in the passed variable or set our own */
		//$number_of_posts = isset($_GET['num']) ? intval($_GET['num']) : 10; //10 is the default
		$format = strtolower($_GET['format']) == 'xml' ? 'xml' : 'json'; //xml is the default
		//$user_id = intval($_GET['user']); //no default

		/* connect to the db */
		//$link = mysql_connect('localhost','root','') or die('Cannot connect to the DB');
		//mysql_select_db('ontranetbd_service',$link) or die('Cannot select the DB');

		/* grab the posts from the db */
		//$query = "SELECT post_title, guid FROM wp_posts WHERE post_author = $user_id AND post_status = 'publish' ORDER BY ID DESC LIMIT $number_of_posts";
		//$result = mysql_query($query,$link) or die('Errant query:  '.$query);
		initializeStore();
		//$result = getResult( $_GET['key'] );

		/* create one master array of the records */
		$responses = getResult( $_GET['key'] );

		/*if(mysql_num_rows($result)) {
			while($post = mysql_fetch_assoc($result)) {
				$posts[] = array('post'=>$post);
			}
		}*/

		/* output in necessary format */
		if($format == 'json') {
			header('Content-type: application/json');
			$responses = prepareResultJSON( $responses );
			//echo "<pre>";
			//echo json_encode(array('response'=>$responses));
			echo json_encode($responses);
			//print_r( $responses );
			//echo "<./pre>";
		}
		else {
			header('Content-type: text/xml');
			// echo '<responses>';
			// echo '<message>XML format unavailable.</message>';
			// echo '<key>'.$responses['key'].'</key>';
			// echo '<result>';
			// echo "0";
			// echo '</result>';
			// echo '</responses>';
			$responses = prepareResultXML( $responses );
			echo $responses;
		}

		/* disconnect from the db */
		//@mysql_close($link);
	}
	else {
		$responses = array(
			"message"	=>		"No Search Key",
			"result"	=>		""
		);
		header('Content-type: application/json');
		echo json_encode(array('response'=>$responses));
	}

	function getResult( $key ) {
		$key = trim( $key );
		if( $key == "" ) {
			return  array(
				"message"	=>		"Key is not found.",
				"key"		=>		$_GET['key'],
				"result"	=>		""
			);
			//echo "Please enter something to search.";
		}
		else {
			$ret = array(
				"Travel Attraction"	=>	NULL,
				"Location"			=>	NULL,
				"Accomodation"		=>	NULL,
				"Activity"			=>	NULL,
				"count"				=>	0
			);
			$result = isIndividualOrClass( $key, false );
			if( $result["verdict"] ) {
				if( $result["verdict"] == 1 ) { //if key is a named individual
					$types = getTypeOfIndividual( $key );
					foreach( $types as $type ) {
						$retn = array();
						$retn['Type'] = $type;
						if( $type == "Area" || $type == "District" ) {
							// find District and Tourist Spots if type is Area
							if( $type == "Area" ) { // Area
								$dist = getDistrict( $key );
								$retn['District'] = $dist;
								$spots = getSpotsInArea( $key );
							}
							else { // District
								$retn['District'] = "";
								$spots = getSpotsInDistrict( $key );
							}
							$retn['Travel Attraction'] = array();
							if( $spots['count'] > 0 ) {
								foreach($spots['result'] as $sp) {
									$spotTypes = getTypeOfIndividual( $sp['spot'] );
									foreach ($spotTypes as $k => $value) {
										if( $value != $type ) { $st = $value; break; }

									}
									if( $st == 'Hotel' || $st == 'Rented House' || $st == 'Resort' || $st == 'Rest House' ) continue;
									$retn['Travel Attraction'] = array_merge( $retn['Travel Attraction'], array( array( "spot" => $sp['spot'], "type" => $st ) ) );
								}
							}
							$ret["count"]++;
							$ret['Location'] = $retn;
						}
						else { // Travel Attraction & Accomodation
							//$spotTypes = getTypeOfIndividual( $key );
							//foreach ($types as $k => $value) {
							//	if( $value != $type ) { $st = $value; break; }
							//}
							$loc = getLocation( $key );
							//if( checkVowel( $type[0] ) ) $art = 'An';
							//else $art = 'A';
							$retn['Location'] = $loc;
							//$strArea = '<div class="well"><span class="label label-success">';
							//$strArea = $strArea . $art . ' ' . $type . '</span>';
							//$strArea = $strArea . '<br><small>' . "Location: " . $loc . '</small>';
							//echo $strArea . '</div>';

							//echo "Activities: " . '<br>';
							//echo "<br>";
							$retn['Open'] = "";
							$retn['Activity'] = "";
							$others = getSpotsOfType( $key, $type );
							//print "<pre>";  print_r($others);
							$retn['Similar Spots'] = array();
							if( $others['count'] > 0 ) {
								//echo "<br>Similar tourist spot(s) in Bangladesh:";
								//echo "<ul class='list-group'>";
								foreach ($others['result'] as $other) {
									if( $other['otsp'] != $key ) {
										//echo "<li class='list-group-item' onclick='triggerSearch(this)'>".$other['otsp'].'</li>';
										$retn['Similar Spots'] = array_merge( $retn['Similar Spots'], array( $other['otsp'] ) );
									}
								}
								//echo "</ul>";
							}
							//echo "<br><br>";
							$ret["Travel Attraction"] = $retn;
							$ret["count"]++;
							/// do work for Accomodation and Activities
						}

					}

				}
				else { //if key is a class
					$ret['type'] = $key;
					$members = getAllMembers( $key );
					if( $members != "Not Found" ) {
						$ret['members'] = array();
						$i = 0;
						foreach( $members as $mem ) {
							$ret['members'] = array_merge( $ret['members'], array( $mem['member'] ) );
							$i++;
						}
					}
					else {
						$ret['members'] = 0;
					}
				}
				return array(
					"message"	=>		"success",
					"key"		=> 		$key,
					"result"	=>		$ret
				);
			}
			else {
				return  array(
					"message"	=>		$result["message"],
					"key"		=>		$_GET['key'],
					"result"	=>		""
				);
			}
		}
	}

	function prepareResultJSON( $data ) {
		$verdict = array();
		$verdict['message'] = $data['message'];
		$verdict['key'] = $data['key'];
		//$verdict['no_of_instances'] = $data['result']['count'];
		$verdict['instances'] = array();
		if( $data['result'] == "" ) {
			$verdict['no_of_instances'] = 0;
			$verdict['instances'] = NULL;
		}
		else {
			$verdict['no_of_instances'] = $data['result']['count'];
			foreach( $data['result'] as $key => $value ) {
				if( $key != 'count' )
					$verdict['instances'][$key] = $value;
			}
		}
		return $verdict;
	}

	function prepareResultXML( $data ) {
		$types = array( 'Travel Attraction', 'Location', 'Accomodation', 'Activity' );
		$detailed_types = array(
			'Travel Attraction'	=>	array('Type','Location','Activity','Open','Similar Spots'),
			'Location'			=> 	array('Type','District','Travel Attraction'),
			'Accomodation'		=>	array('Type','Location'),
			'Activity'			=>	array('Type','Available at')
		);
		$xml = new DOMDocument( '1.0', 'utf-8' );
		$rootElm = $xml->createElement( 'response', "" );
		$elm = $xml->createElement( 'message', $data['message'] );
		$rootElm->appendChild( $elm );
		$elm = $xml->createElement( 'key', $_GET['key'] );
		$rootElm->appendChild( $elm );
		if( $data['result'] == "" ) $instance_count = 0;
		else $instance_count = $data['result']['count'];
		$elm = $xml->createElement( 'no_of_instances', $instance_count );
		$rootElm->appendChild( $elm );
		$insts = $xml->createElement( 'instances', '' );
		foreach( $types as $type ) {
			$inst = $xml->createElement( 'instance', '' );
			$inst_att = $xml->createAttribute( 'type' );
			$inst_att->value = $type;
			$inst->appendChild( $inst_att );
			$inst_att = $xml->createAttribute( 'has_instance' );
			if( $data['result'][$type] != "" ) $inst_att->value = 'true';
			else $inst_att->value = 'false';
			$inst->appendChild( $inst_att );
			$det_elm = $xml->createElement( 'details', '' );
			$tmpData = $data['result'][$type];
			// echo "<pre>";
			// print_r( $tmpData );
			// echo "</pre>";
			foreach( $detailed_types[$type] as $dets ) {
				//echo $dets."<br>";
				if( $dets == 'Similar Spots' || $dets == 'Travel Attraction' ) {
					$det_elm_child = $xml->createElement( 'detail', '' );
					$det_elm_child_att = $xml->createAttribute( 'type' );
					$det_elm_child_att->value = $dets;
					$det_elm_child->appendChild( $det_elm_child_att );
					// echo "<pre>";
					// print_r( $tmpData[$dets] );
					// echo "</pre>";
					// echo $dets;
					if( $tmpData[$dets] == '' ) continue;
					foreach( $tmpData[$dets] as $value ) {
						if( $dets == 'Similar Spots' ) {

							$again_child = $xml->createElement( 'spot', removeAmp( $value ) );
							$again_child_att = $xml->createAttribute( 'type' );
							$again_child_att->value = $tmpData['Type'];
							$again_child->appendChild( $again_child_att );
						}
						else {
							if( $value['type'] == 'Hotel' || $value['type'] == 'Rented House' || $value['type'] == 'Resort' || $value['type'] == 'Rest House' ) continue;
							$again_child = $xml->createElement( 'spot', removeAmp( $value['spot'] ) );
							$again_child_att = $xml->createAttribute( 'type' );
							$again_child_att->value = $value['type'];
							$again_child->appendChild( $again_child_att );
						}
						$det_elm_child->appendChild( $again_child );
					}
				}
				else {
					$det_elm_child = $xml->createElement( 'detail', $tmpData[$dets] );
					$det_elm_child_att = $xml->createAttribute( 'type' );
					$det_elm_child_att->value = $dets;
					$det_elm_child->appendChild( $det_elm_child_att );
				}
				$det_elm->appendChild( $det_elm_child );
			}
			$inst->appendChild( $det_elm );
			$insts->appendChild( $inst );
		}
		$rootElm->appendChild( $insts );
		$xml->appendChild( $rootElm );
		return $xml->saveXML();
	}

	function removeAmp( $str ) {
		return str_replace( "&", "&amp;", $str );
	}
?>
