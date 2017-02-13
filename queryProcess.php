<?php
include_once( "sparql.php" );

if( isset( $_POST["key"] ) ) {
	$key = trim( $_POST["key"] );
	if( $key == "" ) {
		echo '<div class="alert alert-warning" role="alert">' . 'Please enter something to search.' . '</div>';
		//echo "Please enter something to search.";	
	}
	else {
		$result = isIndividualOrClass( $key, true );
		if( $result["verdict"] ) {			
			if( $result["verdict"] == 1 ) { //if key is a named individual
				$types = getTypeOfIndividual( $key );
				foreach( $types as $type ) {
					//echo '<br>As "' . $type . '": ';
					if( $type == "Area" || $type == "District" ) {
						// find District and Tourist Spots if type is Area
						$strArea = '<div class="well"><span class="label label-success">';
						if( $type == "Area" ) { // Area
							$dist = getDistrict( $key );
							$strArea = $strArea . 'An area in District '.$dist.' in Bangladesh.<br>';
							$spots = getSpotsInArea( $key );
						}
						else { // District
							$strArea = $strArea . 'A District';
							$spots = getSpotsInDistrict( $key );
						}
						$strArea = $strArea . '</span></div>';
						echo $strArea;

						$cnt = $spots['count'];
						if( $cnt < 2 ) { $aux = "is"; $entry = "spot"; }
						else { $aux = "are"; $entry = "spots"; }
						if( $cnt == 0 ) { $cnt = "no"; $end = "."; }
						else $end = "-";
						echo "<div class='alert alert-success' role='alert'>" . "There ".$aux." "."<span class='badge'>".$cnt."</span>"." tourist ".$entry." in ".$key.".</div>";
						//print "<pre>";  print_r($spots);
						if( $spots['count'] > 0 ) {
							echo "<ul class='list-group'>";
							foreach($spots['result'] as $sp) {
								echo "<li class='list-group-item' onclick='triggerSearch(this)'>".$sp['spot'];
								$spotTypes = getTypeOfIndividual( $sp['spot'] );
								foreach ($spotTypes as $k => $value) {
									//echo "!!".$value."!!";
									if( $value != $type ) { $st = $value; break; }

								}
								echo ' ('.$st.')</li>';
							}
							echo "</ul>";
						}
						//echo "<br><br>";
					}
					else { // Travel Attraction & Accomodation
						//$spotTypes = getTypeOfIndividual( $key );
						//foreach ($types as $k => $value) {
						//	if( $value != $type ) { $st = $value; break; }
						//}
						$loc = getLocation( $key );
						if( checkVowel( $type[0] ) ) $art = 'An';
						else $art = 'A'; 

						$strArea = '<div class="well"><span class="label label-success">';
						$strArea = $strArea . $art . ' ' . $type . '</span>';
						$strArea = $strArea . '<br><small>' . "Location: " . $loc . '</small>';	
						echo $strArea . '</div>';

						//echo "Activities: " . '<br>';
						//echo "<br>";
						$others = getSpotsOfType( $key, $type );
						//print "<pre>";  print_r($others);
						if( $others['count'] > 0 ) {
							echo "<span style='color: #fff;'>Similar tourist spot(s) in Bangladesh:</span>";
							echo "<ul class='list-group'>";
							foreach ($others['result'] as $other) {
								if( $other['otsp'] != $key )
									echo "<li class='list-group-item' onclick='triggerSearch(this)'>".$other['otsp'].'</li>';
							}
							echo "</ul>";
						}
						
					}
					echo "<hr>";
				}
			}
			else { //if key is a class
				if( checkVowel( $result["message"][0] ) ) $aAn = "an";
				else $aAn = "a";
				//echo '"' . $key . '" is ' . $aAn . ' ' . $result["message"] . ".<br>";
				$members = getAllMembers( $key ); 
				$cntM = count( $members );
				if( $cntM == 0 || $cntM == 1 || ( $cntM == 1 && $members == "Not Found" ) ) {
					$aux = "is";
					$entry = "entry";
				}
				else {
					$aux = "are";
					$entry = "entries";
				}				
				if( $members != "Not Found" ) {
					echo "<div class='alert alert-success' role='alert'>" . "There ".$aux." "."<span class='badge'>".count($members)."</span>"." tourist ".$entry." as ".$key.".</div>";
					/*print "<pre>";
					print_r( $members );
					print "</pre>";*/
					echo '<ul class="list-group">';
					foreach( $members as $mem ) {
						echo '<li class="list-group-item"  onclick="triggerSearch(this)">' . $mem["member"] . '</li>';
					}
					echo '</ul>';
				}
				else {
					echo '<div class="alert alert-info" role="alert">' . 'There ' . $aux . ' no ' . $entry . ' for type "' . $key . '".' . '</div>';
				}
			}
		}
		else {
			echo $result["message"];
		}		
	}	
}
else {
	echo '<div class="alert alert-warning" role="alert">' . 'Nothing has been searched' . '</div>';
}

function checkVowel( $ch ) {
	$ch = strtolower( $ch );
	if( $ch == "a" || $ch == "a" || $ch == "e" || $ch == "i" || $ch == "o" || $ch == "u" ) return 1;
	else return 0;
}