<?php

// k�ytet��n slavea jos sellanen on
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Varastonarvo tuotteittain")."</font><hr>";

if (!isset($pp)) $pp = date("d");
if (!isset($kk)) $kk = date("m");
if (!isset($vv)) $vv = date("Y");

// tutkaillaan saadut muuttujat
$osasto = trim($osasto);
$try    = trim($try);
$pp 	= sprintf("%02d", trim($pp));
$kk 	= sprintf("%02d", trim($kk));
$vv 	= sprintf("%04d", trim($vv));

// h�rski oikeellisuustzekki
if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";

// n�it� k�ytet��n queryss�
$sel_osasto = "";
$sel_tuoteryhma = "";

echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
	<!--

	function toggleAll(toggleBox) {

		var currForm = toggleBox.form;
		var isChecked = toggleBox.checked;
		var nimi = toggleBox.name;

		for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
			if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi) {
				currForm.elements[elementIdx].checked = isChecked;
			}
		}
	}

	//-->
	</script>";

// piirrell��n formi
echo "<form action='$PHP_SELF' name='formi' method='post' autocomplete='OFF'>";

if ($valitaan_useita == "") {
	
	echo "<table>";
	
	// n�ytet��n soveltuvat osastot
	$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' order by selite+0";
	$res2  = mysql_query($query) or die($query);

	$sel = "";
	if ($osasto == "kaikki") $sel = "selected";
	
	echo "<tr><th>Osasto:</th><td>";
	echo "<select name='osasto'>";
	echo "<option value=''>Valitse osasto</option>";
	echo "<option value='kaikki' $sel>N�yt� kaikki</option>";

	while ($rivi = mysql_fetch_array($res2)) {
		$sel = "";
		if ($osasto == $rivi["selite"]) {
			$sel = "selected";
			$sel_osasto = $rivi["selite"];
		}
		echo "<option value='$rivi[selite]' $sel>$rivi[selite] - $rivi[selitetark]</option>";
	}

	echo "</select></td></tr>";

	$trylisa = "";
	$sort_osastot = "";
	if ($osasto != "kaikki" and $sel_osasto != "") {
		$trylisa = " and osasto='$sel_osasto' ";
		$sort_osastot = "&osasto=$sel_osasto";
	}

	// n�ytet��n soveltuvat tuoteryhm�t
	$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='TRY' order by selite+0";
	$res2   = mysql_query($query) or die($query);

	echo "<tr><th>Tuoteryhm�:</th><td>";
	echo "<select name='tuoteryhma'>";
	echo "<option value=''>Valitse tuoteryhm�</option>";

	$sel = "";
	if ($tuoteryhma == "kaikki") $sel = "selected";
	echo "<option value='kaikki' $sel>N�yt� kaikki</option>";

	while ($rivi = mysql_fetch_array($res2)) {
		$sel = "";
		if ($tuoteryhma == $rivi["selite"]) {
			$sel = "selected";
			$sel_tuoteryhma = $rivi["selite"];
		}

		echo "<option value='$rivi[selite]' $sel>$rivi[selite] - $rivi[selitetark]</option>";
	}

	echo "</select></td>";

	$sort_tryt = "";
	if ($tuoteryhma != "kaikki" and $sel_tuoteryhma != "") {
		$sort_tryt = "&tuoteryhma=$sel_tuoteryhma";
	}
	
	echo "<td class='back'><input type='submit' name='valitaan_useita' value='Valitse useita'></td></tr>";
	echo "</table>";
}
else {
	if ($mul_osasto == "") {

		echo "<table><tr><td valign='top' class='back'>";
		
		echo "<div style='height:265;overflow:auto;'>";
		echo "<table>";
		echo "<tr><th colspan='2'>Valitse osasto(t):</th></tr>";

		// n�ytet��n soveltuvat osastot
		$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' order by selite+0";
		$res2  = mysql_query($query) or die($query);
		
		echo "<tr><td><input type='checkbox' name='mul_osa' onclick='toggleAll(this);'></td><td>Ruksaa kaikki</td></tr>";
		
		
		while ($rivi = mysql_fetch_array($res2)) {
			echo "<tr><td><input type='checkbox' name='mul_osasto[]' value='$rivi[selite]'></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
		}

		echo "</table></div><br>";
		echo "<input type='submit' name='valitaan_useita' value='Jatka'>";

		echo "</td><td valign='top' class='back'><input type='submit' name='dummy' value='Valitse yksitt�in'></td></tr></table>";

	}
	elseif ($mul_try == "") {

		echo "<table><tr><td valign='top' class='back'>";
		
		echo "<div style='height:265;overflow:auto;'>";
		echo "<table>";
		echo "<tr><th>Osasto(t):</th></tr>";

		$osastot = "";
		foreach ($mul_osasto as $kala) {
			echo "<input type='hidden' name='mul_osasto[]' value='$kala'>";

			$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' and selite='$kala'";
			$res3   = mysql_query($query) or die($query);
			$selrow = mysql_fetch_array($res3);

			echo "<tr><td>$kala - $selrow[selitetark]</td></tr>";
			$osastot .= "'$kala',";
		}
		$osastot = substr($osastot,0,-1);

		echo "</table></div>";

		echo "</td><td valign='top' class='back'>";

		echo "<div style='height:265;overflow:auto;'>";
		echo "<table>";
		echo "<tr><th colspan='2'>Valitse tuoteryhm�(t):</th></tr>";

		// n�ytet��n soveltuvat osastot
		$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='TRY' order by selite+0";
		$res2  = mysql_query($query) or die($query);

		echo "<tr><td><input type='checkbox' name='mul_try' onclick='toggleAll(this);'></td><td>Ruksaa kaikki</td></tr>";
		
		while ($rivi = mysql_fetch_array($res2)) {
			echo "<tr><td><input type='checkbox' name='mul_try[]' value='$rivi[selite]'></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
		}

		echo "</table></div><br>";
		echo "<input type='submit' name='valitaan_useita' value='Jatka'>";

		echo "</td><td valign='top' class='back'><input type='submit' name='dummy' value='Valitse yksitt�in'></td></tr></table>";


	}
	else {

		echo "<table><tr><td valign='top' class='back'>";

		echo "<div style='height:265;overflow:auto;'>";
		echo "<table>";
		echo "<tr><th>Osasto(t):</th></tr>";

		$osastot = "";
		$sort_osastot = "";

		foreach ($mul_osasto as $kala) {
			echo "<input type='hidden' name='mul_osasto[]' value='$kala'>";
			
			$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO' and selite='$kala'";
			$res3   = mysql_query($query) or die($query);
			$selrow = mysql_fetch_array($res3);

			echo "<tr><td>$kala - $selrow[selitetark]</td></tr>";
			$osastot .= "'$kala',";
			$sort_osastot .= "&mul_osasto[]=$kala";
		}
		$osastot = substr($osastot,0,-2); // vika pilkku ja vika hipsu pois
		$osastot = substr($osastot, 1);   // eka hipsu pois

		echo "</table></div>";

		echo "</td><td valign='top' class='back'>";

		echo "<div style='height:265;overflow:auto;'>";
		echo "<table>";
		echo "<tr><th colspan='2'>Tuoteryhm�(t):</th></tr>";

		$tryt = "";
		$sort_tryt = "";

		foreach ($mul_try as $kala) {
			echo "<input type='hidden' name='mul_try[]' value='$kala'>";
			
			$query = "SELECT * FROM avainsana WHERE yhtio='$kukarow[yhtio]' and laji='TRY' and selite='$kala'";
			$res3   = mysql_query($query) or die($query);
			$selrow = mysql_fetch_array($res3);

			echo "<tr><td>$kala - $selrow[selitetark]</td></tr>";
			$tryt .= "'$kala',";
			$sort_tryt .= "&mul_try[]=$kala";
		}
		$tryt = substr($tryt,0,-2);  // vika pilkku ja vika hipsu pois
		$tryt = substr($tryt, 1);    // eka hipsu pois

		$sel_osasto = $osastot;
		$sel_tuoteryhma = $tryt;

		echo "</table></div><br>";

		echo "</td><td valign='top' class='back'>";

		echo "<input type='submit' name='valitaan_useita' value='Valitse useita'>";
		echo "<input type='submit' name='dummy' value='Valitse yksitt�in'>";

		echo "</td></tr></table>";
	}
}

echo "<table>";
echo "<tr>";
echo "<th>Sy�t� vvvv-kk-pp:</th>";
echo "<td colspan='2'><input type='text' name='vv' size='7' value='$vv'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='pp' size='5' value='$pp'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>N�ytet��nk� tuotteet:</th>";

if ($naytarivit != '') {
	$chk = "CHECKED";
}
else {
	$chk = "";
}

echo "<td colspan='2'><input type='checkbox' name='naytarivit' $chk> (Listaus l�hetet��n s�hk�postiisi)</td>";
echo "</tr>";
echo "</table>";
echo "<br>";

if($valitaan_useita == '') {
	echo "<input type='submit' value='Laske varastonarvot'>";
}
else {
	echo "<input type='submit' name='valitaan_useita' value='Laske varastonarvot'>";
}

echo "</form>";


if ($sel_tuoteryhma != "" or $sel_osasto != "" or $osasto == "kaikki" or $tuoteryhma == "kaikki") {

	$trylisa = "";

	if ($tuoteryhma != "kaikki" and $sel_tuoteryhma != "") {
		$trylisa .= " and tuote.try in ('$sel_tuoteryhma') ";
	}
	if ($osasto != "kaikki" and $sel_osasto != "") {
		$trylisa .= " and tuote.osasto in ('$sel_osasto') ";
	}

	// haetaan halutut tuotteet
	$query  = "	SELECT tuoteno, osasto, try, nimitys, kehahin, epakurantti1pvm, epakurantti2pvm, sarjanumeroseuranta
				FROM tuote
				WHERE yhtio = '$kukarow[yhtio]'
				and ei_saldoa = ''
				$trylisa
				ORDER BY osasto, try, tuoteno";
	$result = mysql_query($query) or pupe_error($query);
	echo "<font class='message'>".t("L�ytyi"). " ";
	flush();
	echo mysql_num_rows($result)." ".t("tuotetta")."...</font><br><br>";
	flush();

	$varvo = 0; // t�h�n summaillaan

	if ($naytarivit != "") {
		$ulos  = "osasto\t";
		$ulos .= "try\t";
		$ulos .= "tuoteno\t";
		$ulos .= "nimitys\t";
		$ulos .= "saldo\t";
		$ulos .= "kehahin\t";
		$ulos .= "vararvo\n";
	}

	while ($row = mysql_fetch_array($result)) {

	   // tuotteen m��r� varastossa nyt
	   $query = "	SELECT sum(saldo) varasto
		   			FROM tuotepaikat use index (tuote_index)
		   			WHERE yhtio = '$kukarow[yhtio]'
		   			and tuoteno = '$row[tuoteno]'";
		$vres = mysql_query($query) or pupe_error($query);
		$vrow = mysql_fetch_array($vres);
		
		$kehahin = 0;
		
		//Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksil�iden ostohinnoista (ostetut yksil�t jotka eiv�t viel� ole myyty(=laskutettu))
		if ($row["sarjanumeroseuranta"] != '') {
			$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
						FROM sarjanumeroseuranta
						LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
						LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
						LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
						LEFT JOIN lasku lasku_osto   use index (PRIMARY) ON lasku_osto.yhtio=sarjanumeroseuranta.yhtio and lasku_osto.tunnus=tilausrivi_osto.uusiotunnus
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
						and (tilausrivi_myynti.tunnus is null or (lasku_myynti.tila in ('N','L') and lasku_myynti.alatila != 'X'))
						and (lasku_osto.tila='U' or (lasku_osto.tila='K' and lasku_osto.alatila='X'))";
			$sarjares = mysql_query($query) or pupe_error($query);
			$sarjarow = mysql_fetch_array($sarjares);
						
			$kehahin = sprintf('%.2f', $sarjarow["kehahin"]);
		}
		else {
			$kehahin = sprintf('%.2f', $row["kehahin"]);
		}
		
		// tuotteen muutos varastossa annetun p�iv�n j�lkeen
		$query = "	SELECT sum(kpl * if(laji in ('tulo','valmistus'), kplhinta, hinta)) muutoshinta, sum(kpl) muutoskpl
		 			FROM tapahtuma use index (yhtio_tuote_laadittu)
		 			WHERE yhtio = '$kukarow[yhtio]'
		 			and tuoteno = '$row[tuoteno]'
		 			and laadittu > '$vv-$kk-$pp 23:59:59'";
		$mres = mysql_query($query) or pupe_error($query);
		$mrow = mysql_fetch_array($mres);

		// katotaan onko tuote ep�kurantti nyt
		$kerroin = 1;
		if ($row['epakurantti1pvm'] != '0000-00-00') {
			$kerroin = 0.5;
		}
		if ($row['epakurantti2pvm'] != '0000-00-00') {
			$kerroin = 0;
		}

		// arvo historiassa: lasketaan (nykyinen varastonarvo) - muutoshinta
		$muutoshinta = ($vrow["varasto"] * $kehahin * $kerroin) - $mrow["muutoshinta"];

		// saldo historiassa: lasketaan nykyiset kpl - muutoskpl
		$muutoskpl = $vrow["varasto"] - $mrow["muutoskpl"];

		// summataan varastonarvoa
		$varvo += $muutoshinta;

		if ($naytarivit != "" and $muutoskpl != 0) {

			// yritet��n kaivaa listaan viel� sen hetkinen kehahin jos se halutaan kerran n�hd�
			$kehasilloin = $kehahin * $kerroin; // nykyinen kehahin
			$kehalisa = "\t~"; // laitetaan about merkki failiin jos ei l�ydet� tapahtumista mit��

			// jos ollaan annettu t�m� p�iv� niin ei ajeta t�t� , koska nykyinen kehahin on oikein ja n�in on nopeempaa! wheee!
			if ($pp != date("d") or $kk != date("m") or $vv != date("Y")) {
				// katotaan mik� oli tuotteen viimeisin hinta annettuna p�iv�n� tai sitten sit� ennen
				$query = "	SELECT hinta
							FROM tapahtuma use index (yhtio_tuote_laadittu)
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and laadittu <= '$vv-$kk-$pp 23:59:59'
							and hinta <> 0
							ORDER BY laadittu desc
							LIMIT 1";
				$ares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($ares) == 1) {
					// l�ydettiin keskihankintahinta tapahtumista k�ytet��n
					$arow = mysql_fetch_array($ares);
					$kehasilloin = $arow["hinta"];
					$kehalisa = "";
				}
				else {
					// ei l�ydetty alasp�in, kokeillaan kattoo l�hin hinta yl�sp�in
					$query = "	SELECT hinta
								FROM tapahtuma use index (yhtio_tuote_laadittu)
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and laadittu >= '$vv-$kk-$pp 23:59:59'
								and hinta <> 0
								ORDER BY laadittu
								LIMIT 1";
					$ares = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($ares) == 1) {
						// l�ydettiin keskihankintahinta tapahtumista k�ytet��n
						$arow = mysql_fetch_array($ares);
						$kehasilloin = $arow["hinta"];
						$kehalisa = "\t!";
					}
				}
			}

   			$ulos .= "$row[osasto]\t";
   			$ulos .= "$row[try]\t";
   			$ulos .= "$row[tuoteno]\t";
   			$ulos .= "$row[nimitys]\t";
   			$ulos .= str_replace(".",",",$muutoskpl)."\t";
   			$ulos .= str_replace(".",",",$kehasilloin)."\t";
   			$ulos .= str_replace(".",",",$muutoshinta)."$kehalisa\n";
		}

	} // end while

	if ($naytarivit != "") {

		// l�hetet��n meili
		$bound = uniqid(time()."_") ;

		$header  = "From: <$yhtiorow[postittaja_email]>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content = "--$bound\n";

		$content .= "Content-Type: text/x-comma-separated-values; name=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\n\n";

		$content .= chunk_split(base64_encode($ulos));
		$content .= "\n" ;

		$content .= "--$bound\n";

		$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Varastonarvo"), $content, $header, "-f $yhtiorow[postittaja_email]");

		echo "<font class='message'>".t("L�hetet��n s�hk�posti");
		if ($boob === FALSE) echo " - ".t("Email l�hetys ep�onnistui!")."<br>";
		else echo " $kukarow[eposti].<br>";
		echo "</font><br>";
	}

	echo "<table>";
	echo "<tr><th>Pvm</th><th>Varastonarvo</th></tr>";
	echo "<tr><td>$vv-$kk-$pp</td><td align='right'>".sprintf("%.2f",$varvo)."</td></tr>";
	echo "</table>";

}

require ("../inc/footer.inc");

?>
