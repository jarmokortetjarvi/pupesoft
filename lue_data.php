<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Datan sis��nluku")."</font><hr>";

if ($oikeurow['paivitys'] != '1') { // Saako p�ivitt��
	if ($uusi == 1) {
		echo "<b>".t("Sinulla ei ole oikeutta lis�t�")."</b><br>";
		$uusi = '';
	}
	if ($del == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa poistaa")."</b><br>";
		$del = '';
		$tunnus = 0;
	}
	if ($upd == 1) {
		echo "<b>".t("Sinulla ei ole oikeuttaa muuttaa")."</b><br>";
		$upd = '';
		$uusi = 0;
		$tunnus = 0;
	}
}

flush();

if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

	require ("inc/pakolliset_sarakkeet.inc");

	list($name,$ext) = split("\.", $_FILES['userfile']['name']);

	if (strtoupper($ext) !="TXT" and strtoupper($ext)!="XLS" and strtoupper($ext)!="CSV") {
		die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
	}

	if ($_FILES['userfile']['size']==0) {
		die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
	}

	if (strtoupper($ext) == "XLS") {
		require_once ('excel_reader/reader.php');

		// ExcelFile
		$data = new Spreadsheet_Excel_Reader();

		// Set output Encoding.
		$data->setOutputEncoding('CP1251');
		$data->setRowColOffset(0);
		$data->read($_FILES['userfile']['tmp_name']);
	}

	echo "<font class='message'>".t("Tarkastetaan l�hetetty tiedosto")."...<br><br></font>";

	// luetaan eka rivi tiedostosta..
	if (strtoupper($ext) == "XLS") {
		$headers = array();

		for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
			$headers[] = strtoupper(trim($data->sheets[0]['cells'][0][$excej]));
		}
	}
	else {
		$file	 = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep�onnistui")."!");

		$rivi    = fgets($file);
		$headers = explode("\t", strtoupper(trim($rivi)));
	}

	$taulut			= array();
	$mul_taulut 	= array();
	$mul_taulas 	= array();
	$taulunotsikot	= array();
	$taulunrivit	= array();

	// Katsotaan onko sarakkeita useasta taulusta
	for ($i = 0; $i < count($headers); $i++) {
		if (strpos($headers[$i], ".") !== FALSE) {

			list($taulu, $sarake) = explode(".", $headers[$i]);
			$taulu = strtolower(trim($taulu));

			// Joinataanko sama taulu monta kertaa?
			if ((isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]]) and in_array($headers[$i], $mul_taulut[$taulu."__".$mul_taulas[$taulu]])) or (!isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]]) and isset($mul_taulut[$taulu]) and in_array($headers[$i], $mul_taulut[$taulu]))) {
				$mul_taulas[$taulu]++;

				$taulu = $taulu."__".$mul_taulas[$taulu];
			}
			elseif (isset($mul_taulut[$taulu."__".$mul_taulas[$taulu]])) {
				$taulu = $taulu."__".$mul_taulas[$taulu];
			}

			$taulut[] = $taulu;
			$mul_taulut[$taulu][] = $headers[$i];
		}
		else {
			$taulut[] = $table;
		}
	}

	// T�ss� kaikki taulut jotka l�yty failista
	$unique_taulut = array_unique($taulut);

	// Tutkitaan mill� ehdoilla taulut joinataan kesken��n
	$joinattavat = array();

	foreach ($unique_taulut as $utaulu) {
		list($taulu, ) = explode(".", $utaulu);
		$taulu = preg_replace("/__[0-9]*$/", "", $taulu);

		list(, , , , $joinattava) = pakolliset_sarakkeet($taulu);

		// Laitetaan aina kaikkiin tauluihin
		$joinattava["TOIMINTO"] = $table;

		$joinattavat[$utaulu] = $joinattava;
	}

	// Laitetaan jokaisen taulun otsikkorivi kuntoon
	for ($i = 0; $i < count($headers); $i++) {
		list($sarake1, $sarake2) = explode(".", $headers[$i]);
		if ($sarake2 != "") $sarake1 = $sarake2;

		$sarake1 = strtoupper(trim($sarake1));

		$taulunotsikot[$taulut[$i]][] = $sarake1;

		// Pit��k� t�m� sarake laittaa my�s johonki toiseen tauluun?
		foreach ($joinattavat as $taulu => $joinit) {
			list ($etu, $taka) = explode(".", $headers[$i]);
			if ($taka == "") $taka = $etu;

			if (isset($joinit[$taka]) and (!isset($taulunotsikot[$taulu]) or !in_array($sarake1, $taulunotsikot[$taulu]))) {
				$taulunotsikot[$taulu][] = $sarake1;
			}
		}
	}

	foreach ($taulunotsikot as $taulu => $otsikot) {
		if (count($otsikot) != count(array_unique($otsikot))) {
			echo "<font class='error'>$taulu-".t("taulun sarakkeissa ongelmia, ei voida jatkaa")."!</font><br>";

			require ("inc/footer.inc");
			exit;
		}
	}

	// Luetaan tiedosto loppuun ja tehd��n taulukohtainen array koko datasta
	if (strtoupper($ext) == "XLS") {
		for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {
			for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {

				$taulunrivit[$taulut[$excej]][$excei-1][] = trim($data->sheets[0]['cells'][$excei][$excej]);

				// Pit��k� t�m� sarake laittaa my�s johonki toiseen tauluun?
				foreach ($taulunotsikot as $taulu => $joinit) {
					list ($etu, $taka) = explode(".", $headers[$excej]);
					if ($taka == "") $taka = $etu;

					if (in_array($taka, $joinit) and $taulu != $taulut[$excej] and $taulut[$excej] == $joinattavat[$taulu][$taka]) {
						$taulunrivit[$taulu][$excei-1][] = trim($data->sheets[0]['cells'][$excei][$excej]);
					}
				}
			}
		}
	}
	else {

		$excei = 0;

		while ($rivi = fgets($file)) {
			// luetaan rivi tiedostosta..
			$rivi = explode("\t", str_replace(array("'", "\\"), "", $rivi));

			for ($excej = 0; $excej < count($rivi); $excej++) {

				$taulunrivit[$taulut[$excej]][$excei][] = trim($rivi[$excej]);

				// Pit��k� t�m� sarake laittaa my�s johonki toiseen tauluun?
				foreach ($taulunotsikot as $taulu => $joinit) {
					list ($etu, $taka) = explode(".", $headers[$excej]);
					if ($taka == "") $taka = $etu;

					if (in_array($taka, $joinit) and $taulu != $taulut[$excej] and $taulut[$excej] == $joinattavat[$taulu][$taka]) {
						$taulunrivit[$taulu][$excei][] = trim($rivi[$excej]);
					}
				}
			}
			$excei++;
		}
		fclose($file);
	}

	/*
	foreach ($taulunrivit as $taulu => $rivit) {

		list($table_mysql, ) = explode(".", $taulu);
		$table_mysql = preg_replace("/__[0-9]*$/", "", $table_mysql);

		echo "<table>";
		echo "<tr><th>$table_mysql</th>";
		foreach ($taulunotsikot[$taulu] as $key => $column) {
			echo "<th>$key => $column</th>";
		}
		echo "</tr>";
		for ($eriviindex = 0; $eriviindex < count($rivit); $eriviindex++) {
			echo "<tr><th>$table_mysql</th>";
			foreach ($rivit[$eriviindex] as $eriv) {
				echo "<td>$eriv</td>";
			}
			echo "</tr>";
		}
		echo "</table><br>";
	}
	exit;
	*/

	$taulunrivit_keys = array_keys($taulunrivit);

	for ($tril = 0; $tril < count($taulunrivit); $tril++) {

		$taulu = $taulunrivit_keys[$tril];
		$rivit = $taulunrivit[$taulu];

		$vikaa			= 0;
		$tarkea			= 0;
		$wheretarkea	= 0;
		$kielletty		= 0;
		$lask			= 0;
		$postoiminto	= 'X';
		$table_mysql 	= "";
		$tarkyhtio		= "";
		$tarkylisa 		= 1;
		$indeksi		= array();
		$indeksi_where	= array();
		$trows			= array();
		$tlength		= array();
		$apu_sarakkeet	= array();

		// Siivotaan joinit ja muut pois tietokannan nimest�
		list($table_mysql, ) = explode(".", $taulu);
		$table_mysql = preg_replace("/__[0-9]*$/", "", $table_mysql);

		// Haetaan valitun taulun sarakkeet
		$query = "SHOW COLUMNS FROM $table_mysql";
		$fres  = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($fres)) {
			// Pushataan arrayseen kaikki sarakenimet ja tietuetyypit
			$trows[strtoupper($row[0])] = $row[1];

			$tlengthpit = ereg_replace("[^0-9,]", "", $row[1]);

			if (strpos($tlengthpit, ",") !== FALSE) {
				$tlengthpit = substr($tlengthpit, 0, strpos($tlengthpit, ",")+1)+1;
			}

			$tlength[strtoupper($row[0])] = trim($tlengthpit);
		}

		// N�m� ovat pakollisia dummysarakkeita jotka ohitetaan lopussa automaattisesti!
		if (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat"))) {
			$apu_sarakkeet = array("YTUNNUS");
		}

		if (count($apu_sarakkeet) > 0) {
			foreach($apu_sarakkeet as $s) {
				$trows[strtoupper($s)] = "";
			}
		}

		if ($table_mysql == 'tullinimike') {
			$tulli_ei_kielta = "";
			$tulli_ei_toimintoa = "";

			if (in_array("KIELI", $taulunotsikot[$taulu]) === FALSE) {
				$tulli_ei_kielta = "PUUTTUU";
				$taulunotsikot[$taulu][] = "KIELI";
			}
			if (in_array("TOIMINTO", $taulunotsikot[$taulu]) === FALSE) {
				$taulunotsikot[$taulu][] = "TOIMINTO";
				$tulli_ei_toimintoa = "PUUTTUU";
			}
		}

		// Ottetaan pakolliset, kieletyt, wherelliset ja eiyhtiota tiedot
		list($pakolliset, $kielletyt, $wherelliset, $eiyhtiota, ) = pakolliset_sarakkeet($table_mysql, $taulunotsikot[$taulu]);

		// $trows sis�lt�� kaikki taulun sarakkeet ja tyypit tietokannasta
		// $taulunotsikot[$taulu] sis�lt�� kaikki sarakkeet saadusta tiedostosta

		foreach ($taulunotsikot[$taulu] as $key => $column) {
			if ($column != '') {
				if ($column == "TOIMINTO") {
					//TOIMINTO sarakkeen positio tiedostossa
					$postoiminto = (string) array_search($column, $taulunotsikot[$taulu]);
				}
				else {
					if (!isset($trows[$column])) {
						echo "<font class='error'>".t("Saraketta")." \"$column\" ".t("ei l�ydy")." $table_mysql-".t("taulusta")."!</font><br>";
						$vikaa++;
					}

					// yhtio ja tunnus kentti� ei saa koskaan muokata...
					if ($column == 'YHTIO' or $column == 'TUNNUS') {
						echo "<font class='error'>".t("Yhti�- ja tunnussaraketta ei saa muuttaa")." $table_mysql-".t("taulussa")."!</font><br>";
						$vikaa++;
					}

					if (in_array($column, $pakolliset)) {
						// pushataan positio indeksiin, ett� tiedet��n miss� kohtaa avaimet tulevat
						$pos = array_search($column, $taulunotsikot[$taulu]);
						$indeksi[$column] = $pos;
						$tarkea++;
					}

					if (in_array($column, $kielletyt)) {
						// katotaan ettei kiellettyj� sarakkkeita muuteta
						$viesti .= t("Sarake").": $column ".t("on kielletty sarake")." $table_mysql-".t("taulussa")."!<br>";
						$kielletty++;
					}

					if (is_array($wherelliset) and in_array($column, $wherelliset)) {
						// katotaan ett� m��ritellyt where lausekeen ehdot l�ytyv�t
						$pos = array_search($column, $taulunotsikot[$taulu]);
						$indeksi_where[$column] = $pos;
						$wheretarkea++;
					}
				}
			}
			else {
				$vikaa++;
				echo "<font class='error'>".t("Tiedostossa on tyhji� sarakkeiden otsikoita")."!</font><br>";
			}
		}

		// Oli virheellisi� sarakkeita tai pakollisia ei l�ytynyt..
		if ($vikaa != 0 or $tarkea != count($pakolliset) or $postoiminto == 'X' or $kielletty > 0 or (is_array($wherelliset) and $wheretarkea != count($wherelliset))) {

			if ($vikaa != 0) {
				echo "<font class='error'>".t("V��ri� sarakkeita tai yritit muuttaa yhti�/tunnus saraketta")."!</font><br>";
			}

			if ($tarkea != count($pakolliset)) {
				echo "<font class='error'>".t("Pakollisia/t�rkeit� kentti� puuttuu")."! ( ";

				foreach ($pakolliset as $apupako) echo "$apupako ";

				echo " ) $table_mysql-".t("taulusta")."!</font><br>";
			}

			if ($postoiminto == 'X') {
				echo "<font class='error'>".t("Toiminto sarake puuttuu")."!</font><br>";
			}

			if ($kielletty > 0) {
				echo "<font class='error'>".t("Yrit�t p�ivitt�� kiellettyj� sarakkeita")." $table_mysql-".t("taulussa")."!</font><br>$viesti";
			}

			if (is_array($wherelliset) and $wheretarkea != count($wherelliset)) {
				echo "<font class='error'>".t("Sinulta puuttui jokin pakollisista sarakkeista")." (";

				foreach ($wherelliset as $apupako) echo "$apupako ";

				echo ") $table_mysql-".t("taulusta")."!</font><br>";
			}

			echo "<font class='error'>".t("Virheit� l�ytyi. Ei voida jatkaa")."!<br></font>";

			require ("inc/footer.inc");
			exit;
		}

		echo "<br><font class='message'>".t("Tiedosto ok, aloitetaan p�ivitys")." $table_mysql-".t("tauluun")."...<br></font>";
		flush();

		$rivilaskuri = 1;

		for ($eriviindex = 0; $eriviindex < count($rivit); $eriviindex++) {
			$hylkaa    = 0;
			$tila      = "";
			$tee       = "";
			$eilisataeikamuuteta = "";
			$rivilaskuri++;

			//asiakashinta/asiakasalennus spessuja
			$chasiakas_ryhma 	= '';
			$chytunnus 			= '';
			$chryhma 			= '';
			$chtuoteno 			= '';
			$chalkupvm 			= '0000-00-00';
			$chloppupvm 		= '0000-00-00';
			$and 				= '';
			$tpupque 			= '';
			$toimi_liitostunnus = '';

			if ($rivilaskuri % 500 == 0) {
				echo "<font class='message'>K�sitell��n rivi�: $rivilaskuri</font><br>";
				flush();
			}

			if ($eiyhtiota == "") {
				$valinta   = " YHTIO = '$kukarow[yhtio]'";
			}
			elseif ($eiyhtiota == "TRIP") {
				$valinta   = " TUNNUS > 0 ";
			}

			// Rakennetaan rivikohtainen array
			$rivi = array();

			foreach ($rivit[$eriviindex] as $eriv) {
				$rivi[] = $eriv;
			}

			if ($table_mysql == 'tullinimike' and $tulli_ei_kielta != "") {
				$rivi[] = "FI";
			}

			if ($table_mysql == 'tullinimike' and $tulli_ei_toimintoa != "") {
				$rivi[] = "LISAA";
			}

			//Jos eri where-ehto array on m��ritelty
			if (is_array($wherelliset)) {
				$indeksi = array_merge($indeksi, $indeksi_where);
				$indeksi = array_unique($indeksi);
			}

			foreach ($indeksi as $j) {
				if ($taulunotsikot[$taulu][$j] == "TUOTENO") {

					$tuoteno = trim($rivi[$j]);

					$valinta .= " and TUOTENO='$tuoteno'";
				}
				elseif ($table_mysql == 'tullinimike' and strtoupper($taulunotsikot[$taulu][$j]) == "CN") {
					$rivi[$j] = str_replace(' ','',$rivi[$j]);

					if (trim($rivi[$j]) == '') {
						$tila = 'ohita';
					}
				}
				elseif ($table_mysql == 'sanakirja' and $taulunotsikot[$taulu][$j] == "FI") {
					// jos ollaan mulkkaamassa RU ni tehd��n utf-8 -> latin-1 konversio FI kent�ll�
					if (in_array("RU", $taulunotsikot[$taulu])) {
						$rivi[$j] = iconv("UTF-8", "ISO-8859-1", $rivi[$j]);
					}

					$valinta .= " and ".$taulunotsikot[$taulu][$j]."= BINARY '".trim($rivi[$j])."'";
				}
				elseif ($table_mysql == 'tuotepaikat' and $taulunotsikot[$taulu][$j] == "OLETUS") {
					//ei haluta t�t� t�nne
				}
				elseif ($table_mysql == 'asiakas' and strtoupper(trim($rivi[$postoiminto])) == 'LISAA' and $taulunotsikot[$taulu][$j] == "YTUNNUS" and $rivi[$j] == "AUTOM") {

					if ($yhtiorow["asiakasnumeroinnin_aloituskohta"] != "") {
						$apu_asiakasnumero = $yhtiorow["asiakasnumeroinnin_aloituskohta"];
					}
					else {
						$apu_asiakasnumero = 0;
					}

					//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksil�iv�t tiedot on oltava konsernitasolla-yksil�lliset
					if ($tarkyhtio == "") {
						$query = "	SELECT *
									FROM yhtio
									JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
									where konserni = '$yhtiorow[konserni]'
									and synkronoi like '%asiakas%'";
						$vresult = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($vresult) > 0) {
							// haetaan konsernifirmat
							$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
										FROM yhtio
										JOIN yhtion_parametrit ON yhtion_parametrit.yhtio = yhtio.yhtio
										where konserni = '$yhtiorow[konserni]'
										and synkronoi like '%asiakas%'";
							$vresult = mysql_query($query) or pupe_error($query);
							$srowapu = mysql_fetch_array($vresult);
							$tarkyhtio = $srowapu["yhtiot"];
						}
						else {
							$tarkyhtio = "'$kukarow[yhtio]'";
						}
					}

					$query = "	SELECT MAX(asiakasnro+0) asiakasnro
								FROM asiakas USE INDEX (asno_index)
								WHERE yhtio in ($tarkyhtio)
								AND asiakasnro+0 >= $apu_asiakasnumero";
					$vresult = mysql_query($query) or pupe_error($query);
					$vrow = mysql_fetch_assoc($vresult);

					if ($vrow['asiakasnro'] != '') {
						$apu_ytunnus = $vrow['asiakasnro'] + $tarkylisa;
						$tarkylisa++;
					}
					else {
						$apu_ytunnus = $tarkylisa;
						$tarkylisa++;
					}

					// P�ivitet��n generoitu arvo kaikkiin muuttujiin...
					$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apu_ytunnus;

					foreach ($taulunotsikot as $autotaulu => $autojoinit) {
						if (in_array("YTUNNUS", $joinit) and $autotaulu != $taulut[$j] and $taulu == $joinattavat[$autotaulu]["YTUNNUS"]) {
							$taulunrivit[$autotaulu][$eriviindex][array_search("YTUNNUS", $taulunotsikot[$autotaulu])] = $apu_ytunnus;
						}
					}

					$valinta .= " and ".$taulunotsikot[$taulu][$j]."='$apu_ytunnus'";
				}
				else {
					$valinta .= " and ".$taulunotsikot[$taulu][$j]."='".trim($rivi[$j])."'";
				}

				// jos pakollinen tieto puuttuu kokonaan
				if (trim($rivi[$j]) == "" and in_array($taulunotsikot[$taulu][$j], $pakolliset)) {
					$tila = 'ohita';
				}
			}

			// jos ei ole puuttuva tieto etsit��n rivi�
			if ($tila != 'ohita') {

				if (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri")) and (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu]) or (in_array("LIITOSTUNNUS", $taulunotsikot[$taulu]) and $rivi[array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])] == ""))) {

					if ((in_array("YTUNNUS", $taulunotsikot[$taulu]) and ($table_mysql == "yhteyshenkilo" or $table_mysql == "asiakkaan_avainsanat")) or (in_array("ASIAKAS", $taulunotsikot[$taulu]) and $table_mysql == "kalenteri")) {

						if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
							$tpque = "	SELECT tunnus
										from toimi
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."'
										and tyyppi != 'P'";
							$tpres = mysql_query($tpque) or pupe_error($tpque);
						}
						elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
							$tpque = "	SELECT tunnus
										from asiakas
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."'";
							$tpres = mysql_query($tpque) or pupe_error($tpque);
						}
						elseif ($table_mysql == "kalenteri") {
							$tpque = "	SELECT tunnus
										from asiakas
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '".$rivi[array_search("ASIAKAS", $taulunotsikot[$taulu])]."'";
							$tpres = mysql_query($tpque) or pupe_error($tpque);
						}

						if (mysql_num_rows($tpres) == 0) {
							if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
								echo t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("ei l�ydy")."!<br>";
							}
							elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
								echo t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("ei l�ydy")."!<br>";
							}
							else {
								echo t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '".$rivi[array_search("ASIAKAS", $taulunotsikot[$taulu])]."' ".t("ei l�ydy")."!<br>";
							}

							$hylkaa++; // ei p�ivitet� t�t� rivi�
						}
						elseif (mysql_num_rows($tpres) == 1) {
							$tpttrow = mysql_fetch_array($tpres);

							//	Liitet��n pakolliset arvot
							if (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {
								$taulunotsikot[$taulu][] = "LIITOSTUNNUS";
							}

							$rivi[] = $tpttrow["tunnus"];

							$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
						}
						else {

							if ($ytunnustarkkuus == 2) {
								$lasind = count($rivi);

								//	Liitet��n pakolliset arvot
								if (!in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {
									$taulunotsikot[$taulu][] = "LIITOSTUNNUS";
								}

								$pushlask = 1;

								while ($tpttrow = mysql_fetch_array($tpres)) {

									$rivi[$lasind] = $tpttrow["tunnus"];

									if ($pushlask < mysql_num_rows($tpres)) {
										$rivit[] = $rivi;
									}

									$pushlask++;
								}

								$valinta .= " and liitostunnus='$rivi[$lasind]' ";
							}
							else {
								if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
									echo t("Virhe rivill�").": $rivilaskuri ".t("Toimittaja")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella l�ytyy useita toimittajia! Lis�� toimittajan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>";
								}
								elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat") {
									echo t("Virhe rivill�").": $rivilaskuri ".t("Asiakas")." '".$rivi[array_search("YTUNNUS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella l�ytyy useita asiakkaita! Lis�� asiakkaan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>";
								}
								else {
									echo t("Virhe rivill�").": $rivilaskuri ".t("Asiakas")." '".$rivi[array_search("ASIAKAS", $taulunotsikot[$taulu])]."' ".t("Samalla ytunnuksella l�ytyy useita asiakkaita! Lis�� asiakkaan tunnus LIITOSTUNNUS-sarakkeeseen")."!<br>";
								}

								$hylkaa++; // ei p�ivitet� t�t� rivi�
							}
						}
					}
					else {
						echo t("Virhe rivill�").": $rivilaskuri ".t("Rivi� ei voi lis�t� jos ei tiedet� ainakin YTUNNUSTA!")."<br>";
						$hylkaa++;
					}
				}
				elseif (in_array($table_mysql, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri")) and in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {

					if ($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "T" and $table_mysql == "yhteyshenkilo") {
						$tpque = "	SELECT tunnus
									from toimi
									where yhtio	= '$kukarow[yhtio]'
									and tunnus	= '".$rivi[array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])]."'
									and tyyppi != 'P'";
						$tpres = mysql_query($tpque) or pupe_error($tpque);
					}
					elseif (($rivi[array_search("TYYPPI", $taulunotsikot[$taulu])] == "A" and $table_mysql == "yhteyshenkilo") or $table_mysql == "asiakkaan_avainsanat" or $table_mysql == "kalenteri") {
						$tpque = "	SELECT tunnus
									from asiakas
									where yhtio	= '$kukarow[yhtio]'
									and tunnus	= '".$rivi[array_search("LIITOSTUNNUS", $taulunotsikot[$taulu])]."'";
						$tpres = mysql_query($tpque) or pupe_error($tpque);
					}

					if (mysql_num_rows($tpres) != 1) {
						echo t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa/Asiakasta")." '$rivi[$r]' ".t("ei l�ydy! Rivi� ei p�ivitetty/lis�tty")."!<br>";
						$hylkaa++; // ei p�ivitet� t�t� rivi�
					}
					else {
						$tpttrow = mysql_fetch_array($tpres);

						// Lis�t��n ehtoon
						$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
					}
				}

				$query = "	SELECT tunnus
							FROM $table_mysql
							WHERE $valinta";
				$fresult = mysql_query($query) or pupe_error($query);

				if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA' and $table_mysql != $table and mysql_num_rows($fresult) != 0) {
					// joinattaviin tauluhin tehd��n muuta-operaatio jos rivi l�ytyy
					$rivi[$postoiminto] = "MUUTA";
				}
				elseif (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA' and $table_mysql != $table and mysql_num_rows($fresult) == 0) {
					// joinattaviin tauluhin tehd��n lisaa-operaatio jos rivi� ei l�ydy
					$rivi[$postoiminto] = "LISAA";
				}
				elseif (strtoupper(trim($rivi[$postoiminto])) == 'LISAA' and mysql_num_rows($fresult) != 0) {
					if ($table_mysql != 'asiakasalennus' and $table_mysql != 'asiakashinta') {
						echo t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("VIRHE:")." ".t("Rivi on jo olemassa, ei voida perustaa uutta!")."</font> $valinta<br>";
						$tila = 'ohita';
					}
				}
				elseif (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA' and mysql_num_rows($fresult) == 0) {
					if ($table_mysql != 'asiakasalennus' and $table_mysql != 'asiakashinta') {
						echo t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei voida muuttaa, koska sit� ei l�ytynyt!")."</font> $valinta<br>";
						$tila = 'ohita';
					}
				}
				elseif (strtoupper(trim($rivi[$postoiminto])) != 'MUUTA' and strtoupper(trim($rivi[$postoiminto])) != 'LISAA') {
					echo t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Rivi� ei voida k�sitell� koska silt� puuttuu toiminto!")."</font> $valinta<br>";
					$tila = 'ohita';
				}
			}
			else {
				echo t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("Pakollista tietoa puuttuu/tiedot ovat virheelliset!")."</font> $valinta<br>";
			}

			// lis�t��n rivi
			if ($tila != 'ohita') {
				if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
					if ($eiyhtiota == "") {
						$query = "INSERT into $table_mysql SET yhtio='$kukarow[yhtio]', laatija='$kukarow[kuka]', luontiaika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() ";
					}
					elseif ($eiyhtiota == "TRIP") {
						$query = "INSERT into $table_mysql SET laatija='$kukarow[kuka]', luontiaika=now() ";
					}
				}

				if (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
					if ($eiyhtiota == "") {
						$query = "UPDATE $table_mysql SET yhtio='$kukarow[yhtio]', muuttaja='$kukarow[kuka]', muutospvm=now() ";
	      			}
					elseif ($eiyhtiota == "TRIP") {
						$query = "UPDATE $table_mysql SET muuttaja='$kukarow[kuka]', muutospvm=now() ";
	      			}
				}

				foreach ($taulunotsikot[$taulu] as $r => $otsikko) {

					//	N�it� ei koskaan lis�t�
					if (is_array($apu_sarakkeet) and in_array($otsikko, $apu_sarakkeet)) {
						continue;
					}

					if ($r != $postoiminto) {

						// Avainsanojen perheet kuntoon!
						if ($table_mysql == 'avainsana' and strtoupper(trim($rivi[$postoiminto])) == 'LISAA' and $rivi[array_search("PERHE", $taulunotsikot[$taulut[$r]])] == "AUTOM") {

							$mpquery = "SELECT max(perhe)+1 max
										FROM avainsana";
							$vresult = mysql_query($mpquery) or pupe_error($mpquery);
							$vrow = mysql_fetch_assoc($vresult);

							$apu_ytunnus = $vrow['max'] + $tarkylisa;
							$tarkylisa++;

							$j = array_search("PERHE", $taulunotsikot[$taulut[$r]]);

							// P�ivitet��n generoitu arvo kaikkiin muuttujiin...
							$taulunrivit[$taulu][$eriviindex][$j] = $rivit[$eriviindex][$j] = $rivi[$j] = $apu_ytunnus;

							foreach ($taulunotsikot as $autotaulu => $autojoinit) {
								if (in_array("PERHE", $joinit) and $autotaulu != $taulut[$r] and $taulu == $joinattavat[$autotaulu]["PERHE"]) {
									$taulunrivit[$autotaulu][$eriviindex][array_search("PERHE", $taulunotsikot[$autotaulu])] = $apu_ytunnus;
								}
							}
						}

						// K�ytt�jien salasanat kuntoon!
						if ($table_mysql == 'kuka' and $taulunotsikot[$taulu][$r] == "SALASANA" and trim($rivi[$r]) != "") {
							$taulunrivit[$taulu][$eriviindex][$r] = $rivit[$eriviindex][$r] = $rivi[$r] = md5(trim($rivi[$r]));
						}

						$rivi[$r] = trim(addslashes($rivi[$r]));

						if (substr($trows[$otsikko],0,7) == "decimal" or substr($trows[$r],0,4) == "real") {
							//korvataan decimal kenttien pilkut pisteill�...
							$rivi[$r] = str_replace(",", ".", $rivi[$r]);
						}

						if ((int) $tlength[$otsikko] > 0 and strlen($rivi[$r]) > $tlength[$otsikko] and ($table_mysql != "tuotepaikat" and $otsikko != "OLETUS" and $rivi[$r] != 'XVAIHDA')) {
							echo t("Virhe rivill�").": $rivilaskuri <font class='error'>".t("VIRHE").": $otsikko ".t("kent�ss� on liian pitk� tieto")."!</font> $rivi[$r]: ".strlen($rivi[$r])." > ".$tlength[$otsikko]."!<br>";
							$hylkaa++; // ei p�ivitet� t�t� rivi�
						}

						if ($table_mysql == 'tuotepaikat' and $otsikko == 'OLETUS') {
							// $tuoteno pit�s olla jo aktivoitu ylh��ll�
							// haetaan tuotteen varastopaikkainfo
							$tpque = "	SELECT sum(if (oletus='X',1,0)) oletus, sum(if (oletus='X',0,1)) regular
										from tuotepaikat where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
							$tpres = mysql_query($tpque) or pupe_error($tpque);

							if (mysql_num_rows($tpres) == 0) {
								$rivi[$r] = "X"; // jos yht��n varastopaikkaa ei l�ydy, pakotetaan oletus
								echo t("Virhe rivill�").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("ei ole yht��n varastopaikkaa, pakotetaan t�st� oletus").".<br>";
							}
							else {
								$tprow = mysql_fetch_array($tpres);
								if ($rivi[$r] == 'XVAIHDA' and $tprow['oletus'] > 0) {
									//vaihdetaan t�m� oletukseksi
									echo t("Virhe rivill�").": $rivilaskuri ".t("Tuotteelle")." '$tuoteno' ".t("Vaihdetaan annettu paikka oletukseksi").".<br>";
								}
								elseif ($rivi[$r] != '' and $tprow['oletus'] > 0) {
									$rivi[$r] = ""; // t�ll� tuotteella on jo oletuspaikka, nollataan t�m�
									echo t("Virhe rivill�").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("on jo oletuspaikka, ei p�ivitetty oletuspaikkaa")."!<br>";
								}
								elseif ($rivi[$r] == '' and $tprow['oletus'] == 0) {
									$rivi[$r] = "X"; // jos yht��n varastopaikkaa ei l�ydy, pakotetaan oletus
									echo t("Virhe rivill�").": $rivilaskuri ".t("Tuotteella")." '$tuoteno' ".t("ei ole yht��n oletuspaikkaa! T�t� EI PIT�ISI tapahtua! Tehd��n nyt t�st� oletus").".<br>";
								}
							}
						}

						if ($table_mysql == 'tuote' and ($otsikko == 'EPAKURANTTI25PVM' or $otsikko == 'EPAKURANTTI50PVM' or $otsikko == 'EPAKURANTTI75PVM' or $otsikko == 'EPAKURANTTI100PVM')) {

							// $tuoteno pit�s olla jo aktivoitu ylh��ll�
							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI25PVM') {
								$tee = "25paalle";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI50PVM') {
								$tee = "puolipaalle";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI75PVM') {
								$tee = "75paalle";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							if (trim($rivi[$r]) != '' and trim($rivi[$r]) != '0000-00-00' and $otsikko == 'EPAKURANTTI100PVM') {
								$tee = "paalle";
							}
							elseif ($tee == "") {
								$tee = "pois";
							}

							$eilisataeikamuuteta = "joo";
						}

						if ($table_mysql == 'tuote' and ($otsikko == 'KUSTP' or $otsikko == 'KOHDE' or $otsikko == 'PROJEKTI') and $rivi[$r] != "") {
							// Kustannuspaikkarumba t�nnekin
							$ikustp_tsk = $rivi[$r];
							$ikustp_ok  = 0;

							if ($otsikko == "PROJEKTI") $kptyyppi = "P";
							if ($otsikko == "KOHDE")	$kptyyppi = "O";
							if ($otsikko == "KUSTP")	$kptyyppi = "K";

							if ($ikustp_tsk != "") {
								$ikustpq = "SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$kptyyppi' and kaytossa != 'E' and nimi = '$ikustp_tsk'";
								$ikustpres = mysql_query($ikustpq) or pupe_error($ikustpq);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp_ok = $ikustprow["tunnus"];
								}
							}

							if ($ikustp_tsk != "" and $ikustp_ok == 0) {
								$ikustpq = "SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$kptyyppi' and kaytossa != 'E' and koodi = '$ikustp_tsk'";
								$ikustpres = mysql_query($ikustpq) or pupe_error($ikustpq);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp_ok = $ikustprow["tunnus"];
								}
							}

							if (is_numeric($ikustp_tsk) and (int) $ikustp_tsk > 0 and $ikustp_ok == 0) {

								$ikustp_tsk = (int) $ikustp_tsk;

								$ikustpq = "SELECT tunnus
											FROM kustannuspaikka
											WHERE yhtio = '$kukarow[yhtio]' and tyyppi = '$kptyyppi' and kaytossa != 'E' and tunnus = '$ikustp_tsk'";
								$ikustpres = mysql_query($ikustpq) or pupe_error($ikustpq);

								if (mysql_num_rows($ikustpres) == 1) {
									$ikustprow = mysql_fetch_assoc($ikustpres);
									$ikustp_ok = $ikustprow["tunnus"];
								}
							}

							if ($ikustp_ok > 0) {
								$rivi[$r] = $ikustp_ok;
								$rivit[$eriviindex][$r]	= $ikustp_ok;
							}
						}

						// tehd��n riville oikeellisuustsekkej�
						if ($table_mysql == 'sanakirja' and $otsikko == 'FI') {
							// jos ollaan mulkkaamassa RU ni tehd��n utf-8 -> latin-1 konversio FI kent�ll�
							 if (in_array("RU", $taulunotsikot[$taulu])) {
								$rivi[$r] = iconv("UTF-8", "ISO-8859-1", $rivi[$r]);
							}
						}

						// tehd��n riville oikeellisuustsekkej�
						if ($table_mysql == 'tuotteen_toimittajat' and $otsikko == 'TOIMITTAJA' and !in_array("LIITOSTUNNUS", $taulunotsikot[$taulu])) {

							$tpque = "	SELECT tunnus
										from toimi
										where yhtio	= '$kukarow[yhtio]'
										and ytunnus	= '$rivi[$r]'
										and tyyppi != 'P'";
							$tpres = mysql_query($tpque) or pupe_error($tpque);

							if (mysql_num_rows($tpres) != 1) {
								echo t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa")." '$rivi[$r]' ".t("ei l�ydy! Tai samalla ytunnuksella l�ytyy useita toimittajia! Lis�� toimittajan tunnus LIITOSTUNNUS-sarakkeeseen. Rivi� ei p�ivitetty/lis�tty")."! ".t("TUOTENO")." = $tuoteno<br>";
								$hylkaa++; // ei p�ivitet� t�t� rivi�
							}
							else {
								$tpttrow = mysql_fetch_array($tpres);

								// Tarvitaan tarkista.inc failissa
								$toimi_liitostunnus = $tpttrow["tunnus"];

								$query .= ", liitostunnus='$tpttrow[tunnus]' ";
								$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
							}
						}
						elseif ($table_mysql == 'tuotteen_toimittajat' and $otsikko == 'LIITOSTUNNUS') {
							$tpque = "	SELECT tunnus
										from toimi
										where yhtio	= '$kukarow[yhtio]'
										and tunnus	= '$rivi[$r]'
										and tyyppi != 'P'";
							$tpres = mysql_query($tpque) or pupe_error($tpque);

							if (mysql_num_rows($tpres) != 1) {
								echo t("Virhe rivill�").": $rivilaskuri ".t("Toimittajaa")." '$rivi[$r]' ".t("ei l�ydy! Rivi� ei p�ivitetty/lis�tty")."! ".t("TUOTENO")." = $tuoteno<br>";
								$hylkaa++; // ei p�ivitet� t�t� rivi�
							}
							else {
								$tpttrow = mysql_fetch_array($tpres);

								// Tarvitaan tarkista.inc failissa
								$toimi_liitostunnus = $tpttrow["tunnus"];
								$valinta .= " and liitostunnus='$tpttrow[tunnus]' ";
							}
						}

						//tarkistetaan asiakasalennus ja asiakashinta juttuja
						if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') {
							if ($otsikko == 'RYHMA' and $rivi[$r] != '') {
								$chryhma = $rivi[$r];
							}

							if ($otsikko == 'TUOTENO' and $rivi[$r] != '') {
								$chtuoteno = $rivi[$r];
							}

							if ($otsikko == 'ASIAKAS_RYHMA' and $rivi[$r] != '') {
								$chasiakas_ryhma = $rivi[$r];
							}

							if ($otsikko == 'YTUNNUS' and $rivi[$r] != '') {
								$chytunnus = $rivi[$r];
							}

							if ($otsikko == 'ALKUPVM' and trim($rivi[$r]) != '') {
								$chalkupvm = $rivi[$r];
							}

							if ($otsikko == 'LOPPUPVM' and trim($rivi[$r]) != '') {
								$chloppupvm = $rivi[$r];
							}
						}

						//tarkistetaan kuka juttuja
						if ($table_mysql == 'kuka') {
							if ($otsikko == 'SALASANA' and $rivi[$r] != '') {
								$rivi[$r] = md5(trim($rivi[$r]));
							}

							if ($otsikko == 'OLETUS_ASIAKAS' and $rivi[$r] != '') {
								$xquery = "	SELECT tunnus
											FROM asiakas
											WHERE yhtio='$kukarow[yhtio]' and tunnus = '$rivi[$r]'";
								$xresult = mysql_query($xquery) or pupe_error($xquery);

								if (mysql_num_rows($xresult) == 0) {

									$x2query = "SELECT tunnus
												FROM asiakas
												WHERE yhtio='$kukarow[yhtio]' and ytunnus = '$rivi[$r]'";
									$x2result = mysql_query($x2query) or pupe_error($x2query);

									if (mysql_num_rows($x2result) == 0) {
										echo t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '$rivi[$r]' ".t("ei l�ydy! Rivi� ei p�ivitetty/lis�tty")."! $otsikko = $rivi[$r]<br>";
										$hylkaa++; // ei p�ivitet� t�t� rivi�
									}
									elseif (mysql_num_rows($x2result) > 1) {
										echo t("Virhe rivill�").": $rivilaskuri ".t("Asiakasta")." '$rivi[$r]' ".t("l�ytyi monia! Rivi� ei p�ivitetty/lis�tty")."! $otsikko = $rivi[$r]<br>";
										$hylkaa++; // ei p�ivitet� t�t� rivi�
									}
									else {
										$x2row = mysql_fetch_array($x2result);
										$rivi[$r] = $x2row['tunnus'];
									}
								}
								else {
									$xrow = mysql_fetch_array($xresult);
									$rivi[$r] = $xrow['tunnus'];
								}
							}
						}

						//muutetaan rivi�, silloin ei saa p�ivitt�� pakollisia kentti�
						if (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA' and (!in_array($otsikko, $pakolliset) or $table_mysql == 'asiakashinta' or $table_mysql == 'asiakasalennus' or ($table_mysql == "tuotepaikat" and $otsikko == "OLETUS" and $rivi[$r] == 'XVAIHDA'))) {
							///* T�ss� on kaikki oikeellisuuscheckit *///
							if ($table_mysql == 'tuotepaikat' and $otsikko == 'SALDO') {
								if ($rivi[$r] != 0 and $rivi[$r] != '') {
									$query .= ", $otsikko='$rivi[$r]' ";
								}
								elseif ($rivi[$r] == 0) {
									echo t("Virhe rivill�").": $rivilaskuri ".t("Saldoa ei saa nollata!")."<br>";
								}
							}
							elseif ($table_mysql == 'asiakashinta' and $otsikko == 'HINTA') {
								if ($rivi[$r] != 0 and $rivi[$r] != '') {
									$query .= ", $otsikko = '$rivi[$r]' ";
								}
								elseif ($rivi[$r] == 0) {
									echo t("Virhe rivill�").": $rivilaskuri ".t("Hintaa ei saa nollata!")."<br>";
								}
							}
							elseif ($table_mysql == 'avainsana' and $otsikko == 'SELITE') {
								if ($rivi[$r] != 0 and $rivi[$r] != '') {
									$query .= ", $otsikko = '$rivi[$r]' ";
								}
								elseif ($rivi[$r] == 0) {
									echo t("Virhe rivill�").": $rivilaskuri ".t("Selite ei saa olla tyhj�!")."<br>";
								}
							}
							elseif ($table_mysql=='tuotepaikat' and $otsikko == 'OLETUS' and $rivi[$r] == 'XVAIHDA') {
								//vaihdetaan t�m� oletukseksi
								$rivi[$r] = "X"; // pakotetaan oletus

								$tpupque = "UPDATE tuotepaikat SET oletus = '' where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";

								$query .= ", $otsikko = '$rivi[$r]' ";
							}
							elseif ($table_mysql=='tuotepaikat' and $otsikko == 'OLETUS') {
								//echo t("Virhe rivill�").": $rivilaskuri Oletusta ei voi muuttaa!<br>";
							}
							else {
								if ($eilisataeikamuuteta == "") {
									$query .= ", $otsikko = '$rivi[$r]' ";
								}
					  		}
						}

						//lis�t��n rivi
						if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
							if ($table_mysql == 'tuotepaikat' and $otsikko == 'OLETUS' and $rivi[$r] == 'XVAIHDA') {
								//vaihdetaan t�m� oletukseksi
								$rivi[$r] = "X"; // pakotetaan oletus

								$tpupque = "UPDATE tuotepaikat SET oletus = '' where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";

								$query .= ", $otsikko = '$rivi[$r]' ";
							}
							elseif ($eilisataeikamuuteta == "") {
								$query .= ", $otsikko = '$rivi[$r]' ";
							}
						}
					}
				}

				//tarkistetaan asiakasalennus ja asiakashinta keisseiss� onko t�llanen rivi jo olemassa
				if ($hylkaa == 0 and ($chasiakas_ryhma != '' or $chytunnus != '') and ($chryhma != '' or $chtuoteno != '') and ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta')) {
					if ($chasiakas_ryhma != '') {
						$and .= " and asiakas_ryhma = '$chasiakas_ryhma'";
					}
					if ($chytunnus != '') {
						$and .= " and ytunnus = '$chytunnus'";
					}
					if ($chryhma != '') {
						$and .= " and ryhma = '$chryhma'";
					}
					if ($chtuoteno != '') {
						$and .= " and tuoteno = '$chtuoteno'";
					}

					$and .= " and alkupvm = '$chalkupvm' and loppupvm = '$chloppupvm'";
				}

				if (strtoupper(trim($rivi[$postoiminto])) == 'MUUTA') {
					if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') {
						$query .= " WHERE yhtio = '$kukarow[yhtio]'";
						$query .= $and;
					}
					else {
						$query .= " WHERE ".$valinta;
					}

					$query .= " ORDER BY tunnus";
				}

				//	Tarkastetaan tarkistarivi.incia vastaan..
				//	Generoidaan oikeat arrayt
				$errori = "";
				$t 		= array();
				$virhe 	= array();
				$poistolukko = "LUEDATA";

				//	Otetaan talteen query..
				$lue_data_query = $query;

				$tarq = "	SELECT *
							FROM $table_mysql";
				if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') {
					$tarq .= " WHERE yhtio = '$kukarow[yhtio]'";
					$tarq .= $and;
				}
				else {
					$tarq .= " WHERE ".$valinta;
				}
				$result = mysql_query($tarq) or pupe_error($tarq);
				$tarkrow = mysql_fetch_array($result);
				$tunnus = $tarkrow["tunnus"];

				// Tehd��n oikeellisuustsekit
				for ($i=1; $i < mysql_num_fields($result); $i++) {

					// Tarkistetaan saako k�ytt�j� p�ivitt�� t�t� kentt��
					$Lindexi = array_search(strtoupper(mysql_field_name($result, $i)), $taulunotsikot[$taulu]);

					if (strtoupper(mysql_field_name($result, $i)) == 'TUNNUS') {
						$tassafailissa = TRUE;
					}
					elseif ($Lindexi !== FALSE and array_key_exists($Lindexi, $rivit[$eriviindex])) {
						$t[$i] = $rivit[$eriviindex][$Lindexi];

						// T�m� rivi on exceliss�
						$tassafailissa = TRUE;
					}
					else {
						$t[$i] = $tarkrow[mysql_field_name($result, $i)];

						// T�m� rivi ei oo exceliss�
						$tassafailissa = FALSE;
					}

					$funktio = $table_mysql."tarkista";

					if (!function_exists($funktio)) {
						@include("inc/$funktio.inc");
					}

					unset($virhe);

					if (function_exists($funktio)) {
						$funktio($t, $i, $result, $tunnus, &$virhe, $tarkrow);
					}

					// Ignoorataan virhe jos se ei koske t�ss� failissa olutta saraketta
					if ($tassafailissa and $virhe[$i] != "") {
						switch ($table_mysql) {
							case "tuote":
								$virheApu = t("Tuote")." ".$tarkrow["tuoteno"].": ";
								break;
							default:
								$virheApu = "";
						}
						echo t("Virhe rivill�").": $rivilaskuri <font class='error'>$virheApu".mysql_field_name($result, $i).": ".$virhe[$i]." (".$t[$i].")</font><br>";
						$errori = 1;
					}
				}

				if ($errori != "") {
					$hylkaa++;
				}

				//	Palautetaan vanha query..
				$query = $lue_data_query;

				if ($hylkaa == 0) {

					// Haetaan rivi niin kuin se oli ennen muutosta
					$syncquery = "	SELECT *
									FROM $table_mysql";

					if ($table_mysql == 'asiakasalennus' or $table_mysql == 'asiakashinta') {
						$syncquery .= " WHERE yhtio = '$kukarow[yhtio]'";
						$syncquery .= $and;
					}
					else {
						$syncquery .= " WHERE ".$valinta;
					}
					$syncres = mysql_query($syncquery) or pupe_error($syncquery);
					$syncrow = mysql_fetch_array($syncres);

					// tuotepaikkojen oletustyhjennysquery uskalletaan ajaa vasta t�ss�
					if ($tpupque != '') {
						$tpupres = mysql_query($tpupque) or pupe_error($tpupque);
					}

					$tpupque = "";

					// Itse lue_datan p�ivitysquery
					$iresult = mysql_query($query) or pupe_error($query);

					// Synkronoidaan
					if (strtoupper(trim($rivi[$postoiminto])) == 'LISAA') {
						$tunnus = mysql_insert_id();
					}
					else {
						$tunnus = $syncrow["tunnus"];
					}

					synkronoi($kukarow["yhtio"], $table_mysql, $tunnus, $syncrow, "");

					// tehd��n ep�kunrattijutut
					if ($tee == "paalle" or $tee == "25paalle" or $tee == "puolipaalle" or $tee == "75paalle" or $tee == "pois") {
						require("epakurantti.inc");
					}

					$lask++;
				}
			}
		}

		echo t("P�ivitettiin")." $lask ".t("rivi�")."!<br><br>";
	}
}
else {

	$sel[$table] = "SELECTED";

	echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
			<input type='hidden' name='tee' value='file'>
			<table>
			<tr>
				<td>".t("Valitse tietokannan taulu").":</td>
				<td><select name='table' onchange='submit();'>
					<option value='asiakas' $sel[asiakas]>".t("Asiakas")."</option>
					<option value='asiakasalennus' $sel[asiakasalennus]>".t("Asiakasalennukset")."</option>
					<option value='asiakashinta' $sel[asiakashinta]>".t("Asiakashinnat")."</option>
					<option value='asiakaskommentti' $sel[asiakaskommentti]>".t("Asiakaskommentit")."</option>
					<option value='asiakkaan_avainsanat' $sel[asiakkaan_avainsanat]>".t("Asiakkaan avainsanat")."</option>
					<option value='abc_parametrit' $sel[abc_parametrit]>".t("ABC-parametrit")."</option>
					<option value='autodata' $sel[autodata]>".t("Autodatatiedot")."</option>
					<option value='autodata_tuote' $sel[autodata_tuote]>".t("Autodata tuotetiedot")."</option>
					<option value='avainsana' $sel[avainsana]>".t("Avainsanat")."</option>
					<option value='budjetti' $sel[budjetti]>".t("Budjetti")."</option>
					<option value='etaisyydet' $sel[etaisyydet]>".t("Et�isyydet varastosta")."</option>
					<option value='hinnasto' $sel[hinnasto]>".t("Hinnasto")."</option>
					<option value='kalenteri' $sel[kalenteri]>".t("Kalenteritietoja")."</option>
					<option value='liitetiedostot' $sel[liitetiedostot]>".t("Liitetiedostot")."</option>
					<option value='maksuehto' $sel[maksuehto]>".t("Maksuehto")."</option>
					<option value='perusalennus' $sel[perusalennus]>".t("Perusalennukset")."</option>
					<option value='rahtimaksut' $sel[rahtimaksut]>".t("Rahtimaksut")."</option>
					<option value='rahtisopimukset' $sel[rahtisopimukset]>".t("Rahtisopimukset")."</option>
					<option value='rekisteritiedot' $sel[rekisteritiedot]>".t("Rekisteritiedot")."</option>
					<option value='sanakirja' $sel[sanakirja]>".t("Sanakirja")."</option>
					<option value='sarjanumeron_lisatiedot' $sel[sarjanumeron_lisatiedot]>".t("Sarjanumeron lis�tiedot")."</option>
					<option value='taso' $sel[taso]>".t("Tilikartan rakenne")."</option>
					<option value='tili' $sel[tili]>".t("Tilikartta")."</option>
					<option value='todo' $sel[todo]>".t("Todo-lista")."</option>
					<option value='toimi' $sel[toimi]>".t("Toimittaja")."</option>
					<option value='toimitustapa' $sel[toimitustapa]>".t("Toimitustapoja")."</option>
					<option value='tullinimike' $sel[tullinimike]>".t("Tullinimikeet")."</option>
					<option value='tuote' $sel[tuote]>".t("Tuote")."</option>
					<option value='tuotepaikat' $sel[tuotepaikat]>".t("Tuotepaikat")."</option>
					<option value='tuoteperhe' $sel[tuoteperhe]>".t("Tuoteperheet")."</option>
					<option value='tuotteen_alv' $sel[tuotteen_alv]>".t("Tuotteiden ulkomaan ALV")."</option>
					<option value='tuotteen_avainsanat' $sel[tuotteen_avainsanat]>".t("Tuotteen avainsanat")."</option>
					<option value='tuotteen_orginaalit' $sel[tuotteen_orginaalit]>".t("Tuotteiden originaalit")."</option>
					<option value='tuotteen_toimittajat' $sel[tuotteen_toimittajat]>".t("Tuotteen toimittajat")."</option>
					<option value='yhteensopivuus_auto' $sel[yhteensopivuus_auto]>".t("Yhteensopivuus automallit")."</option>
					<option value='yhteensopivuus_mp' $sel[yhteensopivuus_mp]>".t("Yhteensopivuus mp-mallit")."</option>
					<option value='yhteensopivuus_tuote' $sel[yhteensopivuus_tuote]>".t("Yhteensopivuus tuotteet")."</option>
					<option value='yhteyshenkilo' $sel[yhteyshenkilo]>".t("Yhteyshenkil�t")."</option>
					<option value='kuka' $sel[kuka]>".t("K�ytt�j�tietoja")."</option>
					</select></td>
			</tr>";

		if (in_array($table, array("yhteyshenkilo", "asiakkaan_avainsanat", "kalenteri"))) {
			echo "<tr><td>".t("Ytunnus-tarkkuus").":</td>
					<td><select name='ytunnustarkkuus'>
					<option value=''>".t("P�ivitet��n vain, jos Ytunnuksella l�ytyy yksi rivi")."</option>
					<option value='2'>".t("P�ivitet��n kaikki sy�tetyll� Ytunnuksella l�ytyv�t asiakkaat")."</option>
					</select></td>
			</tr>";
		}

		echo "	<tr><td>".t("Valitse tiedosto").":</td>
				<td><input name='userfile' type='file'></td>
				<td class='back'><input type='submit' value='".t("L�het�")."'></td>
			</tr>

			</table>
			</form>";
}

require ("inc/footer.inc");

?>