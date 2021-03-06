<?php

	// tarvitaan $tapa jossa on vienti / tuonti
	// ja $row jossa on kentt� kaikkitunnukset ja kaikkituotteet
	// kasvatetaan $virhe muuttujaa jos l�ytyy virheit�
	// itse virheet tulee $virhetxt muuttujaan

	$virhetxt = "";
	$specutxt = "";
	$lopetus  = "$palvelin2"."intrastat.php////tee=tulosta//kk=$kk//vv=$vv//tapa=$tapa//outputti=$outputti//lahetys=nope//vaintullinimike=$vaintullinimike//kayttajan_valinta_maa=$kayttajan_valinta_maa//tapahtumalaji=$tapahtumalaji";

	// k�yd��n l�pi kaikki tuotteet
	$query = "	SELECT *,
					(SELECT alkuperamaa
					FROM tuotteen_toimittajat
					WHERE tuotteen_toimittajat.yhtio = tuote.yhtio
					and tuotteen_toimittajat.tuoteno = tuote.tuoteno
					and tuotteen_toimittajat.alkuperamaa != ''
					ORDER BY if(alkuperamaa = '$yhtiorow[maa]', '2', '1')
					LIMIT 1) alkuperamaa
				FROM tuote
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				AND tuote.tuoteno in ($row[kaikkituotteet])";
	$intrtuores = mysql_query($query) or pupe_error($query);

	while ($intrtuorow = mysql_fetch_array($intrtuores)) {

		if ($intrtuorow["tullinimike1"] == '' or $intrtuorow["tullinimike1"] == 0) {
		  	$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a>. ".t("Tullinimike tuotteelta puuttuu")."!<br>";
			$virhe++;
		}
		elseif ($intrtuorow["tullinimike1"] != "") {
			$query = "	SELECT cn
						FROM tullinimike
						WHERE cn = '$intrtuorow[tullinimike1]'
						AND kieli = '$yhtiorow[kieli]'
						AND cn != ''";
			$tulserul = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tulserul) == 0) {
			  	$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a> ".t("Tullinimike on virheellinen")." $intrtuorow[tullinimike1]<br>";
				$virhe++;
			}
		}
		else {
		  	$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a> ".t("Tullinimike on virheellinen")." $intrtuorow[tullinimike1]<br>";
			$virhe++;
		}

		// #TODO t�ll�nen h�kki, ett� toimii :) vois korjata joskus
		if ($intrtuorow["alkuperamaa"] == $yhtiorow["maa"] and $intrtuorow["toim_maa"] == $yhtiorow["maa"]) {
			$intrtuorow["alkuperamaa"] = "SE";
		}

		if ($intrtuorow["alkuperamaa"] == '') {
			$virhetxt .= t("Alkuper�maa puuttuu tuotteelta")." <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a>!<br>";
			$virhe++;
		}
		elseif ($intrtuorow["alkuperamaa"] == $yhtiorow["maa"] and $tapa != "vienti") {
			$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a>. ".t("Alkuper�maa ei voi olla sama kuin yhti�n kotimaa")."!<br>";
			$virhe++;
		}
		else {
			$query = "	SELECT distinct koodi
						FROM maat
						WHERE koodi='$intrtuorow[alkuperamaa]'";
			$maaresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($maaresult) == 0) {
				$virhetxt .= t("Tuote").": <a href='yllapito.php?toim=tuote&tunnus=$intrtuorow[tunnus]&lopetus=$lopetus'>$intrtuorow[tuoteno]</a>. ".t("Alkuper�maa on virheellinen")."!<br>";
				$virhe++;
			}
		}
	}

	// k�yd��n l�pi kaikki laskut
	$query = "	SELECT lasku.*,
				if(lasku.maa_lahetys = '', ifnull(varastopaikat.maa, lasku.yhtio_maa), lasku.maa_lahetys) maalahetys,
				if(lasku.maa_maara = '', lasku.toim_maa, lasku.maa_maara) maamaara
	 			FROM lasku
				LEFT JOIN varastopaikat ON (varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto)
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				AND lasku.tunnus in ($row[kaikkitunnukset])";
	$intrlasres = mysql_query($query) or pupe_error($query);

	while ($intrlasrow = mysql_fetch_array($intrlasres)) {

		$specutxt = "";

		// erikoiskeissi jos kyseess� on keikka joka on vienti�
		if ($tapa == "vienti" and $row["tapa"] == "Keikka") {
			$specutxt = "T�m� on keikka joka on viety kotimaasta ulkomaanvarastoon.<br>";
			$tapa = "vientituonti";
		}

		// erikoiskeissi jos kyseess� on lasku joka on tuontia
		if ($tapa == "tuonti" and $row["tapa"] == "Lasku") {
			$specutxt  = "T�m� on myyntilasku joka on myyty ulkomaanvarastosta kotimaahan.<br>";
			$tapa = "tuontivienti";
		}

		// echoillaan t�ll�st� infoa
		if ($tapa == "vienti" and $row["tapa"] == "Siirtolista") {
			$specutxt = "T�m� on siirtolista joka on viety kotimaasta ulkomaanvarastoon.<br>";
		}

		// echoillaan t�ll�st� infoa
		if ($tapa == "tuonti" and $row["tapa"] == "Siirtolista") {
			$specutxt  = "T�m� on siirtolista joka on tuotu ulkomaanvarastosta kotimaahan.<br>";
		}

		// kauniiiimpaa
		if ($row["tapa"] == "Siirtolista") {
			$intrlasrow["laskunro"] = $intrlasrow["tunnus"];
		}

		// tuontispecific tarkistuksia
		if ($tapa == 'tuonti' or $tapa == 'tuontivienti') {

			if ($intrlasrow["maalahetys"] == '') {
				$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("L�hetysmaa puuttuu")."!<br>";
				$virhe++;
			}
			elseif ($intrlasrow["maalahetys"] == $yhtiorow["maa"]) {
				$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("L�hetysmaa ei voi olla sama kuin yhti�n kotimaa")."!<br>";
				$virhe++;
			}
			else {
				$query = "	SELECT distinct koodi, eu
							FROM maat
							WHERE koodi = '$intrlasrow[maalahetys]'";
				$maaresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($maaresult) == 0) {
					$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("L�hetysmaa on virheellinen")."!<br>";
					$virhe++;
				}
				else {
					$eurow = mysql_fetch_array($maaresult);
					if ($eurow["eu"] == "") {
						$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("L�hetysmaa on virheellinen").": NON-EU!<br>";
						$virhe++;
					}
				}
			}

		}

		// vientispecifig tarkistuksia
		if ($tapa == 'vienti' or $tapa == 'vientituonti') {

			if ($intrlasrow["maamaara"] == '') {
				$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("M��r�maa puuttuu")."!<br>";
				$virhe++;
			}
			elseif ($intrlasrow["maamaara"] == $yhtiorow["maa"]) {
				$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("M��r�maa ei voi olla sama kuin yhti�n kotimaa")."!<br>";
				$virhe++;
			}
			else {
				$query = "	SELECT distinct koodi, eu
							FROM maat
							WHERE koodi = '$intrlasrow[maamaara]'";
				$maaresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($maaresult) == 0) {
					$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("M��r�maa on virheellinen")."!<br>";
					$virhe++;
				}
				else {
					$eurow = mysql_fetch_array($maaresult);
					if ($eurow["eu"] == "") {
						$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("M��r�maa on virheellinen").": NON-EU!<br>";
						$virhe++;
					}
				}
			}

		}

		if ((int) $intrlasrow["kauppatapahtuman_luonne"] <= 0) {
		   	$virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Kauppatapahtuman luonne puuttuu")."!<br>";
			$virhe++;
		}

		if ((int) $intrlasrow["kuljetusmuoto"] == 0) {
		    $virhetxt .= t($row["tapa"]).": <a href='tilauskasittely/vientitilauksen_lisatiedot.php?tapa=$tapa&tee=K&otunnus=$intrlasrow[tunnus]&lopetus=$lopetus'>$intrlasrow[laskunro]</a>. ".t("Kuljetusmuoto puuttuu")."!<br>";
			$virhe++;
		}

		if ($tapa == "tuontivienti") {
			$tapa = "tuonti";
		}

		if ($tapa == "vientituonti") {
			$tapa = "vienti";
		}

	}

	if ($virhetxt != "") {
		$virhetxt = $specutxt . $virhetxt;
	}

?>