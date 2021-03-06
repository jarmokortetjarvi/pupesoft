<?php

// tarvitaan $kukarow, $tuoteno ja $tee jossa on paalle, puolipaalle tai pois
$debug = 0;

if (trim($tuoteno) == "") {
	$tee = '';
}

if ($tuoteno != "" and ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois" or $tee == "vahvista")) {

	$query = "	SELECT tuote.tunnus, tuote.tuoteno, kehahin, epakurantti25pvm, epakurantti50pvm, epakurantti75pvm, epakurantti100pvm, sum(saldo) saldo
				FROM tuote
				LEFT JOIN tuotepaikat ON (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno)
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				AND tuote.sarjanumeroseuranta NOT IN ('S','U','G')
				AND tuote.tuoteno = '$tuoteno'
				GROUP by 1,2,3,4,5,6,7";
	$result = pupe_query($query);

	if (mysql_num_rows($result) != 1) {
		echo "<font class='error'>";
		printf (t("Tuote %s ei löydy!"), $tuoteno);
		echo "</font><br>";
		$tee = '';
	}
	$tuoterow = mysql_fetch_assoc($result);

	// lasketaan varastonarvo
	$muutos = $tuoterow['saldo'] * $tuoterow['kehahin'];

	if ($debug == 1) {
		echo "Muutos = $muutos<br>";
	}

	// otetaan 'oikea' arvo talteen ihan vaan kaunistelua varten
	$apu2 = $muutos;
	if     ($tuoterow['epakurantti100pvm'] != '0000-00-00')	$apu = 0;
	elseif ($tuoterow['epakurantti75pvm'] != '0000-00-00')	$apu = $muutos * 0.25 ;
	elseif ($tuoterow['epakurantti50pvm'] != '0000-00-00')	$apu = $muutos * 0.5;
	elseif ($tuoterow['epakurantti25pvm'] != '0000-00-00')	$apu = $muutos * 0.75;
	else													$apu = $muutos;

}

if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois") {

	$ekakerr = $tokakerr = 0;

	if ($tuoterow['epakurantti100pvm'] != '0000-00-00') {
		$ekakerr = 4;
	}
	elseif ($tuoterow['epakurantti75pvm'] != '0000-00-00') {
		$ekakerr = 3;
	}
	elseif ($tuoterow['epakurantti50pvm'] != '0000-00-00') {
		$ekakerr = 2;
	}
	elseif ($tuoterow['epakurantti25pvm'] != '0000-00-00') {
		$ekakerr = 1;
	}

	if ($tee == 'paalle') {
		$tokakerr = 4;
	}
	if ($tee == '75paalle') {
		$tokakerr = 3;
	}
	elseif ($tee == 'puolipaalle') {
		$tokakerr = 2;
	}
	elseif ($tee == '25paalle') {
		$tokakerr = 1;
	}

	$kerr = ($tokakerr-$ekakerr)*0.25;

	if ($kerr == 1) {
		$muutos = $muutos * -1;
	}
	else {
		$muutos = ($muutos * $kerr) * -1;
	}

	if ($debug == 1) {
		echo "$ekakerr $tokakerr $kerr $tee laskuettu uudestaan Muutos = $muutos<br>";
	}

	$muutos = round($muutos,2);

	if ($debug == 1) {
		echo "$tee roundattu Muutos = $muutos<br>";
	}

	if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois") {

		// katotaan onko tilauksessa
		$query = "	SELECT sum(varattu) varattu
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
					WHERE yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoterow[tuoteno]'
					and varattu > 0
					and tyyppi = 'O'";
		$tilre = pupe_query($query);
		$tilro = mysql_fetch_assoc($tilre);

		if ($tilro["varattu"] != 0 and $tee != "pois") {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuotetta on tilauksessa!")." ".t("Ei voida laittaa epäkurantiksi!")."</font><br>";
			$tee = "";
		}

		// katotaan onko liitetty keikkaan jonka virallista varastonarvoa ei ole laskettu
		$query = "	SELECT laskunro
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio
						AND lasku.tunnus = tilausrivi.uusiotunnus
						AND lasku.tila = 'K'
						AND lasku.alatila = ''
						AND lasku.kohdistettu != 'X')
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
					and tuoteno = '$tuoterow[tuoteno]'
					and varattu = 0
					and tyyppi = 'O'";
		$tilre = pupe_query($query);

		if (mysql_num_rows($tilre) != 0 and $tee != "pois") {
			$tilro = mysql_fetch_assoc($tilre);
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuotetta on viemättä varastoon keikalla")." $tilro[laskunro]. ".t("Ei voida laittaa epäkurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida aktivoida tuotetta kurantiksi jos se on jo kurantti
		if (($tee == 'pois') and ($tuoterow['epakurantti25pvm'] == '0000-00-00') and ($tuoterow['epakurantti50pvm'] == '0000-00-00') and ($tuoterow['epakurantti75pvm'] == '0000-00-00') and ($tuoterow['epakurantti100pvm'] == '0000-00-00')) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa kurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliepäkurantiksi jos tuote on puoliepäkurantti tai täysepäkurantti
		if (($tee == '25paalle') and (($tuoterow['epakurantti25pvm'] != '0000-00-00') or ($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa 25% epäkurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliepäkurantiksi jos tuote on puoliepäkurantti tai täysepäkurantti
		if (($tee == 'puolipaalle') and (($tuoterow['epakurantti50pvm'] != '0000-00-00') or ($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa puoliepäkurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa puoliepäkurantiksi jos tuote on puoliepäkurantti tai täysepäkurantti
		if (($tee == '75paalle') and (($tuoterow['epakurantti75pvm'] != '0000-00-00') or ($tuoterow['epakurantti100pvm'] != '0000-00-00'))) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa 75% epäkurantiksi!")."</font><br>";
			$tee = "";
		}

		// ei voida laittaa täysepäkurantiksi jos tuote on jo täysepäkurantti
		if (($tee == 'paalle')      and ($tuoterow['epakurantti100pvm'] != '0000-00-00')) {
			echo "<font class='error'>$tuoterow[tuoteno] ".t("Tuote väärässä tilassa!")." ".t("Ei voida laittaa täysiepäkurantiksi!")."</font><br>";
			$tee = "";
		}

		 ///* Päivitetään tuote kurantiksi *///
		if ($tee == 'pois') {
			$query = "	UPDATE tuote SET
						epakurantti25pvm = '0000-00-00',
						epakurantti50pvm = '0000-00-00',
						epakurantti75pvm = '0000-00-00',
						epakurantti100pvm = '0000-00-00'
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}
		else {
			$lisa75 = "";
			$lisa50 = "";
			$lisa25 = "";

			if ($tuoterow['epakurantti75pvm'] == '0000-00-00') { // jos tuote ei ole puoliepäkurantti, päivitetään sama päivä tännekkin
				$lisa75 = " epakurantti75pvm = now(), ";
			}

			if ($tuoterow['epakurantti50pvm'] == '0000-00-00') { // jos tuote ei ole puoliepäkurantti, päivitetään sama päivä tännekkin
				$lisa50 = " epakurantti50pvm = now(), ";
			}

			if ($tuoterow['epakurantti25pvm'] == '0000-00-00') { // jos tuote ei ole puoliepäkurantti, päivitetään sama päivä tännekkin
				$lisa25 = " epakurantti25pvm = now(), ";
			}

		}

		///* Päivitetään tuote täysepäkurantiksi *///
		if ($tee == 'paalle') {
			$query = "	UPDATE tuote set
						$lisa75
						$lisa50
						$lisa25
						epakurantti100pvm = now()
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* Päivitetään tuote 75epäkurantiksi *///
		if ($tee == '75paalle') {
			$query = "	UPDATE tuote set
						$lisa50
						$lisa25
						epakurantti75pvm = now(),
						epakurantti100pvm = ''
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* Päivitetään tuote puoliepäkurantiksi *///
		if ($tee == 'puolipaalle') {
			$query = "	UPDATE tuote set
						$lisa25
						epakurantti50pvm = now(),
						epakurantti75pvm = '',
						epakurantti100pvm = ''
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		///* Päivitetään tuote 25epäkurantiksi *///
		if ($tee == '25paalle') {
			$query = "	UPDATE tuote set
						epakurantti25pvm = now(),
						epakurantti50pvm = '',
						epakurantti75pvm = '',
						epakurantti100pvm = ''
						WHERE yhtio = '$kukarow[yhtio]'
						and	tuoteno = '$tuoterow[tuoteno]'";
			$result = pupe_query($query);
		}

		// jos ollaan laittamassa tuotetta puoliepäkurantiksi, puoliepäkuranttia täysepäkurantiksi tai laittamassa puoliepäkuranttia kurantiksi tai jotain, muutetaan kehahin
		$kerr2 = 1-$kerr;

		$tuoterow["kehahin"] = $tuoterow["kehahin"] - ($tuoterow["kehahin"]*$kerr2);

		if ($tee == 'pois') {
			$tuoterow["kehahin"] = $tuoterow["kehahin"]*-1;
		}

		// jos ollaan laittamassa tuotetta epäkurantiksi niin käännetään saldon etumerkki
		if ($tee == 'paalle' or $tee == '75paalle' or $tee == 'puolipaalle' or $tee == '25paalle') {
			$tuoterow["saldo"] = $tuoterow["saldo"] * -1;
		}

		// jos meillä on vielä täällä tee muuttuja, ni voidaan tehä tapahtuma
		if ($tee != "") {

			if ($debug == 1) {
				echo "$tee ennen inserttejä Muutos = $muutos<br>";
			}

			//jos yhtiö haluaa että uusi keskihankinta lasketaan, perusuen epäkuranttien myynnistä, kun poistetaan epakurantista.
			if ($tee == 'pois' and $yhtiorow['epakur_kehahin_paivitys'] == 'X') {
				$query = 	"SELECT tapahtuma.kplhinta, tapahtuma.selite, tuote.kehahin
							FROM tapahtuma, tuote
							WHERE tapahtuma.yhtio = tuote.yhtio
							and tapahtuma.tuoteno = tuote.tuoteno
							and tapahtuma.yhtio = '$kukarow[yhtio]'
							and tapahtuma.tuoteno = '$tuoterow[tuoteno]'
							and tapahtuma.laji = 'Epäkurantti'
							ORDER BY tapahtuma.tunnus desc
							LIMIT 1";
				$result = pupe_query($query);
				if (mysql_num_rows($result) > 0) {
					$vikarow = mysql_fetch_assoc($result);

					if (strtoupper(substr($vikarow["selite"],0,6)) == "PAALLE") {
						$uuushinta = 0.0001;
					}
					else {
						$uuushinta = $vikarow["kehahin"] - $vikarow["kplhinta"];
					}

					$query = "	UPDATE tuote SET
								kehahin = '$uuushinta',
								muutospvm = now(),
								muuttaja = '$kukarow[kuka]'
								WHERE yhtio = '$kukarow[yhtio]'
								and tuoteno = '$tuoterow[tuoteno]'";
					$result = pupe_query($query);
				}
				else {
					die(t("Kuranttiustapahtumaa tai tuotetta ei löydy. Ei uskalleta jatkaa!"));
				}
			}

			///* Tehdään tapahtuma *///
			$query = "	INSERT into tapahtuma set
						yhtio   	= '$kukarow[yhtio]',
						tuoteno 	= '$tuoterow[tuoteno]',
						laji    	= 'Epäkurantti',
						kpl     	= '$tuoterow[saldo]',
						hinta   	= round('$tuoterow[kehahin]', 6),
						kplhinta	= round('$tuoterow[kehahin]', 6),
						selite  	= '$tee. ".t("Keskihankintahinta").": $tuoterow[kehahin] ".t("Varastonmuutos").": $muutos',
						laatija    	= '$kukarow[kuka]',
						laadittu 	= now()";
			$result = pupe_query($query);

			// otetaan tapahtuman tunnus, laitetaan se tiliöinnin otsikolle
			$tapahtumaid = mysql_insert_id();

			///* Kirjanpito *///
			if ($muutos <> 0) {

				if ($tapahtumat_samalle_tositteelle != "kylla" or $laskuid == 0) {
					$query = "	INSERT into lasku set
								yhtio      = '$kukarow[yhtio]',
								tapvm      = now(),
								tila       = 'X',
								viite      = '$tapahtumaid',
								laatija    = '$kukarow[kuka]',
								luontiaika = now()";
					$result = pupe_query($query);
					$laskuid = mysql_insert_id($link);
				}

				if ($yhtiorow["varastonmuutos_epakurantti"] != "") {
					$varastonmuutos_tili = $yhtiorow["varastonmuutos_epakurantti"];
				}
				else {
					$varastonmuutos_tili = $yhtiorow["varastonmuutos"];
				}

				// Tiliöidään ensisijaisesti varastonmuutos tilin oletuskustannuspaikalle
				list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($varastonmuutos_tili);

				// Toissijaisesti kokeillaan vielä varasto-tilin oletuskustannuspaikkaa
				list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["varasto"], $kustp_ins, $kohde_ins, $projekti_ins);

				$query = "	INSERT into tiliointi set
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$yhtiorow[varasto]',
							kustp    = '{$kustp_ins}',
							kohde	 = '{$kohde_ins}',
							projekti = '{$projekti_ins}',
							tapvm    = now(),
							summa    = '$muutos',
							vero     = 0,
							lukko    = '',
							selite   = 'Tuote $tuoterow[tuoteno] epäkuranttimuutos',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = pupe_query($query);

				$query = "	INSERT into tiliointi set
							yhtio    = '$kukarow[yhtio]',
							ltunnus  = '$laskuid',
							tilino   = '$varastonmuutos_tili',
							kustp    = '{$kustp_ins}',
							kohde	 = '{$kohde_ins}',
							projekti = '{$projekti_ins}',
							tapvm    = now(),
							summa    = $muutos * -1,
							vero     = 0,
							lukko    = '',
							selite   = 'Tuote $tuoterow[tuoteno] epäkuranttimuutos',
							laatija  = '$kukarow[kuka]',
							laadittu = now()";
				$result = pupe_query($query);
			}

			if (!isset($php_cli) or !$php_cli) {
				echo "<font class='message'>";
				echo t("Tuote")." $tuoteno ok!<br>";
				echo t("Varastonarvoa muutettiin")." $muutos $yhtiorow[valkoodi].<br>";
				echo "</font><br>";
			}
		}
	}
}

?>