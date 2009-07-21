<?php

	if (!isset($orderlisa)) {
		$orderlisa = '';
	}

	// Purkkafixi jotta saadaan kauniimmat dropdownit
	if (isset($yhtiorow['naytetaan_kaunis_os_try']) and $yhtiorow['naytetaan_kaunis_os_try'] == "K") {
		$orderlisa = "ORDER BY avainsana.selitetark";
	}
	else {
		$orderlisa = "ORDER BY avainsana.jarjestys, avainsana.selite+0";
	}
	
	if (!isset($mulselprefix) or $mulselprefix == "") {
		$mulselprefix = "tuote";
	}

	$lisa_haku_osasto 		 = "";
	$lisa_haku_try 			 = "";
	$lisa_haku_tme 			 = "";
	$lisa_haku_malli 		 = "";
	$lisa_haku_mallitarkenne = "";
	$lisa_haku_myyja		 = "";
	$lisa_haku_ostaja		 = "";
	
	if (!isset($mul_osasto)) $mul_osasto = array();
	if (!isset($mul_try)) $mul_try = array();
	if (!isset($mul_tme)) $mul_tme = array();
	if (!isset($mul_malli)) $mul_malli = array();
	if (!isset($mul_mallitarkenne)) $mul_mallitarkenne = array();
	if (!isset($mul_tuotemyyja)) $mul_tuotemyyja = array();
	if (!isset($mul_tuoteostaja)) $mul_tuoteostaja = array();

	// jos on valittu jotakin dropdowneista (muu kuin osasto) niin tehd��n niill� rajaukset muihin dropdowneihin
	if (count($mul_osasto) > 0 or count($mul_try) > 0 or count($mul_tme) > 0) {
		if (count($mul_osasto) > 0) {
			$osastot = '';

			foreach ($mul_osasto as $osx) {
				if (trim($osx) != '') {
					if (trim($osx) != "PUPEKAIKKIMUUT") {
						$osx = trim(mysql_real_escape_string($osx));
						$osastot .= "'$osx',";								
						$ulisa .= "&mul_osasto[]=".rawurlencode($osx);
					}					
				}
			}

			$osastot = substr($osastot, 0, -1);

			if (trim($osastot) != '') {
				$lisa_haku_osasto = " and tuote.osasto in ($osastot) ";
				$lisa .= " and $mulselprefix.osasto in ($osastot) ";						
			}
		}

		if (count($mul_try) > 0) {
			$tryt = '';

			foreach ($mul_try as $tryx) {
				if (trim($tryx) != '') {
					if (trim($tryx) != "PUPEKAIKKIMUUT") {
						$tryx = trim(mysql_real_escape_string($tryx));
						$tryt .= "'$tryx',";
						$ulisa .= "&mul_try[]=".rawurlencode($tryx);
					}					
				}
			}

			$tryt = substr($tryt, 0, -1);

			if (trim($tryt) != '') {
				$lisa_haku_try = " and tuote.try in ($tryt) ";
				$lisa .= " and $mulselprefix.try in ($tryt) ";
			}
		}

		if (count($mul_tme) > 0) {
			$tmet = '';

			foreach ($mul_tme as $tmex) {
				if (trim($tmex) != '') {
					if (trim($tmex) != "PUPEKAIKKIMUUT") {
						$tmex = trim(mysql_real_escape_string($tmex));
						$tmet .= "'$tmex',";
						$ulisa .= "&mul_tme[]=".rawurlencode($tmex);
					}					
				}
			}

			$tmet = substr($tmet, 0, -1);

			if (trim($tmet) != '') {
				$lisa_haku_tme = " and tuote.tuotemerkki in ($tmet) ";
				$lisa .= " and $mulselprefix.tuotemerkki in ($tmet) ";					
			}
		}

		if (count($mul_malli) > 0) {
			$mallit = '';

			foreach ($mul_malli as $mallix) {
				if (trim($mallix) != '') {
					if (trim($mallix) != "PUPEKAIKKIMUUT") {
						$mallix = trim(mysql_real_escape_string($mallix));
						$mallit .= "'$mallix',";
						$ulisa .= "&mul_malli[]=".rawurlencode($mallix);
					}					
				}
			}

			$mallit = substr($mallit, 0, -1);

			if (trim($mallit) != '') {
				$lisa_haku_malli = " and tuote.malli in ($mallit) ";
				$lisa .= " and $mulselprefix.malli in ($mallit) ";
			}
		}

		if (count($mul_mallitarkenne) > 0) {
			$mallitarkenteet = '';

			foreach ($mul_mallitarkenne as $mallitarkennex) {
				if (trim($mallitarkennex) != '') {
					if (trim($mallitarkennex) != "PUPEKAIKKIMUUT") {
						$mallitarkennex = trim(mysql_real_escape_string($mallitarkennex));
						$mallitarkenteet .= "'$mallitarkennex',";
						$ulisa .= "&mul_mallitarkenne[]=".rawurlencode($mallitarkennex);
					}					
				}
			}

			$mallitarkenteet = substr($mallitarkenteet, 0, -1);

			if (trim($mallitarkenteet) != '') {
				$lisa_haku_mallitarkenne = " and tuote.mallitarkenne in ($mallitarkenteet) ";
				$lisa .= " and $mulselprefix.mallitarkenne in ($mallitarkenteet) ";
			}
		}
	}
	
	if (count($mul_tuotemyyja) > 0) {
		$tuotemyyjat = '';

		foreach ($mul_tuotemyyja as $tuotemyyjax) {
			if (trim($tuotemyyjax) != '') {
				$tuotemyyjax = trim(mysql_real_escape_string($tuotemyyjax));
				$tuotemyyjat .= "'$tuotemyyjax',";
				$ulisa .= "&mul_tuotemyyja[]=".rawurlencode($tuotemyyjax);
			}
		}

		$tuotemyyjat = substr($tuotemyyjat, 0, -1);
		
		if (trim($tuotemyyjat) != '') {
			$lisa_haku_myyja = " and kuka.myyja in ($tuotemyyjat) ";
			$lisa .= " and $mulselprefix.myyjanro in ($tuotemyyjat) ";
		}
	}

	if (count($mul_tuoteostaja) > 0) {
		$tuoteostajat = '';

		foreach ($mul_tuoteostaja as $tuoteostajax) {
			if (trim($tuoteostajax) != '') {
				$tuoteostajax = trim(mysql_real_escape_string($tuoteostajax));
				$tuoteostajat .= "'$tuoteostajax',";
				$ulisa .= "&mul_tuoteostaja[]=".rawurlencode($tuoteostajax);
			}
		}

		$tuoteostajat = substr($tuoteostajat, 0, -1);
		
		if (trim($tuoteostajat) != '') {
			$lisa_haku_ostaja = " and kuka.myyja in ($tuoteostajat) ";
			$lisa .= " and $mulselprefix.ostajanro in ($tuoteostajat) ";
		}
	}
		
	if ($monivalintarajaus != "") {
		$monivalintarajaus_osasto = " and avainsana.selite in ($monivalintarajaus)";
		
		if (trim($lisa_haku_osasto) == '') {
			$lisa_haku_osasto = " and tuote.osasto in ($monivalintarajaus)";
			$lisa .= " and $mulselprefix.osasto in ($monivalintarajaus)";
			
			foreach (explode(",", $monivalintarajaus) as $monival) {
				$ulisa .= "&mul_osasto[]=".urlencode(str_replace("'", "", $osastot));
			}
		}
	}
	else {
		$monivalintarajaus_osasto = "";
	}

	if (in_array("OSASTO", $monivalintalaatikot)) {
		if (isset($monivalinta_tuotteet)) {
			$query = "	SELECT DISTINCT avainsana.selite,
			            IFNULL((SELECT avainsana_kieli.selitetark
			            FROM avainsana as avainsana_kieli
			            WHERE avainsana_kieli.yhtio = avainsana.yhtio
			            and avainsana_kieli.laji = avainsana.laji
			            and avainsana_kieli.perhe = avainsana.perhe
			            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark,						
						sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko						
						FROM tuote
						JOIN avainsana ON (avainsana.yhtio = tuote.yhtio AND avainsana.selite = tuote.osasto AND avainsana.laji = 'OSASTO' AND avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						$kieltolisa
						$extra_poislisa
						$poislisa_mulsel
						AND tuote.tuoteno in ($monivalinta_tuotteet)
						GROUP BY 1,2
						HAVING naytetaanko > 0
						ORDER BY avainsana.selitetark";
		}
		else {
			$query = "	SELECT DISTINCT avainsana.selite,
			            IFNULL((SELECT avainsana_kieli.selitetark
			            FROM avainsana as avainsana_kieli
			            WHERE avainsana_kieli.yhtio = avainsana.yhtio
			            and avainsana_kieli.laji = avainsana.laji
			            and avainsana_kieli.perhe = avainsana.perhe
			            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
			            FROM avainsana
			            WHERE avainsana.yhtio = '$kukarow[yhtio]'
			            and avainsana.laji = 'OSASTO'
			            and avainsana.kieli in ('$yhtiorow[kieli]', '')
						$monivalintarajaus_osasto
			            $avainlisa
			            $orderlisa";
		}
		
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<table style='display:inline;'>";
		echo "<tr><th>",t("Osasto"),"</th></tr>";
		echo "<tr><td nowrap valign='top' class='back'><select name='mul_osasto[]' multiple size='7' onchange='submit();'>";
		$mul_check = "";
		if ($mul_try!="") {
			if (in_array("PUPEKAIKKIMUUT", $mul_osasto)) {
				$mul_check = 'SELECTED';
			}
		}
	
		echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N�yt� kaikki")."</option>";
		echo "<option value=''>".t("Ei valintaa")."</option>";

		while($sxrow = mysql_fetch_array ($sresult)){
			$sel = '';

			if (count($mul_osasto) > 0) {
				if (in_array(trim($sxrow['selite']), $mul_osasto)) {
					$sel = 'SELECTED';
				}
			}
	
			echo "<option value='$sxrow[selite]' $sel>";
			if ($yhtiorow['naytetaan_kaunis_os_try'] == '') {
				echo $sxrow['selite']." ";
			}
			echo "$sxrow[selitetark]</option>";
		}
		echo "</select></td>";
		echo "</tr></table>";
	}
	
	if (in_array("TRY", $monivalintalaatikot)) {
		
		if (isset($monivalinta_tuotteet)) {
			$query = "	SELECT distinct avainsana.selite,
						IFNULL((SELECT avainsana_kieli.selitetark
				        FROM avainsana as avainsana_kieli
				        WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        and avainsana_kieli.laji = avainsana.laji
				        and avainsana_kieli.perhe = avainsana.perhe
				        and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark,
						sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko
						FROM tuote
						JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
						WHERE tuote.yhtio = '$kukarow[yhtio]'						
						$lisa_haku_osasto
						$kieltolisa
						$extra_poislisa
						$poislisa_mulsel
						AND tuote.tuoteno in ($monivalinta_tuotteet)
						GROUP BY 1,2
						HAVING naytetaanko > 0
						ORDER BY avainsana.selitetark";
		}
		elseif ($lisa_haku_osasto == "") {
			$query = "	SELECT DISTINCT avainsana.selite,
			            IFNULL((SELECT avainsana_kieli.selitetark
			            FROM avainsana as avainsana_kieli
			            WHERE avainsana_kieli.yhtio = avainsana.yhtio
			            and avainsana_kieli.laji = avainsana.laji
			            and avainsana_kieli.perhe = avainsana.perhe
			            and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
			            FROM avainsana
			            WHERE avainsana.yhtio = '$kukarow[yhtio]'
			            and avainsana.laji = 'TRY'
			            and avainsana.kieli in ('$yhtiorow[kieli]', '')
			            $avainlisa
			            $orderlisa";
		}
		else {
			$query = "	SELECT distinct avainsana.selite,
						IFNULL((SELECT avainsana_kieli.selitetark
				        FROM avainsana as avainsana_kieli
				        WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        and avainsana_kieli.laji = avainsana.laji
				        and avainsana_kieli.perhe = avainsana.perhe
				        and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
						FROM tuote
						JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						$lisa_haku_osasto
						$kieltolisa
						$extra_poislisa
						$poislisa_mulsel
						$orderlisa";
		}
	
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<table style='display:inline;'><tr><th>",t("Tuoteryhm�"),"</th></tr>";
		echo "<tr><td nowrap valign='top' class='back'><select name='mul_try[]' onchange='submit();' multiple='TRUE' size='7'>";
		$mul_check = '';
		if ($mul_try!="") {
			if (in_array("PUPEKAIKKIMUUT", $mul_try)) {
				$mul_check = 'SELECTED';
			}
		}
		echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N�yt� kaikki")."</option>";
		echo "<option value=''>".t("Ei valintaa")."</option>";

		while($srow = mysql_fetch_array ($sresult)){
			$sel = '';

			if (count($mul_try) > 0 and in_array(trim($srow['selite']), $mul_try)) {
				$sel = 'SELECTED';
			}

			echo "<option value='$srow[selite]' $sel>";
			if ($yhtiorow['naytetaan_kaunis_os_try'] == '') {
				echo $srow['selite']." ";
			}
			echo "$srow[selitetark]</option>";
		}
		echo "</select></td>";
		echo "</tr></table>";
	}
	
	if (in_array("TUOTEMERKKI", $monivalintalaatikot)) {

		if (isset($monivalinta_tuotteet)) {
			$query = "	SELECT avainsana.selite as tme, IFNULL((SELECT avainsana_kieli.selite
	        			FROM avainsana as avainsana_kieli
	        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
	        			and avainsana_kieli.laji = avainsana.laji
	        			and avainsana_kieli.perhe = avainsana.perhe
	        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite,
						sum(if(tuote.status not in ('P','X'), 1, (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0))) naytetaanko						
						FROM tuote
						JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI' and avainsana.kieli in ('$yhtiorow[kieli]', '') $avainlisa)
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						$lisa_haku_osasto
						$lisa_haku_try
						$kieltolisa
						$extra_poislisa
						$poislisa_mulsel
						AND tuote.tuoteno in ($monivalinta_tuotteet)
						GROUP BY 1,2
						HAVING naytetaanko > 0
						ORDER BY avainsana.selite";
		}		
		elseif ($lisa_haku_osasto == "" and $lisa_haku_try == "" and $kukarow['yhtio'] != 'artr') {
			$query = "	SELECT avainsana.selite as tme, IFNULL((SELECT avainsana_kieli.selite
	        			FROM avainsana as avainsana_kieli
	        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
	        			and avainsana_kieli.laji = avainsana.laji
	        			and avainsana_kieli.perhe = avainsana.perhe
	        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite		         
			            FROM avainsana				
			            WHERE avainsana.yhtio 	= '$kukarow[yhtio]'
			            and avainsana.laji 		= 'TUOTEMERKKI'
			            $avainlisa
			            ORDER BY avainsana.jarjestys, avainsana.selite";
			$tmsresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($lisa_haku_osasto != "" or $lisa_haku_try != "") {
			$query = "	SELECT distinct avainsana.selite as tme, IFNULL((SELECT avainsana_kieli.selite
	        			FROM avainsana as avainsana_kieli
	        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
	        			and avainsana_kieli.laji = avainsana.laji
	        			and avainsana_kieli.perhe = avainsana.perhe
	        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite
						FROM tuote
						JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI' $avainlisa)
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						$lisa_haku_osasto
						$lisa_haku_try
						$kieltolisa
						$extra_poislisa
						$poislisa_mulsel
						ORDER BY avainsana.jarjestys, avainsana.selite";		
			$tmsresult = mysql_query($query) or pupe_error($query);
		}

		if (is_resource($tmsresult) and mysql_num_rows($tmsresult) > 0) {
			echo "<table style='display:inline;'><tr><th>",t("Tuotemerkki"),"</th></tr>";
			echo "<tr><td nowrap valign='top' class='back'>";
			echo "<select name='mul_tme[]' multiple='TRUE' size='7' onchange='submit();'>";
			$mul_check = '';
			if ($mul_tme!="") {
				if (in_array("PUPEKAIKKIMUUT", $mul_tme)) {
					$mul_check = 'SELECTED';
				}
			}
			echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N�yt� kaikki")."</option>";
			echo "<option value=''>",t("Ei valintaa"),"</option>";

			while($srow = mysql_fetch_array ($tmsresult)){
				$sel = '';

				if (count($mul_tme) > 0 and in_array(trim($srow['tme']), $mul_tme)) {
					$sel = 'SELECTED';
				}

				echo "<option value='$srow[tme]' $sel>$srow[selite]</option>";
			}

			echo "</select></td>";
			echo "</tr></table>";
		}
	}
	
	if (in_array("MALLI/MALLITARK", $monivalintalaatikot)) {
		if (($lisa_haku_osasto != "" and $lisa_haku_try != "") or ($lisa_haku_osasto != "" and in_array("PUPEKAIKKIMUUT", $mul_try))) {
		
			echo "<br>".t("Lis�valinnat").":<br>";
			echo "* ".t("Mik�li haluat t�sment�� hakua, k�yt� lis�ksi alla olevia ryhmi�")."<br>";

			if ($lisa_haku_tme != '' or  $lisa_haku_try != '') {
				// malli ja mallitarkenne dropdownit
				$query = "	SELECT DISTINCT tuote.malli
							FROM tuote
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							and tuote.malli != ''
							$lisa_haku_osasto
							$lisa_haku_try
							$lisa_haku_tme
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							ORDER BY malli";
				$sxresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sxresult) > 0) {
					echo "<table style='display:inline;'><tr><th>",t("Malli"),"</th></tr>";
					echo "<tr><td nowrap valign='top' class='back'>";
					echo "<select name='mul_malli[]' multiple='TRUE' size='7' onchange='submit();'>";
					$mul_check = '';
					if ($mul_malli!="") {
						if (in_array("PUPEKAIKKIMUUT", $mul_malli)) {
							$mul_check = 'SELECTED';
						}
					}
					echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N�yt� kaikki")."</option>";
					echo "<option value=''>",t("Ei valintaa"),"</option>";

					while($mallirow = mysql_fetch_array ($sxresult)){
						$sel = '';

						if (count($mul_malli) > 0 and in_array(trim($mallirow['malli']), $mul_malli)) {
							$sel = 'SELECTED';
						}

						echo "<option value='$mallirow[malli]' $sel>$mallirow[malli]</option>";
					}

					echo "</select>";
					echo "</td>";
					echo "</tr></table>";
				}
			}

			if ($lisa_haku_malli != '') {
				$query = "	SELECT DISTINCT tuote.mallitarkenne
							FROM tuote
							WHERE tuote.yhtio = '$kukarow[yhtio]'
							and tuote.mallitarkenne != ''
							$lisa_haku_osasto
							$lisa_haku_try
							$lisa_haku_tme
							$lisa_haku_malli
							$kieltolisa
							$extra_poislisa
							$poislisa_mulsel
							ORDER BY mallitarkenne";
				$sxresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($sxresult) > 0) {
					echo "<table style='display:inline;'><tr><th>",t("Mallitarkenne"),"</th></tr>";
					echo "<tr><td nowrap valign='top' class='back'>";
					echo "<select name='mul_mallitarkenne[]' multiple='TRUE' size='7' onchange='submit();'>";
					$mul_check = '';
					if ($mul_mallitarkenne!="") {
						if (in_array("PUPEKAIKKIMUUT", $mul_mallitarkenne)) {
							$mul_check = 'SELECTED';
						}
					}
					echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("N�yt� kaikki")."</option>";
					echo "<option value=''>",t("Ei valintaa"),"</option>";

					while($mallitarkennerow = mysql_fetch_array ($sxresult)){
						$sel = '';

						if (count($mul_mallitarkenne) > 0 and in_array(trim($mallitarkennerow['mallitarkenne']), $mul_mallitarkenne)) {
							$sel = 'SELECTED';
						}

						echo "<option value='$mallitarkennerow[mallitarkenne]' $sel>$mallitarkennerow[mallitarkenne]</option>";
					}

					echo "</select>";
					echo "</td>";
					echo "</tr></table>";
				}
			}
		}
	}

	if (in_array("TUOTEMYYJA", $monivalintalaatikot)) {
		echo "<table style='display:inline;'>";
		echo "<tr><th>",t("Tuotemyyj�"),"</th></tr>";
		echo "<tr>";

		// tehd��n query
		$query = "	SELECT DISTINCT myyja, nimi 
					FROM kuka 
					WHERE yhtio = '$kukarow[yhtio]' 
					AND myyja>0
					ORDER BY myyja";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<td nowrap valign='top' class='back'><select name='mul_tuotemyyja[]' multiple size='7' onchange='submit();'>";
		echo "<option value=''>".t("Ei valintaa")."</option>";

		while($sxrow = mysql_fetch_array ($sresult)){
			$sel = '';

			if (count($mul_tuotemyyja) > 0) {
				if (in_array(trim($sxrow['myyja']), $mul_tuotemyyja)) {
					$sel = 'SELECTED';
				}
			}
	
			echo "<option value='$sxrow[myyja]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
		}
		echo "</select></td>";
		echo "</tr></table>";
	}
	
	if (in_array("TUOTEOSTAJA", $monivalintalaatikot)) {
		echo "<table style='display:inline;'>";
		echo "<tr><th>",t("Tuoteostaja"),"</th></tr>";
		echo "<tr>";

		$query = "	SELECT distinct myyja, nimi 
					FROM kuka 
					WHERE yhtio='$kukarow[yhtio]' 
					AND myyja>0
					ORDER BY myyja";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<td nowrap valign='top' class='back'><select name='mul_tuoteostaja[]' multiple size='7' onchange='submit();'>";
		echo "<option value=''>".t("Ei valintaa")."</option>";

		while($sxrow = mysql_fetch_array ($sresult)){
			$sel = '';

			if (count($mul_tuoteostaja) > 0) {
				if (in_array(trim($sxrow['myyja']), $mul_tuoteostaja)) {
					$sel = 'SELECTED';
				}
			}
	
			echo "<option value='$sxrow[myyja]' $sel>$sxrow[myyja] $sxrow[nimi]</option>";
		}
		echo "</select></td>";
		echo "</tr></table>";
	}

?>