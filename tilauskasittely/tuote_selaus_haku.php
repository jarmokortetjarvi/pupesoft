<?php

	if (@include("../inc/parametrit.inc"));
	elseif (@include("parametrit.inc"));
	else exit;
	
	if (@include("verkkokauppa/ostoskori.inc")) {
		$kori_polku = "../verkkokauppa/ostoskori.php";
	}
	elseif (@include("ostoskori.inc")) {
		$kori_polku = "ostoskori.php";

		if ($tultiin == "futur") {
			$kori_polku .= "?ostoskori=".$ostoskori."&tultiin=".$tultiin;
		}
	}
	else exit;

	// Liitetiedostot popup
	if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
		liite_popup("AK", $tuotetunnus, $width, $height);
	}
	else {
		liite_popup("JS");
	}

	if (function_exists("js_popup")) {
		echo js_popup(-100);
	}

	echo "<SCRIPT type='text/javascript'>
			<!--
				function sarjanumeronlisatiedot_popup(tunnus) {
					window.open('$PHP_SELF?tunnus='+tunnus+'&toiminto=sarjanumeronlisatiedot_popup', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=0,top=0,width=800,height=600');
				}
			//-->
			</SCRIPT>";

	if (isset($toiminto) and $toiminto == "sarjanumeronlisatiedot_popup") {
		@include('sarjanumeron_lisatiedot_popup.inc');

		if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
			$hinnat = 'MY';
		}
		else {
			$hinnat = '';
		}

		list($divitx, , , ,) = sarjanumeronlisatiedot_popup($tunnus, '', '', $hinnat, '');
		echo "$divitx";
		exit;
	}

	if ($verkkokauppa == "") {
		echo "<font class='head'>".t("Etsi ja selaa tuotteita").":</font><hr>";
	}

	if (!isset($toim_kutsu)) {
		$toim_kutsu = '';
	}

	if ($toim_kutsu == "") {
		$toim_kutsu = "RIVISYOTTO";
	}

	$query    = "SELECT * from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result   = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_assoc($result);

	if ($verkkokauppa == "") {
		if (!isset($ostoskori)) {
			$ostoskori = '';
		}

		if (is_numeric($ostoskori)) {
			echo "<table><tr><td class='back'>";
			echo "	<form method='post' action='$kori_polku'>
					<input type='hidden' name='tee' value='poistakori'>
					<input type='hidden' name='ostoskori' value='$ostoskori'>
					<input type='hidden' name='pyytaja' value='haejaselaa'>
					<input type='submit' value='".t("Tyhjenn� ostoskori")."'>
					</form>";
			echo "</td><td class='back'>";
			echo "	<form method='post' action='$kori_polku'>
					<input type='hidden' name='tee' value=''>
					<input type='hidden' name='ostoskori' value='$ostoskori'>
					<input type='hidden' name='pyytaja' value='haejaselaa'>
					<input type='submit' value='".t("N�yt� ostoskori")."'>
					</form>";
			echo "</td></tr></table>";
		}
		elseif ($kukarow["kuka"] != "" and $laskurow["tila"] == "O") {

			echo "	<form method='post' action='".$palvelin2."tilauskasittely/tilaus_osto.php'>
					<input type='hidden' name='aktivoinnista' value='true'>
					<input type='hidden' name='tee' value='AKTIVOI'>
					<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
					<input type='submit' value='".t("Takaisin tilaukselle")."'>
					</form><br><br>";
		}
		elseif ($kukarow["kuka"] != "" and $laskurow["tila"] != "" and $toim_kutsu != "") {

			if ($kukarow["extranet"] != "") {
				$toim_kutsu 	 = "EXTRANET";
				$tilauskasittely = "";
			}
			else {
				$tilauskasittely = "tilauskasittely/";
			}

			echo "	<form method='post' action='".$palvelin2.$tilauskasittely."tilaus_myynti.php'>
					<input type='hidden' name='toim' value='$toim_kutsu'>
					<input type='hidden' name='aktivoinnista' value='true'>
					<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
					<input type='submit' value='".t("Takaisin tilaukselle")."'>
					</form><br><br>";
		}
	}

	if (!isset($tee)) {
		$tee = '';
	}

	// Tarkistetaan tilausrivi
	if (($tee == 'TI' or is_numeric($ostoskori)) and isset($tilkpl)) {

		if (is_numeric($ostoskori)) {
			$kori = check_ostoskori($ostoskori,$kukarow["oletus_asiakas"]);
			$kukarow["kesken"] = $kori["tunnus"];
		}

		// haetaan avoimen tilauksen otsikko
		if ($kukarow["kesken"] != 0) {
			$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$laskures = mysql_query($query);
		}
		else {
			// Luodaan uusi myyntitilausotsikko
			if ($kukarow["extranet"] == "") {
				require_once("tilauskasittely/luo_myyntitilausotsikko.inc");
				$tilausnumero = luo_myyntitilausotsikko(0);
				$kukarow["kesken"] = $tilausnumero;
				$kaytiin_otsikolla = "NOJOO!";
			}
			else {
				require_once("luo_myyntitilausotsikko.inc");
				$tilausnumero = luo_myyntitilausotsikko($kukarow["oletus_asiakas"]);
				$kukarow["kesken"] = $tilausnumero;
				$kaytiin_otsikolla = "NOJOO!";
			}

			// haetaan avoimen tilauksen otsikko
			$query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
			$laskures = mysql_query($query);
		}

		if ($kukarow["kesken"] != 0 and $laskures != '') {
			// tilauksen tiedot
			$laskurow = mysql_fetch_assoc($laskures);
		}

		if (is_numeric($ostoskori)) {
			echo "<font class='message'>Lis�t��n tuotteita ostoskoriin $ostoskori.</font><br>";
		}
		else {
			echo "<font class='message'>Lis�t��n tuotteita tilaukselle $kukarow[kesken].</font><br>";
		}

		// K�yd��n l�pi formin kaikki rivit
		foreach ($tilkpl as $yht_i => $kpl) {

			$kpl = str_replace(',', '.', $kpl);

			if ((float) $kpl > 0 or ($kukarow["extranet"] == "" and (float) $kpl < 0)) {

				// haetaan tuotteen tiedot
				$query    = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
				$tuoteres = mysql_query($query);

				if (mysql_num_rows($tuoteres) == 0) {
					echo "<font class='error'>Tuotetta $tiltuoteno[$yht_i] ei l�ydy!</font><br>";
				}
				else {
					// tuote l�ytyi ok, lis�t��n rivi
					$trow = mysql_fetch_assoc($tuoteres);

					$ytunnus         = $laskurow["ytunnus"];
					$kpl             = (float) $kpl;
					$kpl_echo 		 = (float) $kpl;
					$tuoteno         = $trow["tuoteno"];
					$toimaika 	     = $laskurow["toimaika"];
					$kerayspvm	     = $laskurow["kerayspvm"];
					$hinta 		     = "";
					$netto 		     = "";
					$ale 		     = "";
					$alv		     = "";
					$var			 = "";
					$varasto 	     = $laskurow["varasto"];
					$rivitunnus		 = "";
					$korvaavakielto	 = "";
					$jtkielto 		 = $laskurow['jtkielto'];
					$varataan_saldoa = "";
					$myy_sarjatunnus = $tilsarjatunnus[$yht_i];

					if ($tilpaikka[$yht_i] != '') {
						$paikka	= $tilpaikka[$yht_i];
					}
					else {
						$paikka	= "";
					}

					if ($verkkokauppa != "" and $verkkokauppa_saldotsk === FALSE) {
						$varataan_saldoa = "EI";
					}

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

					echo "<font class='message'>".t("Lis�ttiin")." $kpl_echo ".t_avainsana("Y", "", " and avainsana.selite='$trow[yksikko]'", "", "", "selite")." ".t("tuotetta")." $tiltuoteno[$yht_i].</font><br>";

					//Hanskataan sarjanumerollisten tuotteiden lis�varusteet
					if ($tilsarjatunnus[$yht_i] > 0 and $lisatty_tun > 0) {
						require("sarjanumeron_lisavarlisays.inc");

						lisavarlisays($tilsarjatunnus[$yht_i], $lisatty_tun);
					}
				} // tuote ok else
			} // end kpl > 0
		} // end foreach

		echo "<br>";

		$trow			 = "";
		$ytunnus         = "";
		$kpl             = "";
		$tuoteno         = "";
		$toimaika 	     = "";
		$kerayspvm	     = "";
		$hinta 		     = "";
		$netto 		     = "";
		$ale 		     = "";
		$alv		     = "";
		$var			 = "";
		$varasto 	     = "";
		$rivitunnus		 = "";
		$korvaavakielto	 = "";
		$varataan_saldoa = "";
		$myy_sarjatunnus = "";
		$paikka			 = "";
		$tee 			 = "";
	}

	$jarjestys = "tuote.tuoteno";

	$lisa  				= "";
	$ulisa 				= "";
	$toimtuotteet 		= "";
	$origtuotteet 		= "";
	$poislisa_mulsel 	= "";

	if (!isset($ojarj)) {
		$ojarj = '';
	}

	if (strlen($ojarj) > 0) {
		$ojarj = trim(mysql_real_escape_string($ojarj));

		if ($ojarj == 'tuoteno') {
			$jarjestys = 'tuote.tuoteno';
		}
		elseif ($ojarj == 'toim_tuoteno') {
			$jarjestys = 'tuote.tuoteno';
		}
		elseif ($ojarj == 'nimitys') {
			$jarjestys = 'tuote.nimitys';
		}
		elseif ($ojarj == 'osasto') {
			$jarjestys = 'tuote.osasto';
		}
		elseif ($ojarj == 'try') {
			$jarjestys = 'tuote.try';
		}
		elseif ($ojarj == 'hinta') {
			$jarjestys = 'tuote.myyntihinta';
		}
		elseif ($ojarj == 'nettohinta') {
			$jarjestys = 'tuote.nettohinta';
		}
		elseif ($ojarj == 'aleryhma') {
			$jarjestys = 'tuote.aleryhma';
		}
		elseif ($ojarj == 'status') {
			$jarjestys = 'tuote.status';
		}
		else {
			$jarjestys = 'tuote.tuoteno';
		}
	}

	if (!isset($poistetut)) {
		$poistetut = '';
	}

	if ($poistetut != "") {

		$poischeck = "CHECKED";
		$ulisa .= "&poistetut=checked";

		if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
			// N�ytet��n vain poistettuja tuotteita
			$poislisa  			= " and tuote.status in ('P','X')
									and (SELECT sum(saldo)
										 FROM tuotepaikat
										 JOIN varastopaikat on (varastopaikat.yhtio=tuotepaikat.yhtio
										 and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										 and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										 and varastopaikat.tyyppi = '')
										 WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0 ";

			$poislisa_mulsel	= " and tuote.status in ('P','X') ";
		}
		else {
			$poislisa  		 	= "";
			//$poislisa_mulsel	= "";
		}
	}
	else {
		$poislisa  = " and (tuote.status not in ('P','X')
						or (SELECT sum(saldo) FROM tuotepaikat WHERE tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno and tuotepaikat.saldo > 0) > 0) ";
		//$poislisa_mulsel  = " and tuote.status not in ('P','X') ";
		$poischeck = "";
	}

	if (!isset($lisatiedot)) {
		$lisatiedot = '';
	}

	if ($lisatiedot != "") {
		$lisacheck = "CHECKED";
		$ulisa .= "&lisatiedot=checked";
	}
	else {
		$lisacheck = "";
	}

	if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
		// K�ytet��n alempana
		$query = "SELECT * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
		$oleasres = mysql_query($query) or pupe_error($query);
		$oleasrow = mysql_fetch_assoc($oleasres);

		$query = "SELECT * from valuu where yhtio='$kukarow[yhtio]' and nimi='$oleasrow[valkoodi]'";
		$olhires = mysql_query($query) or pupe_error($query);
		$olhirow = mysql_fetch_assoc($olhires);

		if ($verkkokauppa != "") {

			if ($kukarow["kuka"] == "www") {
				$extra_poislisa = " and tuote.hinnastoon = 'W' ";
			}
			else {
				$extra_poislisa = " and tuote.hinnastoon in ('W','V') ";
			}

			$avainlisa = " and avainsana.nakyvyys = '' ";
		}
		else {
			$extra_poislisa = " and tuote.hinnastoon != 'E' ";
			$avainlisa = " and avainsana.jarjestys < 10000 ";
		}
	}
	else {
		$extra_poislisa = "";
		$avainlisa = "";
	}

	if (!isset($nimitys)) {
		$nimitys = '';
	}

	if (trim($nimitys) != '') {
		$nimitys = mysql_real_escape_string(trim($nimitys));
		$lisa .= " and tuote.nimitys like '%$nimitys%' ";
		$ulisa .= "&nimitys=$nimitys";
	}

	if (!isset($tuotenumero)) {
		$tuotenumero = '';
	}

	if (trim($tuotenumero) != '') {
		$tuotenumero = mysql_real_escape_string(trim($tuotenumero));
		$lisa .= " and tuote.tuoteno like '%$tuotenumero%' ";
		$ulisa .= "&tuotenumero=$tuotenumero";
	}

	if (!isset($toim_tuoteno)) {
		$toim_tuoteno = '';
	}

	if (trim($toim_tuoteno) != '') {
		$toim_tuoteno = mysql_real_escape_string(trim($toim_tuoteno));

		//Otetaan konserniyhti�t hanskaan
		$query	= "	SELECT distinct tuoteno
					FROM tuotteen_toimittajat
					WHERE yhtio = '$kukarow[yhtio]'
					and toim_tuoteno like '%".$toim_tuoteno."%'
					LIMIT 500";
		$pres = mysql_query($query) or pupe_error($query);

		while($prow = mysql_fetch_assoc($pres)) {
			$toimtuotteet .= "'".$prow["tuoteno"]."',";
		}

		$toimtuotteet = substr($toimtuotteet, 0, -1);

		if ($toimtuotteet != "") {
			$lisa .= " and tuote.tuoteno in ($toimtuotteet) ";
		}

		$ulisa .= "&toim_tuoteno=$toim_tuoteno";
	}

	if (!isset($alkuperaisnumero)) {
		$alkuperaisnumero = '';
	}

	if (trim($alkuperaisnumero) != '') {
		$alkuperaisnumero = mysql_real_escape_string(trim($alkuperaisnumero));

		$query	= "	SELECT distinct tuoteno
					FROM tuotteen_orginaalit
					WHERE yhtio = '$kukarow[yhtio]'
					and orig_tuoteno like '%$alkuperaisnumero%'
					LIMIT 500";
		$pres = mysql_query($query) or pupe_error($query);

		while($prow = mysql_fetch_assoc($pres)) {
			$origtuotteet .= "'".$prow["tuoteno"]."',";
		}

		$origtuotteet = substr($origtuotteet, 0, -1);

		if ($origtuotteet != "") {
			$lisa .= " and tuote.tuoteno in ($origtuotteet) ";
		}

		$ulisa .= "&alkuperaisnumero=$alkuperaisnumero";
	}

	// vientikieltok�sittely:
	// +maa tarkoittaa ett� myynti on kielletty t�h�n maahan ja sallittu kaikkiin muihin
	// -maa tarkoittaa ett� ainoastaan t�h�n maahan saa myyd�
	// eli n�ytet��n vaan tuotteet jossa vienti kent�ss� on tyhj�� tai -maa.. ja se ei saa olla +maa
	$kieltolisa = "";
	unset($vierow);

	if ($kukarow["kesken"] > 0) {
		$query  = "	SELECT if (toim_maa != '', toim_maa, maa) maa
					FROM lasku
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus  = '$kukarow[kesken]'";
		$vieres = mysql_query($query) or pupe_error($query);
		$vierow = mysql_fetch_assoc($vieres);
	}
	elseif ($verkkokauppa != "") {
		$vierow = array();

		if ($maa != "") {
			$vierow["maa"] = $maa;
		}
		else {
			$vierow["maa"] = $yhtiorow["maa"];
		}
	}
	elseif ($kukarow["extranet"] != "") {
		$query  = "	SELECT if (toim_maa != '', toim_maa, maa) maa
					FROM asiakas
					WHERE yhtio	= '$kukarow[yhtio]'
					and tunnus  = '$kukarow[oletus_asiakas]'";
		$vieres = mysql_query($query) or pupe_error($query);
		$vierow = mysql_fetch_assoc($vieres);
	}

	if (isset($vierow) and $vierow["maa"] != "") {
		$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
	}

	if (file_exists('sarjanumeron_lisatiedot_popup.inc')) {
		require("sarjanumeron_lisatiedot_popup.inc");
	}

	$orginaaalit = FALSE;

	if (table_exists("tuotteen_orginaalit")) {
		$query	= "	SELECT tunnus
					FROM tuotteen_orginaalit
					WHERE yhtio = '$kukarow[yhtio]'
					LIMIT 1";
		$orginaaleja_res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($orginaaleja_res) > 0) {
			$orginaaalit = TRUE;
		}
	}

	if ($verkkokauppa == "") {

		echo "<form action = '$PHP_SELF?toim_kutsu=$toim_kutsu' method = 'post'>";
		echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

		if (!isset($tultiin)) {
			$tultiin = '';
		}

		if ($tultiin == "futur") {
			echo " <input type='hidden' name='tultiin' value='$tultiin'>";
		}

		echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

		echo "<table style='display:inline;padding-right:4px;' valign='top'>";
		echo "<tr><th>".t("Tuotenumero")."</th><td><input type='text' size='25' name='tuotenumero' id='tuotenumero' value = '$tuotenumero'></td></tr>";
		echo "<tr><th>".t("Toim tuoteno")."</th><td><input type='text' size='25' name = 'toim_tuoteno' id='toim_tuoteno' value = '$toim_tuoteno'></td></tr>";

		if ($orginaaalit) {
			echo "<tr><th>".t("Alkuper�isnumero")."</th><td><input type='text' size='25' name = 'alkuperaisnumero' id='alkuperaisnumero' value = '$alkuperaisnumero'></td></tr>";
		}

		echo "<tr><th>".t("Nimitys")."</th><td><input type='text' size='25' name='nimitys' id='nimitys' value = '$nimitys'></td></tr>";

		if ($kukarow["extranet"] != "") {
			echo "<tr><th>".t("Tarjoustuotteet")."</th><td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr>";
		}
		else {
			echo "<tr><th>".t("Poistetut")."</th><td><input type='checkbox' name='poistetut' id='poistetut' $poischeck></td></tr>";
		}

		echo "<tr><th>".t("Lis�tiedot")."</th><td><input type='checkbox' name='lisatiedot' id='lisatiedot' $lisacheck></td></tr>";
		echo "</table><br>";

		// Monivalintalaatikot (osasto, try tuotemerkki...)
		// M��ritell��n mitk� latikot halutaan mukaan
		$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "MALLI/MALLITARK");

		require ("monivalintalaatikot.inc");

		echo "<br><br>";
		echo "<input type='Submit' name='submit_button' id='submit_button' value = '".t("Etsi")."'></form>";
		echo "&nbsp;<form><input type='submit' name='submit_button2' id='submit_button2' value = '".t("Tyhjenn�")."'></form><br>";
	}

	if ($verkkokauppa != "") {
		if ($osasto != "") {
			$lisa .= "and tuote.osasto = '$osasto' ";
			$ulisa .= "&osasto=$osasto";
		}
		if ($try != "") {
			$lisa .= "and tuote.try = '$try' ";
			$ulisa .= "&try=$try";
		}
		if ($tuotemerkki != "") {
			$lisa .= "and tuote.tuotemerkki = '$tuotemerkki' ";
			$ulisa .= "&tuotemerkki=$tuotemerkki";
		}
	}

	// Halutaanko saldot koko konsernista?
	$query = "	SELECT *
				FROM yhtio
				WHERE konserni='$yhtiorow[konserni]' and konserni != ''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0 and $yhtiorow["haejaselaa_konsernisaldot"] == "K") {
		$yhtiot = array();

		while ($row = mysql_fetch_assoc($result)) {
			$yhtiot[] = $row["yhtio"];
		}
	}
	else {
		$yhtiot = array();
		$yhtiot[] = $kukarow["yhtio"];
	}

	if (isset($sort) and $sort != '') {
		$sort = trim(mysql_real_escape_string($sort));
	}

	if (!isset($sort)) {
		$sort = '';
	}

	if ($sort == 'asc') {
		$sort = 'desc';
		$edsort = 'asc';
	}
	else {
		$sort = 'asc';
		$edsort = 'desc';
	}

	if (!isset($submit_button)) {
		$submit_button = '';
	}

	if ($submit_button != '' and ($lisa != '' or ($toim_kutsu != '' and $lisa != '' and $url == 'y'))) {

		$tuotekyslinkki = "";

		if ($kukarow["extranet"] == "") {
			$query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi='tuote.php' LIMIT 1";
			$tarkres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tarkres) > 0) {
				$tuotekyslinkki = "tuote.php";
			}
			else {
				$query = "SELECT tunnus from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi='tuvar.php' LIMIT 1";
				$tarkres = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tarkres) > 0) {
					$tuotekyslinkki = "tuvar.php";
				}
				else {
					$tuotekyslinkki = "";
				}
			}
		}

		$query = "	SELECT
					ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
					ifnull((SELECT id FROM korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) korvaavat,
					tuote.tuoteno,
					tuote.nimitys,
					tuote.osasto,
					tuote.try,
					tuote.myyntihinta,
					tuote.nettohinta,
					tuote.aleryhma,
					tuote.status,
					tuote.ei_saldoa,
					tuote.yksikko,
					tuote.tunnus,
					(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
					tuote.sarjanumeroseuranta,
					tuote.status
					FROM tuote use index (tuoteno, nimitys)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$kieltolisa
					$lisa
					$extra_poislisa
					$poislisa
					ORDER BY $jarjestys $sort
					LIMIT 500";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {

			$rows = array();

			// Rakennetaan array ja laitetaan korvaavat mukaan
			while ($mrow = mysql_fetch_assoc($result)) {
				if ($mrow["korvaavat"] != $mrow["tuoteno"]) {
					$query = "	SELECT
								ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi='P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
								korvaavat.id korvaavat,
								tuote.tuoteno,
								tuote.nimitys,
								tuote.osasto,
								tuote.try,
								tuote.myyntihinta,
								tuote.nettohinta,
								tuote.aleryhma,
								tuote.status,
								tuote.ei_saldoa,
								tuote.yksikko,
								tuote.tunnus,
								(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
								tuote.sarjanumeroseuranta,
								tuote.status
								FROM korvaavat
								JOIN tuote ON tuote.yhtio=korvaavat.yhtio and tuote.tuoteno=korvaavat.tuoteno
								WHERE korvaavat.yhtio = '$kukarow[yhtio]'
								and korvaavat.id = '$mrow[korvaavat]'
								and korvaavat.tuoteno != '$mrow[tuoteno]'
								$kieltolisa
								$poislisa
								ORDER BY korvaavat.jarjestys, korvaavat.tuoteno";
					$kores = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($kores) > 0) {

						$krow = mysql_fetch_assoc($kores);
						$ekakorva = $krow["korvaavat"];

						mysql_data_seek($kores, 0);

						if (!isset($rows[$ekakorva.$mrow["tuoteno"]])) $rows[$ekakorva.$mrow["tuoteno"]] = $mrow;

						while ($krow = mysql_fetch_assoc($kores)) {

							$krow["mikakorva"] = $mrow["tuoteno"];

							if (!isset($rows[$ekakorva.$krow["tuoteno"]])) $rows[$ekakorva.$krow["tuoteno"]] = $krow;
						}
					}
					else {
						$rows[$mrow["tuoteno"]] = $mrow;
					}
				}
				else {
					$rows[$mrow["tuoteno"]] = $mrow;

					if (!function_exists("tuoteselaushaku_tuoteperhe")) {
						function tuoteselaushaku_tuoteperhe($esiisatuoteno, $tuoteno, $isat_array, $kaikki_array, $rows) {
							global $kukarow, $kieltolisa, $poislisa;

							if (in_array($tuoteno, $isat_array)) {
								//echo "FUULI! TEET IKUISEN LUUPIN!!!!!!!!<br>";
							}
							else {
								$isat_array[] = $tuoteno;

								$query = "	SELECT
				 							'$esiisatuoteno' tuoteperhe,
											tuote.tuoteno korvaavat,
											tuote.tuoteno,
											tuote.nimitys,
											tuote.osasto,
											tuote.try,
											tuote.myyntihinta,
											tuote.nettohinta,
											tuote.aleryhma,
											tuote.status,
											tuote.ei_saldoa,
											tuote.yksikko,
											tuote.tunnus,
											(SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') FROM tuotteen_toimittajat use index (yhtio_tuoteno) WHERE tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
											tuote.sarjanumeroseuranta,
											tuote.status
											FROM tuoteperhe
											JOIN tuote ON tuote.yhtio=tuoteperhe.yhtio and tuote.tuoteno=tuoteperhe.tuoteno
											WHERE tuoteperhe.yhtio 	  = '$kukarow[yhtio]'
											and tuoteperhe.isatuoteno = '$tuoteno'
											$kieltolisa
											$poislisa
											ORDER BY tuoteperhe.tuoteno";
								$kores = mysql_query($query) or pupe_error($query);

								while ($krow = mysql_fetch_assoc($kores)) {
									$rows[$krow["tuoteperhe"].$krow["tuoteno"]] = $krow;
									$kaikki_array[]	= $krow["tuoteno"];
								}
							}

							return array($isat_array, $kaikki_array, $rows);
						}
					}

					if ($mrow["tuoteperhe"] == $mrow["tuoteno"]) {
						$riikoko 		= 1;
						$isat_array 	= array();
						$kaikki_array 	= array($mrow["tuoteno"]);

						for ($isa=0; $isa < $riikoko; $isa++) {
							list($isat_array, $kaikki_array, $rows) = tuoteselaushaku_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows);

							if ($yhtiorow["rekursiiviset_tuoteperheet"] == "Y") {
								$riikoko = count($kaikki_array);
							}
						}
					}
				}
			}

			if ($yhtiorow["saldo_kasittely"] == "T") {
				$saldoaikalisa = date("Y-m-d");
			}
			else {
				$saldoaikalisa = "";
			}

			if ($verkkokauppa != "") {
				echo avoin_kori();

				echo "<form id = 'lisaa' action=\"javascript:ajaxPost('lisaa', 'tuote_selaus_haku.php?', 'selain', false, true);\" name='lisaa' method='post' autocomplete='off'>";

				echo "<input type='hidden' name='submit_button' value = '1'>";
				echo "<input type='hidden' name='sort' value = '$edsort'>";
				echo "<input type='hidden' name='ojarj' value = '$ojarj'>";

				if ($osasto != "") {
					echo "<input type='hidden' name='osasto' value = '$osasto'>";
				}
				if ($try != "") {
					echo "<input type='hidden' name='try' value = '$try'>";
				}
				if ($tuotemerkki != "") {
					echo "<input type='hidden' name='tuotemerkki' value = '$tuotemerkki'>";
				}
			}
			else {
				echo "<form action='?submit_button=1&sort=$edsort&ojarj=$ojarj$ulisa' name='lisaa' method='post' autocomplete='off'>";
			}

			echo "<input type='hidden' name='tee' value = 'TI'>";
			echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
			echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

			if ($tultiin == "futur") {
				echo " <input type='hidden' name='tultiin' value='$tultiin'>";
			}

			if ($verkkokauppa == "") {
				echo "<br/>";
				echo "<table>";
				echo "<tr>";

				echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=tuoteno$ulisa'>".t("Tuoteno")."</a>";

				if ($lisatiedot != "") {
					echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=toim_tuoteno$ulisa'>".t("Toim Tuoteno");
				}

				echo "</th>";

				echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=nimitys$ulisa'>".t("Nimitys")."</th>";
				echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=osasto$ulisa'>".t("Osasto");
				echo "<br><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=try$ulisa'>".t("Try")."</th>";

				if ($kukarow['hinnat'] >= 0) echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=hinta$ulisa'>".t("Hinta");

				if ($lisatiedot != "" and $kukarow["extranet"] == "") {
					echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=nettohinta$ulisa'>".t("Nettohinta");
				}

				echo "<th><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=aleryhma$ulisa'>".t("Aleryhm�");

				if ($lisatiedot != "" and $kukarow["extranet"] == "") {
					echo "<br/><a href = '?submit_button=1&toim_kutsu=$toim_kutsu&url=y&sort=$sort&ojarj=status$ulisa'>".t("Status");
				}

				echo "</th>";

				echo "<th>".t("Myyt�viss�")."</th>";

				if ($lisatiedot != "" and $kukarow["extranet"] == "") {
					echo "<th>".t("Hyllyss�")."</th>";
				}

				if ($oikeurow["paivitys"] == 1 and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
					echo "<th>&nbsp;</th>";
				}

				echo "</tr>";
			}
			else {
				echo "<br/>";
				echo "<table style='width:100%;'>";
				echo "<tr>";

				echo "<th>".t("Tuotenro")."</th>";
				echo "<th>".t("Nimitys")."</th>";

				if ($kukarow["kuka"] != "www") {
					echo "<th>".t("Hinta")." / ";

					if ($yhtiorow["alv_kasittely"] != "") {
						echo t("ALV")." 0%";
					}
					else {
						echo t("Sis. ALV");
					}

					echo "</th>";

					if ($kukarow["kuka"] != "www" and $verkkokauppa_saldotsk) {
						echo "<th>".t("Saldo")."</th>";
					}

					echo "<th>".t("Osta")."</th>";
				}

				echo "</tr>";
			}

			$edtuoteno 	= "";
			$yht_i 		= 0;
			$alask 		= 0;

			if ($verkkokauppa == "") {
				foreach ($rows as $ind => $row) {
					// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausrivilt�
					if ($row["sarjanumeroseuranta"] == "S" and ($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"])) {
						$query	= "	SELECT sarjanumeroseuranta.*, 
									sarjanumeroseuranta.tunnus sarjatunnus,
									tilausrivi_osto.tunnus osto_rivitunnus,
									tilausrivi_osto.perheid2 osto_perheid2,
									tilausrivi_osto.nimitys nimitys,
									lasku_myynti.nimi myynimi,
									tilausrivi_myynti.tyyppi, 
									lasku_myynti.tunnus myytunnus
									FROM sarjanumeroseuranta
									LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
									LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
									LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio=sarjanumeroseuranta.yhtio and lasku_myynti.tunnus=tilausrivi_myynti.otunnus
									WHERE sarjanumeroseuranta.yhtio in ('".implode("','", $yhtiot)."')
									and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
									and sarjanumeroseuranta.myyntirivitunnus != -1
									and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.tyyppi='T')
									and tilausrivi_osto.laskutettuaika != '0000-00-00'
									order by nimitys";
						$sarjares = mysql_query($query) or pupe_error($query);

						// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausrivilt�
						$sarjalask = 0;

						if (mysql_num_rows($sarjares) > 0) {

							while ($sarjarow = mysql_fetch_assoc($sarjares)) {
								$fnlina1 = "";
								
								if (($sarjarow["siirtorivitunnus"] > 0) or ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"])) {

									if ($sarjarow["osto_perheid2"] > 0 and $sarjarow["osto_perheid2"] != $sarjarow["osto_rivitunnus"]) {
										$ztun = $sarjarow["osto_perheid2"];
									}
									else {
										$ztun = $sarjarow["siirtorivitunnus"];
									}

									$query = "	SELECT tilausrivi.tunnus, tilausrivi.tuoteno, sarjanumeroseuranta.sarjanumero, tyyppi, otunnus
												FROM tilausrivi
												LEFT JOIN sarjanumeroseuranta ON (tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus)
												WHERE tilausrivi.yhtio='$kukarow[yhtio]' and tilausrivi.tunnus='$ztun'";
									$siires = mysql_query($query) or pupe_error($query);
									$siirow = mysql_fetch_array($siires);

									if ($siirow["tyyppi"] == "O") {
										// pultattu kiinni johonkin
										$fnlina1 = " <font class='message'>(".t("Varattu lis�varusteena").": $siirow[tuoteno] <a href='tilauskasittely/sarjanumeroseuranta.php?tuoteno_haku=".urlencode($siirow["tuoteno"])."&sarjanumero_haku=".urlencode($siirow["sarjanumero"])."'>$siirow[sarjanumero]</a>)</font>";
									}
									elseif ($siirow["tyyppi"] == "G") {
										// jos t�m� on jollain siirtolistalla
										$fnlina1 = " <font class='message'>(".t("Kesken siirtolistalla").": $siirow[otunnus])</font>";
									}
								}
								
								if ($sarjarow["nimitys"] != "") {
									$row["nimitys"] = $sarjarow["nimitys"];
								}
								
								if ($fnlina1 != "") {
									$row["nimitys"] = $sarjarow["nimitys"]."<br>".$fnlina1;
									// Sarjanumero on varattu, ei voi liitt� tilaukselle
									$row["sarjadisabled"] = TRUE;
								}

								if ($sarjarow["yhtio"] != $kukarow["yhtio"]) {
									$row["sarjanumero"] = $sarjarow["sarjanumero"]." ($sarjarow[yhtio])";
								}
								else {
									$row["sarjanumero"] = $sarjarow["sarjanumero"];
								}

								$row["sarjatunnus"] = $sarjarow["tunnus"];
								$row["sarjayhtio"] = $sarjarow["yhtio"];

								if ($sarjalask > 0) {
									$row["korvaavat"] = $ind.$sarjalask;
									array_splice($rows, $alask, 0, array($ind.$sarjalask => $row));
						 		}
								else {
									$rows[$ind] = $row;
								}

								$sarjalask++;
							}
						}
					}

					$alask++;
				}
			}

			foreach ($rows as &$row) {

				if ($kukarow['extranet'] != '' or $verkkokauppa != "") {
					$hae_ja_selaa_asiakas = (int) $kukarow['oletus_asiakas'];
				}
				else {
					$hae_ja_selaa_asiakas = (int) $laskurow['liitostunnus'];
				}

				if ($hae_ja_selaa_asiakas != 0) {
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]'
								AND tuoteno = '$row[tuoteno]'";
					$tuotetempres = mysql_query($query);
					$temptrow = mysql_fetch_assoc($tuotetempres);

					$temp_laskurowwi = $laskurow;

					if (!is_array($temp_laskurowwi)) {
						$query = "	SELECT *
									FROM asiakas
									WHERE yhtio = '$kukarow[yhtio]'
									AND tunnus = '$kukarow[oletus_asiakas]'";
						$asiakastempres = mysql_query($query);
						$asiakastemprow = mysql_fetch_assoc($asiakastempres);

						$temp_laskurowwi['liitostunnus']	= $asiakastemprow['tunnus'];
						$temp_laskurowwi['ytunnus']			= $asiakastemprow['ytunnus'];
						$temp_laskurowwi['valkoodi']		= $asiakastemprow['valkoodi'];
						$temp_laskurowwi['maa']				= $asiakastemprow['maa'];
					}

					$hinnat = alehinta($temp_laskurowwi, $temptrow, 1, '', '', '', "hinta,hintaperuste,aleperuste,ale");

					// jos tuote saadaan n�ytt�� vain mik�li asiakkaalla on alehinta, niin sanotaan continue jos joku hinta/ale l�ytyy
					if ($temptrow["hinnastoon"] == "V" and ($hinnat["hintaperuste"] >= 13 or $hinnat["hintaperuste"] === FALSE) and ($hinnat["aleperuste"] >= 9 or $hinnat["aleperuste"] === FALSE)) {
						continue;
					}
				}

				if ($row["sarjatunnus"] > 0 and $kukarow["extranet"] == "" and $verkkokauppa == "" and function_exists("sarjanumeronlisatiedot_popup")) {
					if ($lisatiedot != "") {
						echo "<tr class='aktiivi'><td colspan='7' class='back'><br></td></tr>";
					}
					else {
						echo "<tr class='aktiivi'><td colspan='8' class='back'><br></td></tr>";
					}
				}

				echo "<tr class='aktiivi'>";

				$vari = "";

				if ($verkkokauppa == "" and isset($row["mikakorva"])) {
					$vari = 'spec';
					$row["nimitys"] .= "<br> * ".t("Korvaa tuotteen").": $row[mikakorva]";
				}
				if ($verkkokauppa == "" and strtoupper($row["status"]) == "P") {
					$vari = "tumma";
					$row["nimitys"] .= "<br> * ".t("Poistuva tuote");
				}

				// Peek ahead
				$row_seuraava = current($rows);

				if ($row["tuoteperhe"] == $row["tuoteno"] and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"] and $row_seuraava["tuoteperhe"] != "") {
					$classleft = "";
					$classmidl = "";
					$classrigh = "";
				}
				elseif ($row["tuoteperhe"] == $row["tuoteno"]) {
					$classleft = "style='border-top: 1px solid #555555; border-left: 1px solid #555555;' ";
					$classmidl = "style='border-top: 1px solid #555555;' ";
					$classrigh = "style='border-top: 1px solid #555555; border-right: 1px solid #555555;' ";
				}
				elseif ($row["tuoteperhe"] != "" and $row["tuoteperhe"] != $row_seuraava["tuoteperhe"]) {
					$classleft = "style='border-bottom: 1px solid #555555; border-left: 1px solid #555555;' ";
					$classmidl = "style='border-bottom: 1px solid #555555;' ";
					$classrigh = "style='border-bottom: 1px solid #555555; border-right: 1px solid #555555;' ";
				}
				elseif ($row["tuoteperhe"] != '') {
					$classleft = "style='border-left: 1px solid #555555;' ";
					$classmidl = "";
					$classrigh = "style='border-right: 1px solid #555555;' ";
				}
				else {
					$classleft = "";
					$classmidl = "";
					$classrigh = "";
				}

				$linkkilisa = "";

				//	Liitet��n originaalitietoja
				if ($orginaaalit === TRUE) {
					$id = md5(uniqid());

					$query = "	SELECT *
								FROM tuotteen_orginaalit
								WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]'";
					$orgres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($orgres)>0) {
						$linkkilisa = "<div id='div_$id' class='popup' style='width: 300px'>
						<table width='300px' align='center'>
						<caption><font class='head'>Tuotteen originaalit</font></caption>
						<tr>
							<th>".t("Tuotenumero")."</th>
							<th>".t("Merkki")."</th>
							<th>".t("Hinta")."</th>
						</tr>";

						while($orgrow = mysql_fetch_assoc($orgres)) {
							$linkkilisa .= "<tr>
									<td>$orgrow[orig_tuoteno]</td>
									<td>$orgrow[merkki]</td>
									<td>$orgrow[orig_hinta]</td>
								</tr>";
						}

						$linkkilisa .= "</table></div>";

						if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
							$linkkilisa .= "&nbsp;&nbsp;<img class='tooltip' id='$id' src='pics/lullacons/info.png' height='13'>";
						}
						else {
							$linkkilisa .= "&nbsp;&nbsp;<img class='tooltip' id='$id' src='../pics/lullacons/info.png' height='13'>";
						}
					}
				}

				if ($verkkokauppa != "") {
					if ($row["toim_tuoteno"] != "" and $kukarow["kuka"] != "www") {
						$toimlisa = "<br>$row[toim_tuoteno]";
					}
					else {
						$toimlisa = "";
					}

					echo "<td valign='top' class='$vari' $classleft id='tno'>$row[tuoteno] $toimlisa</td>";
					echo "<td valign='top' class='$vari' $classmidl><a id='P3_$row[tuoteno]' href='javascript:sndReq(\"T_$row[tuoteno]\", \"verkkokauppa.php?tee=tuotteen_lisatiedot&tuoteno=$row[tuoteno]\", \"P3_$row[tuoteno]\")'>".t_tuotteen_avainsanat($row, 'nimitys')."</a>";
				}
				elseif ($kukarow["extranet"] != "" or $tuotekyslinkki == "") {
					echo "<td valign='top' class='$vari' $classleft>$row[tuoteno] $linkkilisa ";
				}
				else {
					echo "<td valign='top' class='$vari' $classleft><a href='../$tuotekyslinkki?tuoteno=".urlencode($row["tuoteno"])."&tee=Z&lopetus=$PHP_SELF////submit_button=1//toim_kutsu=$toim_kutsu//url=y//sort=$sort//ojarj=$ojarj".str_replace("&","//",$ulisa)."'>$row[tuoteno]</a>$linkkilisa ";
				}

				if ($lisatiedot != "" and $verkkokauppa == "") {
					echo "<br>$row[toim_tuoteno]";
				}

				echo "</td>";

				if ($verkkokauppa == "") {
					echo "<td valign='top' class='$vari' $classmidl>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";
				}

				if ($verkkokauppa == "") {
					echo "<td valign='top' class='$vari' $classmidl>$row[osasto]<br>$row[try]</td>";
				}

				if ($verkkokauppa == "" or $kukarow["kuka"] != "www") {
					if ($kukarow['hinnat'] >= 0) {

						$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["myyntihinta"]). " $yhtiorow[valkoodi]";

						if ($kukarow["extranet"] != "" and $kukarow["naytetaan_asiakashinta"] != "") {
							// haetaan tuotteen tiedot
							$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $hinnat["hinta"] * (1-($hinnat["ale"]/100)))." $laskurow[valkoodi]";
						}
						elseif ($kukarow["extranet"] != "") {
							// jos kyseess� on extranet asiakas yritet��n n�ytt�� kaikki hinnat oikeassa valuutassa
							if ($oleasrow["valkoodi"] != $yhtiorow["valkoodi"]) {

								$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["myyntihinta"])." $yhtiorow[valkoodi]";

								$query = "	SELECT *
											from hinnasto
											where yhtio = '$kukarow[yhtio]'
											and tuoteno = '$row[tuoteno]'
											and valkoodi = '$oleasrow[valkoodi]'
											and laji = ''
											and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
											order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
											limit 1";
								$olhires = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($olhires) == 1) {
									$olhirow = mysql_fetch_assoc($olhires);
									$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", $olhirow["hinta"])." $olhirow[valkoodi]";
								}
								elseif ($olhirow["kurssi"] != 0) {
									$myyntihinta = sprintf("%.".$yhtiorow['hintapyoristys']."f", yhtioval($row["myyntihinta"], $olhirow["kurssi"])). " $oleasrow[valkoodi]";
								}
							}
						}
						else {
							$query = "	SELECT distinct valkoodi, maa
										from hinnasto
										where yhtio = '$kukarow[yhtio]'
										and tuoteno = '$row[tuoteno]'
										and laji = ''
										order by maa, valkoodi";
							$hintavalresult = mysql_query($query) or pupe_error($query);

							while ($hintavalrow = mysql_fetch_assoc($hintavalresult)) {

								// katotaan onko tuotteelle valuuttahintoja
								$query = "	SELECT *
											from hinnasto
											where yhtio = '$kukarow[yhtio]'
											and tuoteno = '$row[tuoteno]'
											and valkoodi = '$hintavalrow[valkoodi]'
											and maa = '$hintavalrow[maa]'
											and laji = ''
											and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
											order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
											limit 1";
								$hintaresult = mysql_query($query) or pupe_error($query);

								while ($hintarow = mysql_fetch_assoc($hintaresult)) {
									$myyntihinta .= "<br>$hintarow[maa]: ".sprintf("%.".$yhtiorow['hintapyoristys']."f", $hintarow["hinta"])." $hintarow[valkoodi]";
								}
							}
						}

						echo "<td valign='top' class='$vari' align='right' $classmidl nowrap>$myyntihinta";

						if ($lisatiedot != "" and $kukarow["extranet"] == "") {
							echo "<br>".sprintf("%.".$yhtiorow['hintapyoristys']."f", $row["nettohinta"])." $yhtiorow[valkoodi]";
						}

						echo "</td>";
					}

				}

				if ($verkkokauppa == "") {
					echo "<td valign='top' class='$vari' $classmidl>$row[aleryhma]";

					if ($lisatiedot != "" and $kukarow["extranet"] == "") {
						echo "<br>$row[status]";
					}

					echo "</td>";
				}

				$edtuoteno = $row["korvaavat"];

				if ($verkkokauppa == "" or ($verkkokauppa != "" and $kukarow["kuka"] != "www" and $verkkokauppa_saldotsk)) {
					if ($row["tuoteperhe"] == $row["tuoteno"] and $row["sarjanumeroseuranta"] != "S") {
						// Tuoteperheen is�t
						if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
							$saldot = tuoteperhe_myytavissa($row["tuoteno"], "KAIKKI", "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

							$kokonaismyytavissa = 0;

							foreach ($saldot as $varasto => $myytavissa) {
								$kokonaismyytavissa += $myytavissa;
							}

							if ($kokonaismyytavissa > 0) {
								echo "<td valign='top' class='$vari' $classrigh><font class='green'>".t("On")."</font></td>";
							}
							else {
								echo "<td valign='top' class='$vari' $classrigh><font class='red'>".t("Ei")."</font></td>";
							}
						}
						else {
							$saldot = tuoteperhe_myytavissa($row["tuoteno"], "", "KAIKKI", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

							$classrighx = substr($classrigh, 0, -2)." padding: 0px;' ";

							echo "<td valign='top' class='$vari' $classrighx>";
							echo "<table style='width:100%;'>";

							$ei_tyhja = "";

							foreach ($saldot as $varaso => $saldo) {
								if ($saldo != 0) {
									$ei_tyhja = 'yes';
									echo "<tr class='aktiivi'><td class='$vari' nowrap>$varaso</td><td class='$vari' align='right' nowrap>".sprintf("%.2f", $saldo)." ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td></tr>";
								}
							}

							if ($ei_tyhja == '') {
								echo "<tr class='aktiivi'><td class='$vari' nowrap colspan='2'><font class='red'>".t("Tuote loppu")."</font></td></tr>";
							}

							echo "</table></td>";
						}
					}
					elseif ($row['ei_saldoa'] != '') {
						// Saldottomat tuotteet
						if ($kukarow["extranet"] != "" or $verkkokauppa != "") {
							echo "<td valign='top' class='$vari' $classrigh><font class='green'>".t("On")."</font></td>";
						}
						else {
							echo "<td valign='top' class='$vari' $classrigh><font class='green'>".t("Saldoton")."</font></td>";
						}
					}
					elseif ($verkkokauppa == "" and ($row["sarjanumeroseuranta"] == "S" and ($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"]))) {
						// Sarjanumerolliset tuotteet, (ei verkkokauppahaarassa)
						if ($kukarow["extranet"] != "") {
							echo "<td valign='top' class='$vari' $classrigh>$row[sarjanumero] ";
						}
						else {
							echo "<td valign='top' class='$vari' $classrigh><a onClick=\"javascript:sarjanumeronlisatiedot_popup('$row[sarjatunnus]')\">$row[sarjanumero]</a> ";
						}

						if (!isset($row["sarjadisabled"]) and $row["sarjayhtio"] == $kukarow["yhtio"] and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
							echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
							echo "<input type='hidden' name='tilsarjatunnus[$yht_i]' value = '$row[sarjatunnus]'>";
							echo "<input type='checkbox' name='tilkpl[$yht_i]' value='1'> ";
							$yht_i++;
						}

						echo "</td>";

						if ($lisatiedot != "" and $kukarow["extranet"] == "") {
							echo "<td class='$vari' $classrigh></td>";
						}
					}
					elseif ($kukarow["extranet"] != "" or $verkkokauppa != "") {

						list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", 0, "", "", "", "", "", $laskurow["toim_maa"], $saldoaikalisa);

						$tulossalisa = "";

						if ($verkkokauppa != "" or $myytavissa <= 0) {
							$tulossa_query = " 	SELECT IFNULL(MIN(toimaika),'') paivamaara
							 					FROM tilausrivi
												WHERE yhtio='$kukarow[yhtio]' AND tuoteno='$row[tuoteno]' AND varattu > 0 AND tyyppi='O'";
							$tulossa_result = mysql_query($tulossa_query) or pupe_error($tulossa_query);
							$tulossa_row = mysql_fetch_assoc($tulossa_result);

							if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["paivamaara"] != '') {
								$tulossalisa = ", <font class='info'>".t("Saapuu")." ".tv1dateconv($tulossa_row['paivamaara'])."</font>";
							}
							elseif ((float) $myytavissa <= 0) {
								$query = "	SELECT toimitusaika
											FROM tuotteen_toimittajat
											WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]' and toimitusaika>0
											LIMIT 1";
								$tulossa_result = mysql_query($query) or pupe_error($query);
								$tulossa_row = mysql_fetch_assoc($tulossa_result);

								if ($tulossa_row["toimitusaika"] > 0) {
									$tulossalisa = ", <font class='info'>".t("Toimaika: %s pv.", $kieli, $tulossa_row["toimitusaika"])."</font>";
								}
							}
						}

						if ($myytavissa > 0) {

							echo "<td valign='top' class='$vari' $classrigh><font class='green'>";

							if ($verkkokauppa != "" and $verkkokauppa_saldoluku) {
								echo $myytavissa;
							}
							else {
								echo t("On");
							}

							echo "</font>$tulossalisa</td>";
						}
						else {
							echo "<td valign='top' class='$vari' $classrigh><font class='red'>".t("Ei")."</font>$tulossalisa</td>";
						}
					}
					else {

						if ($laskurow["toim_maa"] != '') {
							$sallitut_maat_lisa = " and (varastopaikat.sallitut_maat like '%$laskurow[toim_maa]%' or varastopaikat.sallitut_maat = '') ";
						}

						// K�yd��n l�pi tuotepaikat
						if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F" or $row["sarjanumeroseuranta"] == "G") {
							$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
										tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
										sarjanumeroseuranta.sarjanumero era,
										concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
										varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
							 			FROM tuote
										JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
										JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
										$sallitut_maat_lisa
										and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
										JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
										and sarjanumeroseuranta.tuoteno = tuote.tuoteno
										and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
										and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
										and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
										and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
										and sarjanumeroseuranta.myyntirivitunnus = 0
										and sarjanumeroseuranta.era_kpl != 0
										WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
										and tuote.tuoteno = '$row[tuoteno]'
										GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
										ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
						}
						else {
							$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
										tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
										concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
										varastopaikat.nimitys, if (varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
							 			FROM tuote
										JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
										JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
										$sallitut_maat_lisa
										and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
										and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
										WHERE tuote.yhtio in ('".implode("','", $yhtiot)."')
										and tuote.tuoteno = '$row[tuoteno]'
										ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
						}
						$varresult = mysql_query($query) or pupe_error($query);

						$classrighx = substr($classrigh, 0, -2)." padding: 0px;' ";

						echo "<td valign='top' class='$vari' $classrighx>";

						if (mysql_num_rows($varresult) > 0) {
							$hyllylisa = "";
							echo "<table style='width:100%;'>";

							// katotaan jos meill� on tuotteita varaamassa saldoa joiden varastopaikkaa ei en�� ole olemassa...
							list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], 'ORVOT', '', '', '', '', '', '', '', $saldoaikalisa);
							$orvot *= -1;

							while ($saldorow = mysql_fetch_assoc ($varresult)) {

								list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], $laskurow["toim_maa"], $saldoaikalisa, $saldorow["era"]);

								//	Listataan vain varasto jo se ei ole kielletty
								if ($sallittu === TRUE) {
									// hoidetaan pois problematiikka jos meill� on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
									if ($orvot > 0) {
										if ($myytavissa >= $orvot and $saldorow["yhtio"] == $kukarow["yhtio"]) {
									    	// poistaan orpojen varaamat tuotteet t�lt� paikalta
									    	$myytavissa = $myytavissa - $orvot;
									    	$orvot = 0;
										}
										elseif ($orvot > $myytavissa and $saldorow["yhtio"] == $kukarow["yhtio"]) {
									    	// poistetaan niin paljon orpojen saldoa ku voidaan
									    	$orvot = $orvot - $myytavissa;
									    	$myytavissa = 0;
										}
									}

									if ($myytavissa != 0 or ($lisatiedot != "" and $kukarow["extranet"] == "" and $verkkokauppa == "" and $hyllyssa != 0)) {
										echo "	<tr>
												<td class='$vari' nowrap>$saldorow[nimitys] $saldorow[tyyppi]</td>
												<td class='$vari' align='right' nowrap>".sprintf("%.2f", $myytavissa)." ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td>
												</tr>";
									}

									if ($lisatiedot != "" and $kukarow["extranet"] == "" and $verkkokauppa == "" and $hyllyssa != 0) {
										$hyllylisa .= "	<tr class='aktiivi'>
														<td class='$vari' align='right' nowrap>".sprintf("%.2f", $hyllyssa)."</td>
														</tr>";
									}
								}
							}
							echo "</table></td>";
						}
						echo "</td>";

						if ($lisatiedot != "" and $kukarow["extranet"] == "" and $verkkokauppa == "") {
							echo "<td valign='top' $classrigh class='$vari'>";

							if (mysql_num_rows($varresult) > 0 and $hyllylisa != "") {

								echo "<table width='100%'>";
								echo "$hyllylisa";
								echo "</table></td>";
							}
							echo "</td>";
						}
					}
				}

				if ($oikeurow["paivitys"] == 1 and ($kukarow["kuka"] != "" or is_numeric($ostoskori))) {
					echo "<td align='right' class='$vari' style='vertical-align: top;' nowrap>";
					echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
					echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
					echo "<input type='submit' value = '".t("Lis��")."'>";
					echo "</td>";
					$yht_i++;
				}

				if ($verkkokauppa == "") {
					// Onko liitetiedostoja
					$liitteet = liite_popup("TN", $row["tunnus"]);

					if ($liitteet != "") echo "<td class='back' style='vertical-align: top;'>$liitteet</td>";
				}

				echo "</tr>";

				if ($verkkokauppa != "") {
					
					if (stripos($_SERVER["HTTP_USER_AGENT"], "MSIE") !== FALSE) {
						echo "<tr><td colspan='6' class='back' style='padding:0px; margin:0px;height:0px;'><div id='T_$row[tuoteno]'></div></td></tr>";
					}
					else {
						echo "<tr id='T_$row[tuoteno]'></tr>";
					}
				}

				if ($row["sarjatunnus"] > 0 and $kukarow["extranet"] == "" and $verkkokauppa == "" and function_exists("sarjanumeronlisatiedot_popup")) {
					list($kommentit, $text_output, $kuvalisa_bin, $ostohinta, $tuotemyyntihinta) = sarjanumeronlisatiedot_popup($row["sarjatunnus"], $row["sarjayhtio"], '', '', '100%', '');

					if ($lisatiedot != "") {
						echo "<tr class='aktiivi'><td colspan='7'>$kommentit</td></tr>";
					}
					else {
						echo "<tr class='aktiivi'><td colspan='6'>$kommentit</td></tr>";
					}
				}
			}

			echo "</table>";
			echo "</form>";
		}
		else {
			echo t("Yht��n tuotetta ei l�ytynyt")."!";
		}

		if (mysql_num_rows($result) == 500) {
			echo "<br><br><font class='message'>".t("L�ytyi yli 500 tuotetta, tarkenna hakuasi")."!</font>";
		}
	}

	if ($verkkokauppa == "") {
		if (@include("inc/footer.inc"));
		elseif (@include("footer.inc"));
		else exit;
	}
?>