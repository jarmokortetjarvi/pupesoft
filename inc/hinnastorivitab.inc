<?php

function hinnastorivi($tuote, $laskurow) {
    global $kukarow;
	
	
	//korvaavat tuotteet
	$koti 		= '';
	$edellinen 	= '';
	$seuraava 	= '';
	$korvaavat	= '';

	if ($tuote["id"] > 0) {
		$query = "SELECT * from korvaavat where id='$tuote[id]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
		$korvaresult = mysql_query($query) or pupe_error($query);

		$lask = 0;
		while ($korvarow = mysql_fetch_array($korvaresult)){
			$korvaavat[$lask] = $korvarow["tuoteno"];
			if ($korvarow["tuoteno"] == $tuote["tuoteno"]) {
				$koti = $lask;
			}
			$lask++;
		}
		//t�ss� listan viimeinen indeksi
		$lask--;

		//edellinen ja seuraava korvaava
		if ($koti == 0) {
			$edellinen	= '';
			$seuraava	= $korvaavat[$koti+1];
		}
		elseif($koti == $lask) {
			$edellinen	= $korvaavat[$koti-1];
			$seuraava	= '';
		}
		else{
			$edellinen	= $korvaavat[$koti-1];
			$seuraava	= $korvaavat[$koti+1];
		}
	}
	
	$myyntihinta = tuotteen_myyntihinta($laskurow, $tuote, 1, "ei");
    			
    // tehd��n tietuetta
	$out = $tuote['tuoteno']."\t";		// tuotenumero
	$out .= t_tuotteen_avainsanat($tuote, 'nimitys')."\t";		// tuotteen nimitys
	$out .= $tuote['eankoodi']."\t";	// tuotteen eankoodi alennusryhm� kahteen merkkiin asti zeropaddattuna
	$out .= t_avainsana("Y", "", "and avainsana.selite='$tuote[yksikko]'", "", "", "selite")."\t";		// tuotteen yksikko
	$out .= $tuote['aleryhma']."\t"; 	// tuotteen aleryhm�
	$out .= $myyntihinta."\t";	   		// tuotteen luettelohint
	$out .= $tuote['tuotemerkki']."\t";	// tuotemerkki
	$out .= $tuote['osasto']."\t";	   	// tuotteen osasto
	$out .= $tuote['try']."\t";			// tuoteryhm�
	$out .= $seuraava;					// korvaava tuote
	$out .= "\n";
	
    return $out;
}

?>