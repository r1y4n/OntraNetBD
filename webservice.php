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
			echo json_encode(array('response'=>$responses));
		}
		else {
			header('Content-type: text/xml');
			echo '<responses>';
			echo '<message>XML format unavailable.</message>';
			echo '<key>'.$responses['key'].'</key>';
			echo '<result>';
			/*if( is_array($responses['result']) ) {
				foreach($responses['result'] as $index => $response) {
					//if( $index == 0 ) $index = "res".$index;
					if(is_array($response)) {
						//echo '<'.$response.'>';
						foreach($response as $key => $value) {
							if( $key == 0 ) $key = "res".$key;
							echo '<'.$key.'>';
							if(is_array($value)) {
								foreach($value as $tag => $val) {
									echo '<'.$tag.'>'.htmlentities($val).'</'.$tag.'>';
								}
							}
							else {
								echo '<'.$key.'>'.htmlentities($value).'</'.$key.'>';
							}
							echo '</',$key,'>';
						}
						//echo '</'.$response.'>';
					}
					else {
						echo '<'.$index.'>'.htmlentities($response).'</'.$index.'>';
					}
				}
			}
			else {
				echo $responses['result'];
			}*/
			echo "0";
			echo '</result>';
			echo '</responses>';
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
			$ret = array();
			$result = isIndividualOrClass( $key, false );
			if( $result["verdict"] ) {			
				if( $result["verdict"] == 1 ) { //if key is a named individual
					//$ret = array();
					$types = getTypeOfIndividual( $key );
					foreach( $types as $type ) {
						//echo '<br>As "' . $type . '": ';			
						$retn = array();
						$retn['type'] = $type;			
						if( $type == "Area" || $type == "District" ) {
							
							// find District and Tourist Spots if type is Area
							//$strArea = '<div class="well"><span class="label label-success">';
							if( $type == "Area" ) { // Area
								
								$dist = getDistrict( $key );
								$retn['partOf'] = $dist;
								//$strArea = $strArea . 'An area in District '.$dist.' in Bangladesh.<br>';
								$spots = getSpotsInArea( $key );
							}
							else { // District
								//$strArea = $strArea . 'A District';
								//$retn['type'] = "District";
								$spots = getSpotsInDistrict( $key );
							}
							//$strArea = $strArea . '</span></div>';
							//echo $strArea;

							//$cnt = $spots['count'];
							//if( $cnt < 2 ) { $aux = "is"; $entry = "spot"; }
							//else { $aux = "are"; $entry = "spots"; }
							//if( $cnt == 0 ) { $cnt = "no"; $end = "."; }
							//else $end = "-";
							//echo "<div class='alert alert-success' role='alert'>" . "There ".$aux." "."<span class='badge'>".$cnt."</span>"." tourist ".$entry." in ".$key.".</div>";
							//print "<pre>";  print_r($spots);
							$retn['spots'] = array();
							if( $spots['count'] > 0 ) {
								//echo "<ul class='list-group'>";
								foreach($spots['result'] as $sp) {
									//echo "<li class='list-group-item' onclick='triggerSearch(this)'>".$sp['spot'];
									$spotTypes = getTypeOfIndividual( $sp['spot'] );
									foreach ($spotTypes as $k => $value) {
										//echo "!!".$value."!!";
										if( $value != $type ) { $st = $value; break; }

									}
									//echo ' ('.$st.')</li>';
									$retn['spots'] = array_merge( $retn['spots'], array( array( "spot" => $sp['spot'], "type" => $st ) ) );
								}
								//echo "</ul>";
							}
							//echo "<br><br>";
						}
						else { // Travel Attraction & Accomodation
							//$spotTypes = getTypeOfIndividual( $key );
							//foreach ($types as $k => $value) {
							//	if( $value != $type ) { $st = $value; break; }
							//}
							$loc = getLocation( $key );
							//if( checkVowel( $type[0] ) ) $art = 'An';
							//else $art = 'A'; 
							$retn['location'] = $loc;
							//$strArea = '<div class="well"><span class="label label-success">';
							//$strArea = $strArea . $art . ' ' . $type . '</span>';
							//$strArea = $strArea . '<br><small>' . "Location: " . $loc . '</small>';	
							//echo $strArea . '</div>';

							//echo "Activities: " . '<br>';
							//echo "<br>";
							$others = getSpotsOfType( $key, $type );
							//print "<pre>";  print_r($others);
							$retn['similar'] = array();
							if( $others['count'] > 0 ) {
								//echo "<br>Similar tourist spot(s) in Bangladesh:";
								//echo "<ul class='list-group'>";
								foreach ($others['result'] as $other) {
									if( $other['otsp'] != $key ) {
										//echo "<li class='list-group-item' onclick='triggerSearch(this)'>".$other['otsp'].'</li>';
										$retn['similar'] = array_merge( $retn['similar'], array( $other['otsp'] ) );
									}
								}
								//echo "</ul>";
							}
							//echo "<br><br>";
						}
						$ret[$type] = $retn;
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
?>
