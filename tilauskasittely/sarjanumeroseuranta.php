<?php

	// otetaan sis��n voidaan ottaa $myyntirivitunnus tai $ostorivitunnus
	// ja $from niin tiedet��n mist� tullaan ja minne palata

	if (strpos($_SERVER['SCRIPT_NAME'], "sarjanumeroseuranta.php")  !== FALSE) {
		require("../inc/parametrit.inc");
	}

	echo "<font class='head'>".t("Sarjanumeroseuranta")."</font><hr>";

	$tunnuskentta 	= "";
	$rivitunnus 	= "";
	$hyvitysrivi 	= "";

	if ($myyntirivitunnus != "") {
		$tunnuskentta 	= "myyntirivitunnus";
		$rivitunnus 	= $myyntirivitunnus;
	}

	if ($ostorivitunnus != "") {
		$tunnuskentta 	= "ostorivitunnus";
		$rivitunnus	 	= $ostorivitunnus;
	}

	// haetaan tilausrivin tiedot
	if ($from != '' and $rivitunnus != "") {
		$query    = "	SELECT *
						FROM tilausrivi
						WHERE yhtio='$kukarow[yhtio]'
						and tunnus='$rivitunnus'";
		$sarjares = mysql_query($query) or pupe_error($query);
		$rivirow  = mysql_fetch_array($sarjares);

		$query    = "	SELECT *
						FROM lasku
						WHERE yhtio='$kukarow[yhtio]'
						and tunnus='$rivirow[otunnus]'";
		$sarjares = mysql_query($query) or pupe_error($query);
		$laskurow  = mysql_fetch_array($sarjares);

		//Jotta jt:tkin toimisi
		$rivirow["varattu"] = $rivirow["varattu"] + $rivirow["jt"];

		// jos varattu on nollaa ja kpl ei niin otetaan kpl (esim varastoon viedyt ostotilausrivit)
		if ($rivirow["varattu"] == 0 and $rivirow["kpl"] != 0) {
			$rivirow["varattu"] = $rivirow["kpl"];
		}

		// t�ss� muutetaan myyntirivitunnus ostorivitunnukseksi jos $rivirow["varattu"] eli kappalem��r� on negatiivinen
		if ($rivirow["varattu"] < 0 and $tunnuskentta = "myyntirivitunnus") {
			$tunnuskentta 		= "ostorivitunnus";
			$rivirow["varattu"] = abs($rivirow["varattu"]);
			$hyvitysrivi 		= "ON";
		}

		/*
		// Katsotaan onko sarjanumerot viel� k�yt�ss�, tilausrivi on voitu poistaa
		$query = "	SELECT sarjanumeroseuranta.tunnus sarjatunnus, tilausrivi.tunnus rivitunnus
					FROM sarjanumeroseuranta
					LEFT JOIN tilausrivi ON sarjanumeroseuranta.yhtio=tilausrivi.yhtio and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus and tilausrivi.tyyppi!='D'
					WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]'
					and sarjanumeroseuranta.tuoteno='$rivirow[tuoteno]'
					HAVING tilausrivi.tunnus is null";
		$sres = mysql_query($query) or pupe_error($query);

		while($srow = mysql_fetch_array($sres)) {
			$query = "update sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tunnus='$srow[sarjatunnus]'";
			$sarjares = mysql_query($query) or pupe_error($query);
		}

		// Katsotaan onko sarjanumerot viel� k�yt�ss�, tilausrivi on voitu poistaa
		$query = "	SELECT sarjanumeroseuranta.tunnus sarjatunnus, tilausrivi.tunnus rivitunnus
					FROM sarjanumeroseuranta
					LEFT JOIN tilausrivi ON sarjanumeroseuranta.yhtio=tilausrivi.yhtio and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus and tilausrivi.tyyppi!='D'
					WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]'
					and sarjanumeroseuranta.tuoteno='$rivirow[tuoteno]'
					HAVING tilausrivi.tunnus is null";
		$sres = mysql_query($query) or pupe_error($query);

		while($srow = mysql_fetch_array($sres)) {
			$query = "update sarjanumeroseuranta set ostorivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and tunnus='$srow[sarjatunnus]'";
			$sarjares = mysql_query($query) or pupe_error($query);
		}
		*/
	}

	//ollaan poistamassa sarjanumero-olio kokonaan
	if ($toiminto == 'POISTA') {
		$query = "	DELETE
					FROM sarjanumeroseuranta
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$sarjatunnus'
					and myyntirivitunnus=0
					and ostorivitunnus=0";
		$dellares = mysql_query($query) or pupe_error($query);

		$sarjanumero	= "";
		$lisatieto		= "";
		$sarjatunnus	= "";
		$toiminto		= "";

		echo "<font class='message'>".t("Sarjanumero poistettu")."!</font><br><br>";
	}

	//halutaan muuttaa sarjanumeron tietoja
	if ($toiminto == 'MUOKKAA') {
		if (isset($PAIVITA)) {
			$query = "	UPDATE sarjanumeroseuranta
						SET lisatieto = '$lisatieto',
						sarjanumero = '$sarjanumero'
						WHERE yhtio = '$kukarow[yhtio]' and tunnus='$sarjatunnus'";
			$sarjares = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t("P�vitettiin sarjanumeron tiedot")."!</font><br><br>";

			$sarjanumero	= "";
			$lisatieto		= "";
			$sarjatunnus	= "";
			$toiminto		= "";
		}
		else {
			$query = "	SELECT sarjanumeroseuranta.* , tuote.tuoteno, tuote.nimitys
						FROM sarjanumeroseuranta
						LEFT JOIN tuote ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
						WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]'
						and sarjanumeroseuranta.tunnus='$sarjatunnus'";
			$muutares = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($muutares) == 1) {

				$muutarow = mysql_fetch_array($muutares);

				echo "<table>";
				echo "<tr><th colspan='2'>".t("Muuta sarjanumerotietoja").":</th></tr>";
				echo "<tr><th>".t("Tuotenumero")."</th><td>$muutarow[tuoteno] $muutarow[nimitys]</td></tr>";

				echo "	<form action='$PHP_SELF' method='post'>
						<input type='hidden' name='muut_siirrettavat'	value='$muut_siirrettavat'>
						<input type='hidden' name='$tunnuskentta' 		value='$rivitunnus'>
						<input type='hidden' name='from' 				value='$from'>
						<input type='hidden' name='otunnus' 			value='$otunnus'>
						<input type='hidden' name='toiminto' 			value='MUOKKAA'>
						<input type='hidden' name='sarjatunnus' 		value='$sarjatunnus'>
						<input type='hidden' name='sarjanumero_haku' 	value='$sarjanumero_haku'>
						<input type='hidden' name='tuoteno_haku' 		value='$tuoteno_haku'>
						<input type='hidden' name='nimitys_haku' 		value='$nimitys_haku'>
						<input type='hidden' name='ostotilaus_haku' 	value='$ostotilaus_haku'
						<input type='hidden' name='myyntitilaus_haku'	value='$myyntitilaus_haku'>
						<input type='hidden' name='lisatieto_haku' 		value='$lisatieto_haku'>";

				echo "<tr><th>".t("Sarjanumero")."</th><td><input type='text' size='30' name='sarjanumero' value='$muutarow[sarjanumero]'></td></tr>";
				echo "<tr><th>".t("Lis�tieto")."</th><td><input type='text' size='30' name='lisatieto' value='$muutarow[lisatieto]'></td>";
				echo "<td class='back'><input type='submit' name='PAIVITA' value='".t("P�ivit�")."'></form></td>";
				echo "</tr></table><br><br>";

				if ($muutarow["perheid"] == $muutarow["tunnus"] or $muutarow["perheid"] == 0) {
					$voidaan_liittaa = "YES";
				}
				else {
					$voidaan_liittaa = "NO";
				}
			}
			else {
				echo t("Muutettava sarjanumero on kadonnut")."!!!!<br>";
			}
		}
	}

	// ollaan sy�tetty uusi
	if ($toiminto == 'LISAA' and trim($sarjanumero) != '') {

		$query = "	SELECT *
					FROM sarjanumeroseuranta
					WHERE yhtio = '$kukarow[yhtio]'
					and sarjanumero = '$sarjanumero'
					and tuoteno = '$rivirow[tuoteno]'
					and (ostorivitunnus=0 or myyntirivitunnus=0)";
		$sarjares = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($sarjares) == 0) {
			//jos ollaan sy�tetty kokonaan uusi sarjanuero
			$query = "insert into sarjanumeroseuranta (yhtio, tuoteno, sarjanumero, lisatieto, $tunnuskentta) VALUES ('$kukarow[yhtio]','$rivirow[tuoteno]','$sarjanumero','$lisatieto','')";
			$sarjares = mysql_query($query) or pupe_error($query);

			echo "<font class='message'>".t("Lis�ttiin sarjanumero")." $sarjanumero.</font><br><br>";

			$sarjanumero	= "";
			$lisatieto		= "";
		}
		else {
			$sarjarow = mysql_fetch_array($sarjares);
			echo "<font class='error'>".t("Sarjanumero l�ytyy jo tuotteelta")." $sarjarow[tuoteno]/$sarjanumero.</font><br><br>";
		}
	}

	// ollaan valittu joku tunnus listasta ja halutaan liitt�� se tilausriviin tai poistaa se tilausrivilt�
	if ($from != '' and $rivitunnus != "" and $formista == "kylla") {
		// jos olemme ruksanneet v�hemm�n tai yht�paljon kuin tuotteita on rivill�, voidaan p�ivitt�� muutokset
		foreach ($sarjat as $sarjatun) {
			$query = "	SELECT tunnus, perheid
						FROM sarjanumeroseuranta
						WHERE tunnus = '$sarjatun'";
			$sarres = mysql_query($query) or pupe_error($query);
			$sarrow = mysql_fetch_array($sarres);

			$query = "	update sarjanumeroseuranta
						set $tunnuskentta=''
						WHERE yhtio='$kukarow[yhtio]'
						and tunnus='$sarrow[tunnus]' or (perheid='$sarrow[perheid]' and perheid!=0)";
			$sarjares = mysql_query($query) or pupe_error($query);


		}

		if ($rivirow["varattu"] >= count($sarjataan)) {
			//jos mik��n ei ole ruksattu niin ei tietenk��n halutakkaan lis�t� mit��n sarjanumeroa
			if (count($sarjataan) > 0) {
				foreach ($sarjataan as $sarjatun) {

					if ($tunnuskentta == "ostorivitunnus") {
						//Hanskataan sarjanumeron varastopaikkaa
						$paikkalisa = "	,
										hyllyalue	= '$rivirow[hyllyalue]',
										hyllynro	= '$rivirow[hyllynro]',
										hyllyvali	= '$rivirow[hyllyvali]',
										hyllytaso	= '$rivirow[hyllytaso]'";
					}
					else {
						$paikkalisa = "";
					}

					$query = "	UPDATE sarjanumeroseuranta
								SET $tunnuskentta='$rivitunnus'
								$paikkalisa
								WHERE yhtio='$kukarow[yhtio]' and tunnus='$sarjatun'";
					$sarjares = mysql_query($query) or pupe_error($query);

					// Tutkitaan pitt��k� meid�n liitt�� muita tilausrivej� jos sarjanumerolla on perhe
					//Haetaan sarjanumeron ja siihen liitettyjen sarjanumeroiden kaikki tiedot.
					if ($tunnuskentta == 'myyntirivitunnus') {
						//$rivitunnus rikkoontuu lisaarivi.inciss�
						$rivitunnus_sarjans = $rivitunnus;
						$rivitunnus 		= "";

						$query = "	SELECT *
									FROM sarjanumeroseuranta
									WHERE tunnus = '$sarjatun' and perheid != 0";
						$sarres = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($sarres) > 0) {
							$sarrow = mysql_fetch_array($sarres);

							$query = "	SELECT tuoteno, tunnus
										FROM sarjanumeroseuranta
										WHERE yhtio = '$sarrow[yhtio]'
										and perheid = '$sarrow[perheid]'
										and tunnus != '$sarrow[tunnus]'";
							$sarres1 = mysql_query($query) or pupe_error($query);

							while($sarrow1 = mysql_fetch_array($sarres1)) {
								// Katsotaan onko tilauksella vapaata tilausrivi� jossa on t�m� tuote
								$query    = "	SELECT tilausrivi.tunnus ttunnus, sarjanumeroseuranta.tunnus stunnus
												FROM tilausrivi
												LEFT JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and sarjanumeroseuranta.tuoteno=tilausrivi.tuoteno and sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus
												WHERE tilausrivi.yhtio='$kukarow[yhtio]'
												and tilausrivi.tuoteno='$sarrow1[tuoteno]'
												and tilausrivi.otunnus='$kukarow[kesken]'
												HAVING stunnus is null
												LIMIT 1";
								$sressi = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($sressi) == 1) {
									//Vapaa tilausrivi l�ytyi liitet��n se t�h�n
									$srowwi = mysql_fetch_array($sressi);

									$query = "	UPDATE sarjanumeroseuranta
												SET $tunnuskentta='$srowwi[ttunnus]'
												WHERE yhtio='$kukarow[yhtio]'
												and tunnus='$sarrow1[tunnus]'";
									$sressi = mysql_query($query) or pupe_error($query);
								}
								else {
									// Vapaata tilausrivi� ei l�ytynyt, perustetaan uusi

									// haetaan tuotteen tiedot
									$query = "	select *
												from tuote
												where yhtio='$kukarow[yhtio]'
												and tuoteno='$sarrow1[tuoteno]'";
									$tuoteres = mysql_query($query);

									if (mysql_num_rows($tuoteres) == 0) {
										echo "<font class='error'>Tuotetta $sarrow1[tuoteno] ei l�ydy!</font><br>";
									}
									else {
										// tuote l�ytyi ok, lis�t��n rivi
										$trow = mysql_fetch_array($tuoteres);

										$ytunnus         = $laskurow["ytunnus"];
										$kpl             = 1.00;
										$tuoteno         = $sarrow1["tuoteno"];
										$toimaika 	     = $laskurow["toimaika"];
										$kerayspvm	     = $laskurow["kerayspvm"];
										$hinta 		     = "";
										$netto 		     = "";
										$ale 		     = "";
										$alv		     = "";
										$var			 = "";
										$varasto 	     = "";
										$rivitunnus		 = "";
										$korvaavakielto	 = "";
										$varataan_saldoa = "";
										$myy_sarjatunnus = $sarrow1["tunnus"];

										// jos meill� on ostoskori muuttujassa numero, niin halutaan lis�t� tuotteita siihen ostoskoriin
										if (is_numeric($ostoskori)) {
											lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
											$kukarow["kesken"] = "";
										}
										elseif (file_exists("../tilauskasittely/lisaarivi.inc")) {
											require ("../tilauskasittely/lisaarivi.inc");
										}
										else {
											require ("lisaarivi.inc");
										}

										echo "<font class='message'>Lis�ttiin $kpl kpl tuotetta $trow[tuoteno].</font><br>";

									} // tuote ok else
								}
							}
						}
						$rivitunnus 		= $rivitunnus_sarjans;
						$rivitunnus_sarjans = "";
					}
				}
			}
		}
		else {
			echo "<font class='error'>".sprintf(t('Riviin voi liitt�� enint��n %s sarjanumeroa'), abs($rivirow["varattu"])).".</font><br><br>";
		}
	}



	// poistetaan t�� perheid
	if (count($linkit) > 0) {
		foreach ($linkit as $link1) {
			$query = "	UPDATE sarjanumeroseuranta
						SET perheid  = ''
						WHERE yhtio  = '$kukarow[yhtio]'
						and perheid  = '$link1'";
			$sarjares = mysql_query($query) or pupe_error($query);
		}
	}

	if (count($linkataan) > 0 and $formista == "kylla") {
		foreach ($linkataan as $muuttuja) {

			list($link1, $link2) = explode('###', $muuttuja);

			$query = "	UPDATE sarjanumeroseuranta
						SET perheid = '$link1'
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus in ('$link1','$link2')";
			$sarjares = mysql_query($query) or pupe_error($query);
		}
	}

	if (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "KERAA") and $hyvitysrivi != "ON") {
		//Haetaan tuoteen tiedot
		$query  = "	SELECT *
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$rivirow[tuoteno]'";
		$tuoteres = mysql_query($query) or pupe_error($query);
		$tuoterow = mysql_fetch_array($tuoteres);

		//Jos tuote on marginaaliverotuksen alainen niin sen pit�� ollaa onnistuneesti ostettu jotta sen voi myyd�
		$mlisa = "";
		if($tuoterow["sarjanumeroseuranta"] == "M") {
			$mlisa = "	and sarjanumeroseuranta.ostorivitunnus != 0
						and tilausrivi.laskutettuaika > '0000-00-00' ";
		}

		$query    = "	SELECT sarjanumeroseuranta.*, tilausrivi.nimitys
						FROM sarjanumeroseuranta
						LEFT JOIN tilausrivi ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus
						WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]'
						and sarjanumeroseuranta.tuoteno='$rivirow[tuoteno]'
						and sarjanumeroseuranta.myyntirivitunnus in (0,$rivitunnus)
						$mlisa
						order by sarjanumero";
	}
	elseif($from == "riviosto" or $from == "kohdista" or (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "KERAA") and $hyvitysrivi == "ON")) {
		// Haetaan vain sellaiset sarjanumerot jotka on viel� vapaita
		$query    = "	SELECT sarjanumeroseuranta.*, tilausrivi.nimitys, tilausrivi.varattu, tilausrivi.kpl, lasku.tunnus otunnus
						FROM sarjanumeroseuranta
						LEFT JOIN tilausrivi ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus
						LEFT JOIN lasku ON lasku.yhtio=sarjanumeroseuranta.yhtio and lasku.tunnus=tilausrivi.otunnus
						WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]'
						and sarjanumeroseuranta.tuoteno='$rivirow[tuoteno]'
						and sarjanumeroseuranta.ostorivitunnus in (0,$rivitunnus)
						order by sarjanumero";
	}
	else {
		$lisa  = "";
		$lisa2 = "";

		if ($ostotilaus_haku != "") {
			if (is_numeric($ostotilaus_haku)) {
				$lisa .= " and lasku.tunnus='$ostotilaus_haku' ";
			}
			else {
				$lisa .= " and match (lasku.nimi) against ('$ostotilaus_haku*' IN BOOLEAN MODE) ";
			}
		}

		if ($myyntitilaus_haku != "") {
			if (is_numeric($myyntitilaus_haku)) {
				$lisa .= " and lasku.tunnus='$myyntitilaus_haku' ";
			}
			else {
				$lisa .= " and match (lasku.nimi) against ('$myyntitilaus_haku*' IN BOOLEAN MODE) ";
			}
		}

		if ($ostotilaus_haku != "" and $myyntitilaus_haku != "") {
			$lisa .= " and lasku.tunnus in ('$ostotilaus_haku','$myyntitilaus_haku') ";
		}

		if ($ostotilaus_haku != "" or $myyntitilaus_haku != "") {
			$lisa2 = "	LEFT JOIN tilausrivi ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and (sarjanumeroseuranta.ostorivitunnus=tilausrivi.tunnus or sarjanumeroseuranta.myyntirivitunnus=tilausrivi.tunnus)
						LEFT JOIN lasku ON lasku.yhtio=sarjanumeroseuranta.yhtio and lasku.tunnus=tilausrivi.otunnus ";
		}

		if ($lisatieto_haku) {
			$lisa .= " and sarjanumeroseuranta.lisatieto like '$lisatieto_haku%' ";
		}

		if ($tuoteno_haku) {
			$lisa .= " and sarjanumeroseuranta.tuoteno like '$tuoteno_haku%' ";
		}

		if ($nimitys_haku) {
			$lisa .= " and tuote.nimitys like '$nimitys_haku%' ";
		}

		if ($sarjanumero_haku) {
			$lisa .= " and sarjanumeroseuranta.sarjanumero like '$sarjanumero_haku%' ";
		}

		if ($toiminto == "MUOKKAA" and $sarjatunnus != 0) {
			$lisa .= " and sarjanumeroseuranta.tunnus != '$sarjatunnus' and (sarjanumeroseuranta.perheid=0 or sarjanumeroseuranta.perheid='$sarjatunnus') ";
		}

		// N�ytet��n kaikki
		$query = "	SELECT distinct sarjanumeroseuranta.*, tuote.nimitys
					FROM sarjanumeroseuranta
					LEFT JOIN tuote ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
					$lisa2
					WHERE sarjanumeroseuranta.yhtio='$kukarow[yhtio]'
					$lisa
					ORDER BY tuoteno, myyntirivitunnus
					LIMIT 100";
	}
	$sarjares = mysql_query($query) or pupe_error($query);

	if ($rivirow["tuoteno"] != '') {
		echo "<table>";
		echo "<tr><th>".t("Tuotenumero")."</th><td>$rivirow[tuoteno] $rivirow[nimitys]</td></tr>";
		echo "<tr><th>".t("M��r�")."</th><td>$rivirow[varattu] $rivirow[yksikko]</td></tr>";
		echo "</table><br>";
	}

	if (file_exists('sarjanumeron_lisatiedot_popup.inc')) {
		require("sarjanumeron_lisatiedot_popup.inc");
	}

	echo js_popup(500);
	$divit = "";

	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Sarjanumero")."</th>";
	echo "<th>".t("Tuoteno")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Ostotilaus")."</th>";
	echo "<th>".t("Myyntitilaus")."</th>";
	echo "<th>".t("Sis�inen viesti")."</th>";
	echo "<th>".t("Valitse")."</th>";
	echo "<th>".t("Muokkaa")."</th>";
	echo "<th>".t("Poista")."</th>";
	echo "<th>".t("Lis�tiedot")."</th>";
	echo "</tr>";

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='$tunnuskentta' 	value = '$rivitunnus'>";
	echo "<input type='hidden' name='from' 				value = '$from'>";
	echo "<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>";
	echo "<input type='hidden' name='toiminto' 			value = '$toiminto'>";
	echo "<input type='hidden' name='sarjatunnus' 		value = '$sarjatunnus'>";
	echo "<input type='hidden' name='otunnus' 			value = '$otunnus'>";
	echo "<tr>";
	echo "<td><input type='text' size='10' name='sarjanumero_haku' 		value='$sarjanumero_haku'></td>";
	echo "<td><input type='text' size='10' name='tuoteno_haku' 			value='$tuoteno_haku'></td>";
	echo "<td><input type='text' size='10' name='nimitys_haku' 			value='$nimitys_haku'></td>";
	echo "<td><input type='text' size='10' name='ostotilaus_haku' 		value='$ostotilaus_haku'></td>";
	echo "<td><input type='text' size='10' name='myyntitilaus_haku'		value='$myyntitilaus_haku'></td>";
	echo "<td><input type='text' size='10' name='lisatieto_haku' 		value='$lisatieto_haku'></td>";
	echo "<td></td><td></td><td></td><td></td><td><input type='submit' value='Hae'></td>";
	echo "</tr>";
	echo "</form>";

	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='$tunnuskentta' 	value='$rivitunnus'>";
	echo "<input type='hidden' name='from' 				value='$from'>";
	echo "<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>";
	echo "<input type='hidden' name='toiminto' 			value='$toiminto'>";
	echo "<input type='hidden' name='sarjatunnus' 		value='$sarjatunnus'>";
	echo "<input type='hidden' name='otunnus' 			value='$otunnus'>";
	echo "<input type='hidden' name='formista' 			value='kylla'>";
	echo "<input type='hidden' name='sarjanumero_haku' 	value='$sarjanumero_haku'>";
	echo "<input type='hidden' name='tuoteno_haku' 		value='$tuoteno_haku'>";
	echo "<input type='hidden' name='nimitys_haku' 		value='$nimitys_haku'>";
	echo "<input type='hidden' name='ostotilaus_haku' 	value='$ostotilaus_haku'>";
	echo "<input type='hidden' name='myyntitilaus_haku'	value='$myyntitilaus_haku'>";
	echo "<input type='hidden' name='lisatieto_haku' 	value='$lisatieto_haku'>";

	while ($sarjarow = mysql_fetch_array($sarjares)) {

		if (function_exists("sarjanumeronlisatiedot_popup")) {
			$divit .= sarjanumeronlisatiedot_popup ($sarjarow["tunnus"]);
		}

		echo "<tr>";
		echo "<td>$sarjarow[sarjanumero]</td>";
		echo "<td>$sarjarow[tuoteno]</td>";
		echo "<td>$sarjarow[nimitys]</td>";

		if ($sarjarow["ostorivitunnus"] == 0) {
			$sarjarow["ostorivitunnus"] = "";
		}
		if ($sarjarow["myyntirivitunnus"] == 0) {
			$sarjarow["myyntirivitunnus"] = "";
		}

		$query 	= "	SELECT lasku.*
					FROM tilausrivi
					JOIN lasku ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
					WHERE tilausrivi.tunnus='$sarjarow[ostorivitunnus]' and tilausrivi.yhtio='$kukarow[yhtio]'";
		$lasres	= mysql_query($query) or pupe_error($query);
		$lasrow1	= mysql_fetch_array($lasres);

		echo "<td>$lasrow1[tunnus] $lasrow1[nimi]</td>";

		$query 	= "	SELECT lasku.*
					FROM tilausrivi
					JOIN lasku ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
					WHERE tilausrivi.tunnus='$sarjarow[myyntirivitunnus]' and tilausrivi.yhtio='$kukarow[yhtio]'";
		$lasres	= mysql_query($query) or pupe_error($query);
		$lasrow2	= mysql_fetch_array($lasres);

		echo "<td>$lasrow2[tunnus] $lasrow2[nimi]</td>";

		echo "<td>$sarjarow[lisatieto]</td>";

		if (($sarjarow[$tunnuskentta] == 0 or $sarjarow[$tunnuskentta] == $rivitunnus) and $rivitunnus != '') {
			$chk = "";
			if ($sarjarow[$tunnuskentta] == $rivitunnus){
				$chk="CHECKED";
			}

			if ($tunnuskentta == "ostorivitunnus" and $sarjarow["kpl"] != 0) {
				echo "<td>".t("Lukittu")."</td>";
			}
			elseif (($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA" or $from == "KERAA") or ($from == "riviosto" or $from == "kohdista")) {
				echo "<input type='hidden' name='sarjat[]' value='$sarjarow[tunnus]'>";
				echo "<td><input type='checkbox' name='sarjataan[]' value='$sarjarow[tunnus]' $chk onclick='submit()'></td>";
			}
		}
		elseif($toiminto == 'MUOKKAA' and $voidaan_liittaa == "YES" and $sarjatunnus != '' and $sarjatunnus != $sarjarow["tunnus"]) {
			$chk='';
			if ($sarjatunnus == $sarjarow["perheid"]) {
				$chk = "CHECKED";
			}

			echo "<input type='hidden' name='linkit[]' value='$sarjatunnus'>";
			echo "<td class='spec'><input type='checkbox' name='linkataan[$sarjatunnus###$sarjarow[tunnus]]' value='$sarjatunnus###$sarjarow[tunnus]' $chk onclick='submit()'></td>";
		}
		else {
			echo "<td></td>";
		}


		//jos saa muuttaa niin n�ytet��n muokkaa linkki
		echo "<td><a href='$PHP_SELF?toiminto=MUOKKAA&$tunnuskentta=$rivitunnus&from=$from&otunnus=$otunnus&sarjatunnus=$sarjarow[tunnus]&sarjanumero_haku=$sarjanumero_haku&tuoteno_haku=$tuoteno_haku&nimitys_haku=$nimitys_haku&ostotilaus_haku=$ostotilaus_haku&myyntitilaus_haku=$myyntitilaus_haku&lisatieto_haku=$lisatieto_haku'>".t("Muokkaa")."</a></td>";

		if ($sarjarow['ostorivitunnus'] == "" and $sarjarow['myyntirivitunnus'] == "") {
			echo "<td><a href='$PHP_SELF?toiminto=POISTA&$tunnuskentta=$rivitunnus&from=$from&otunnus=$otunnus&sarjatunnus=$sarjarow[tunnus]'>".t("Poista")."</a></td>";
		}
		else {
			echo "<td></td>";
		}

		$query = "	SELECT *
					FROM sarjanumeron_lisatiedot
					WHERE yhtio		 = '$kukarow[yhtio]'
					and liitostunnus = '$sarjarow[tunnus]'";
		$lisares = mysql_query($query) or pupe_error($query);
		$lisarow = mysql_fetch_array($lisares);

		if ($lisarow["tunnus"] != 0) {
			$ylisa = "&tunnus=$lisarow[tunnus]";
		}
		else {
			$ylisa = "&liitostunnus=$sarjarow[tunnus]&uusi=1";
		}

		echo "<td class='menu' onmouseout=\"popUp(event,'$sarjarow[tunnus]')\" onmouseover=\"popUp(event,'$sarjarow[tunnus]')\"><a href='../yllapito.php?toim=sarjanumeron_lisatiedot$ylisa&lopetus=$PHP_SELF!!!!$tunnuskentta=$rivitunnus!!from=$from!!otunnus=$otunnus!!sarjanumero_haku=$sarjanumero_haku!!tuoteno_haku=$tuoteno_haku!!nimitys_haku=$nimitys_haku!!ostotilaus_haku=$ostotilaus_haku!!myyntitilaus_haku=$myyntitilaus_haku!!lisatieto_haku=$lisatieto_haku'>".t("Lis�tiedot")."</a></td>";

		echo "</tr>";

		if (($sarjarow["perheid"] != 0 and $sarjarow["tunnus"] == $sarjarow["perheid"]) or ($from != '' and $sarjarow["perheid"] > 0)) {
			// Haetaan sarjanumerot perhe
			$query = "	SELECT distinct sarjanumeroseuranta.*, tuote.nimitys
						FROM sarjanumeroseuranta
						JOIN tuote ON sarjanumeroseuranta.yhtio=tuote.yhtio and sarjanumeroseuranta.tuoteno=tuote.tuoteno
						WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
						and sarjanumeroseuranta.tunnus != '$sarjarow[tunnus]'
						and sarjanumeroseuranta.perheid = '$sarjarow[perheid]'
						ORDER BY sarjanumero";
			$lisares = mysql_query($query) or pupe_error($query);

			while($lisarow = mysql_fetch_array($lisares)) {
				echo "<tr>";
				echo "<td class='spec'>$lisarow[sarjanumero]</td>";
				echo "<td class='spec'>$lisarow[tuoteno]</td>";

				if (function_exists("sarjanumeronlisatiedot_popup")) {
					$divit .= sarjanumeronlisatiedot_popup ($lisarow["tunnus"]);
				}

				echo "<td class='spec' colspan='4' align='right'>$sarjarow[nimitys] ja $lisarow[nimitys] liitetty toisiinsa</td>";
				echo "<td class='spec'>x</td>";
				echo "<td class='spec'></td>";
				echo "<td class='spec'></td>";

				//Haetaan sarjanumeron lis�tiedot
				$query    = "	SELECT *
								FROM sarjanumeron_lisatiedot
								WHERE yhtio		 = '$kukarow[yhtio]'
								and liitostunnus = '$lisarow[tunnus]'";
				$lisares1 = mysql_query($query) or pupe_error($query);
				$lisarow1 = mysql_fetch_array($lisares1);

				if ($lisarow1["tunnus"] != 0) {
					$ylisa = "&tunnus=$lisarow1[tunnus]";
				}
				else {
					$ylisa = "&liitostunnus=$lisarow[tunnus]&uusi=1";
				}

				echo "<td class='menu' onmouseout=\"popUp(event,'$lisarow[tunnus]')\" onmouseover=\"popUp(event,'$lisarow[tunnus]')\"><a href='../yllapito.php?toim=sarjanumeron_lisatiedot$ylisa&lopetus=$PHP_SELF!!!!$tunnuskentta=$rivitunnus!!from=$from!!otunnus=$otunnus'>".t("Lis�tiedot")."</a></td>";
				echo "</tr>";
			}
		}
	}

	echo "</form>";
	echo "</table>";

	//Piilotetut divit jotka popappaa javascriptill�
	echo $divit;

	if ($toiminto== '') {
		$sarjanumero 	= '';
		$lisatieto 		= '';
		$chk 			= '';
	}

	if ($rivirow["tuoteno"] != '') {
		echo "<br><table>";
		echo "<tr><th colspan='2'>".t("Lis�� uusi sarjanumero")."</th></tr>";
		echo "<tr><th>".t("Sarjanumero")."</th>";

		echo "	<form action='$PHP_SELF' method='post'>
				<input type='hidden' name='$tunnuskentta' value='$rivitunnus'>
				<input type='hidden' name='from' value='$from'>
				<input type='hidden' name='otunnus' value='$otunnus'>
				<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
				<input type='hidden' name='toiminto' value='LISAA'>";
		echo "<td><input type='text' size='30' name='sarjanumero' value='$sarjanumero'></td></tr>";
		echo "<tr><th>".t("Lis�tieto")."</th><td><input type='text' size='30' name='lisatieto' value='$lisatieto'></td>";
		echo "<td class='back'><input type='submit' value='".t("Lis��")."'></form></td>";
		echo "</tr></table>";
	}

	echo "<br>";

	if ($from == "PIKATILAUS" or $from == "RIVISYOTTO" or $from == "TARJOUS" or $from == "SIIRTOLISTA") {
		echo "<form method='post' action='tilaus_myynti.php'>
			<input type='hidden' name='toim' value='$from'>
			<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
			<input type='submit' value='".t("Takaisin tilaukselle")."'>
			</form>";
	}

	if ($from == "riviosto") {
		echo "<form method='post' action='tilaus_osto.php'>
			<input type='hidden' name='tee' value='Y'>
			<input type='hidden' name='aktivoinnista' value='true'>
			<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
			<input type='submit' value='".t("Takaisin tilaukselle")."'>
			</form>";
	}

	if ($from == "kohdista") {
		echo "<form method='post' action='keikka.php'>
			<input type='hidden' name='toiminto' value='kohdista'>
			<input type='hidden' name='muut_siirrettavat' value = '$muut_siirrettavat'>
			<input type='hidden' name='otunnus' value='$otunnus'>
			<input type='submit' value='".t("Takaisin keikkaan")."'>
			</form>";
	}

	if ($from == "KERAA") {
		echo "<form method='post' action='keraa.php'>
			<input type='hidden' name='id' value='$otunnus'>
			<input type='submit' value='".t("Takaisin ker�ykseen")."'>
			</form>";
	}

	require ("../inc/footer.inc");

?>