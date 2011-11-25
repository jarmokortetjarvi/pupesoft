<?php
// T�m� skripti k�ytt�� slave-tietokantapalvelinta
$useslave = 1;

require("../inc/parametrit.inc");

echo "<font class='head'>".t("Valmistusten suunnittelu")."</font><hr>";

// Muodostetaan ostotilaukset
if(isset($muodostaValmistukset)){
	echo t("Nyt pit�isi muodostaa valmistukset");
}

if ($yhtiot == "") $yhtiot = "'$kukarow[yhtio]'";

// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
if ($yhtiorow["varaako_jt_saldoa"] != "") {
	$lisavarattu = " + tilausrivi.varattu";
}
else {
	$lisavarattu = "";
}

// ABC luokkanimet
$ryhmanimet   = array('A-30','B-20','C-15','D-15','E-10','F-05','G-03','H-02','I-00');

// Tarvittavat p�iv�m��r�t
if (!isset($kka1)) $kka1 = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva1)) $vva1 = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa1)) $ppa1 = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($kkl1)) $kkl1 = date("m");
if (!isset($vvl1)) $vvl1 = date("Y");
if (!isset($ppl1)) $ppl1 = date("d");

// Edellisen vuoden vastaavat kaudet
$kka1ed = date("m",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$vva1ed = date("Y",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$ppa1ed = date("d",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$kkl1ed = date("m",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
$vvl1ed = date("Y",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
$ppl1ed = date("d",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));

// Jos p�iv�m��r�t on virheellisi�, k�ytet��n nykyhetke�
if(checkdate($kka1ed, $ppa1ed, $vva1ed)){
	$apvm = "$vva1ed-$kka1ed-$ppa1ed";
}
else $apvm = date('Y-m-d');

if(checkdate($kkl1ed, $ppl1ed, $vvl1ed)){
	$lpvm = "$vvl1ed-$kkl1ed-$ppl1ed";
}
else $lpvm = date('Y-m-d');

// Tehd��n yksi rivi taulukkoon
function teerivi( $tuoteno, $sisartuoteno, $kerroin ){

	// otetaan kaikki muuttujat mukaan funktioon mit� on failissakin
	extract($GLOBALS);

	// Haetaan is�tuotteiden varastosaldo
	$query = "	SELECT ifnull(sum(saldo),0) saldo 
				FROM tuotepaikat
				JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
				WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
				AND concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
				AND concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))	
				AND tuotepaikat.tuoteno in $isatuoteno";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) > 0) {
		$isanvarastosaldo = 0;
		while ($varrow = mysql_fetch_array($result)) {
			$isanvarastosaldo += floatval( $varrow['saldo'] );
		}
	}
	
	// Haetaan is�tuotteiden myynti
	$query = "	SELECT
				sum(if(tilausrivi.tyyppi = 'L' AND laadittu >= '$vva1-$kka1-$ppa1 00:00:00' AND laadittu <= '$vvl1-$kkl1-$ppl1 23:59:59' and var='P', tilkpl,0)) puutekpl,
				sum(if(tilausrivi.tyyppi = 'L' AND laskutettuaika >= '$vva1-$kka1-$ppa1' AND laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl,
				sum(if(tilausrivi.tyyppi = 'L' AND laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed' AND laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl,0)) EDkpl,
				sum(if(tilausrivi.tyyppi = 'L' AND laskutettuaika >= '$vva1-$kka1-$ppa1' AND laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kate,0)) kate,
				sum(if(tilausrivi.tyyppi = 'L' AND laskutettuaika >= '$vva1-$kka1-$ppa1' AND laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,rivihinta,0)) rivihinta,
				sum(if(tilausrivi.tyyppi = 'O' AND laskutettuaika >= '$vva1-$kka1-$ppa1' AND laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.varattu,0)) tilattu,
				sum(if((tilausrivi.tyyppi = 'L' or tilausrivi.tyyppi = 'V') AND tilausrivi.var not in ('P','J','S'), tilausrivi.varattu, 0)) ennpois,
				sum(if(tilausrivi.tyyppi = 'L' AND tilausrivi.var in ('J','S'), tilausrivi.jt $lisavarattu, 0)) jt,
				sum(if(tilausrivi.tyyppi = 'E', tilausrivi.varattu, 0)) ennakko
				FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.tyyppi in ('L','V','E')
				AND ((tilausrivi.laskutettuaika >= '$apvm' AND tilausrivi.laskutettuaika <= '$lpvm') or tilausrivi.laskutettuaika = '0000-00-00')
				AND tilausrivi.tuoteno in $isatuoteno";
	$result = mysql_query($query) or pupe_error($query);
	$isarow = mysql_fetch_array($result);

	$isanreaalisaldo = $isanvarastosaldo - $isarow['tilattu'] - $isarow['ennpois'];
	
	// Haetaan budjetti-indeksi, jos sellainen on m��ritetty
	// TODO: haetaan budjetti-indeksi
	if(1==2){
	}
	else $budjetti_indeksi = 1;
	
	// Lasketaan is�tuotteen myyntiennuste
	$myyntiennuste = $isarow['EDkpl'] * $budjetti_indeksi - $isanreaalisaldo;
	
	// Haetaan lapsituotteen varastosaldo
	$query = "	SELECT ifnull(sum(saldo),0) saldo 
				FROM tuotepaikat
				JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
				WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
				AND concat(rpad(upper(alkuhyllyalue)  ,5,'0'),lpad(upper(alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))
				AND concat(rpad(upper(loppuhyllyalue) ,5,'0'),lpad(upper(loppuhyllynro) ,5,'0')) >= concat(rpad(upper(tuotepaikat.hyllyalue) ,5,'0'),lpad(upper(tuotepaikat.hyllynro) ,5,'0'))	
				AND tuotepaikat.tuoteno = '$tuoteno'";
	$result = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($result) > 0) {
		$lapsenvarastosaldo = 0;
		while ($varrow = mysql_fetch_array($result)) {
			$lapsenvarastosaldo += floatval( $varrow['saldo'] );
		}
	}	

	// Haetaan lapsituotteen myynti
	$query = "	SELECT
				sum(if(tilausrivi.tyyppi = 'L' AND laadittu >= '$vva1-$kka1-$ppa1 00:00:00' AND laadittu <= '$vvl1-$kkl1-$ppl1 23:59:59' and var='P', tilkpl,0)) puutekpl,
				sum(if(tilausrivi.tyyppi = 'L' AND laskutettuaika >= '$vva1-$kka1-$ppa1' AND laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl,
				sum(if(tilausrivi.tyyppi = 'L' AND laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed' AND laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl,0)) EDkpl,
				sum(if(tilausrivi.tyyppi = 'L' AND laskutettuaika >= '$vva1-$kka1-$ppa1' AND laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kate,0)) kate,
				sum(if(tilausrivi.tyyppi = 'L' AND laskutettuaika >= '$vva1-$kka1-$ppa1' AND laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,rivihinta,0)) rivihinta,
				sum(if((tilausrivi.tyyppi = 'L' or tilausrivi.tyyppi = 'V') AND tilausrivi.var not in ('P','J','S'), tilausrivi.varattu, 0)) ennpois,
				sum(if(tilausrivi.tyyppi = 'L' AND tilausrivi.var in ('J','S'), tilausrivi.jt $lisavarattu, 0)) jt,
				sum(if(tilausrivi.tyyppi = 'E', tilausrivi.varattu, 0)) ennakko,
				sum(if(tilausrivi.tyyppi in ('L','V','W') AND toimitettuaika >= '$vva1-01-01' AND toimitettuaika <= '$vvl1-12-31', kpl, 0)) vuosikulutus
				FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
				AND tilausrivi.tyyppi in ('L','V','E')
				AND ((tilausrivi.laskutettuaika >= '$apvm' AND tilausrivi.laskutettuaika <= '$lpvm') OR tilausrivi.laskutettuaika = '0000-00-00')
				AND tilausrivi.tuoteno = '$tuoteno'";
	$result = mysql_query($query) or pupe_error($query);
	$lapsirow = mysql_fetch_array($result); 
	
	// Haetaan lapsituotteen toimitusaika ja pakkauskoko
	$query = "	SELECT if(tuotteen_toimittajat.toimitusaika > 0, tuotteen_toimittajat.toimitusaika, 0 ) toimitusaika,
				pakkauskoko
				FROM tuotteen_toimittajat 
				WHERE yhtio='$kukarow[yhtio]' AND tuoteno='$tuoteno'";
	$result = mysql_query($query) or pupe_error($query);
	$toimittajarow = mysql_fetch_array($result); 
	
	$lapsenreaalisaldo = $lapsenvarastosaldo - $lapsirow['tilattu'] - $lapsirow['ennpois'];
	$toimitusaika = $toimittajarow['toimitusaika'];
	$vuosikulutus = $lapsirow['vuosikulutus'];
	$paivakulutus = ( $vuosikulutus > 0 ? round($vuosikulutus / 360) : 0 );
	
	$kulutusennuste = $myyntiennuste * $kerroin; //- $lapsenreaalisaldo;
	$maaraennuste = $kulutusennuste + ($paivakulutus * $toimitusaika); // TODO: otetaanko todelliset vuodenp�iv�t vai k�ytet��nk� 360
	if($paivakulutus > 0){
		$riittopv = ( floor( $lapsenreaalisaldo / $paivakulutus ) > 0 ? floor( $lapsenreaalisaldo / $paivakulutus ) : 0 ); // N�ytet��n negatiivinen riitto nollana
	}
	else $riittopv =  t('Ei tiedossa'); // Jos aiempaa kulutusta ei ole
	$ostosuositus = ( ($maaraennuste-$lapsenreaalisaldo) > 0 ? ceil($maaraennuste-$lapsenreaalisaldo) : 0); // N�ytet��n negatiivinen suositus nollana

	$tuoterivi['kulutusennuste'] 	= $kulutusennuste;
	$tuoterivi['maaraennuste']		= $maaraennuste;
	$tuoterivi['riittopv'] 			= $riittopv;
	$tuoterivi['reaalisaldo'] 		= $lapsenreaalisaldo;
	$tuoterivi['ostosuositus'] 		= $ostosuositus;
	
	$tuoterivi['ostoeramaara'] = ceil( $ostosuositus / $toimittajarow['pakkauskoko']);
	
	return $tuoterivi;
}

// tehd��n itse raportti
if ($tee == "RAPORTOI" and isset($ehdotusnappi)) {

	// haetaan nimitietoa
	if ($tuoryh != '') {
		// tehd��n avainsana query
		$sresult = t_avainsana("TRY", "", "and avainsana.selite ='$tuoryh'", $yhtiot);
		$trow1 = mysql_fetch_array($sresult);
	}
	if ($osasto != '') {
		// tehd��n avainsana query
		$sresult = t_avainsana("OSASTO", "", "and avainsana.selite ='$osasto'", $yhtiot);
		$trow2 = mysql_fetch_array($sresult);
	}
	if ($toimittajaid != '') {
		$query = "	SELECT nimi
					FROM toimi
					WHERE yhtio in ($yhtiot) and tunnus='$toimittajaid'";
		$sresult = mysql_query($query) or pupe_error($query);
		$trow3 = mysql_fetch_array($sresult);
	}

	$lisaa  = ""; // tuote-rajauksia
	$lisaa2 = ""; // toimittaja-rajauksia
	$lisaa3 = ""; // asiakas-rajauksia
	
	$varastosaldo = null;
	$myyntiennuste = null;
	$tilattu = null;
	
	if ($osasto != '') {
		$lisaa .= " and tilausrivi.osasto = '$osasto' ";
	}
	if ($tuoryh != '') {
		$lisaa .= " and tilausrivi.try = '$tuoryh' ";
	}
	if ($tuotemerkki != '') {
		$lisaa .= " and tilausrivi.tuotemerkki = '$tuotemerkki' ";
	}
	if ($toimittajaid != '') {
		$lisaa2 .= " JOIN tuotteen_toimittajat ON (tilausrivi.yhtio = tuotteen_toimittajat.yhtio and tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid') ";
	}
	if ($eliminoikonserni != '') {
		$lisaa3 .= " and asiakas.konserniyhtio = '' ";
	}

	$abcnimi = $ryhmanimet[$abcrajaus];

	$varastot_paikoittain = "";

	if (is_array($valitutvarastot) and count($valitutvarastot) > 0) {
		$varastot_paikoittain = "KYLLA";
	}

	$maa_varastot 			= "";
	$varastot_maittain		= "";

	if (is_array($valitutmaat) and count($valitutmaat) > 0) {
		$varastot_maittain = "KYLLA";

		// katsotaan valitut varastot
		$query = "	SELECT *
					FROM varastopaikat
					WHERE yhtio in ($yhtiot)";
		$vtresult = mysql_query($query) or pupe_error($query);

		while ($vrow = mysql_fetch_array($vtresult)) {
			if (in_array($vrow["maa"], $valitutmaat)) {
				$maa_varastot .= "'".$vrow["tunnus"]."',";
			}
		}
	}

	$maa_varastot 		 = substr($maa_varastot,0,-1);
	$maa_varastot_yhtiot = substr($maa_varastot_yhtiot,0,-1);

	// katotaan JT:ss� olevat tuotteet ABC-analyysi� varten, koska ne pit�� includata aina!
	$query = "	SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
				FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
				JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
				WHERE tilausrivi.yhtio in ($yhtiot)
				and tyyppi IN  ('L','G')
				and var = 'J'
				and jt $lisavarattu > 0";
	$vtresult = mysql_query($query) or pupe_error($query);
	$vrow = mysql_fetch_array($vtresult);

	$jt_tuotteet = "''";

	if ($vrow[0] != "") {
		$jt_tuotteet = $vrow[0];
	}

	if ($abcrajaus != "") {
		// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
		$abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tilausrivi.yhtio
					and abc_aputaulu.tuoteno = tilausrivi.tuoteno
					and abc_aputaulu.tyyppi = '$abcrajaustapa'
					and (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
	}
	else {
		$abcjoin = " LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tilausrivi.yhtio and abc_aputaulu.tuoteno = tilausrivi.tuoteno and abc_aputaulu.tyyppi = '$abcrajaustapa') ";
	}
	
	$toimilisa = "";
	if ($toimittajaid != '') $toimilisa = " and liitostunnus = '$toimittajaid' ";

	// Haetaan valmistukset annetulta ajanjaksolta
	$query = "	
		SELECT 
		lasku.kohde valmistuslinja
		, tilausrivi.tuoteno
		, tilausrivi.osasto
		, tilausrivi.try
		, tilausrivi.kpl+tilausrivi.tilkpl valmistuskpl
		, DATE_FORMAT( lasku.luontiaika, GET_FORMAT(DATE, 'EUR')) valmistuspvm
		, lasku.alatila valmistustila
		, abc_aputaulu.luokka abcluokka
		, abc_aputaulu.luokka_osasto abcluokka_osasto
		, abc_aputaulu.luokka_try abcluokka_try
		FROM tilausrivi
		$lisaa2
		$abcjoin
		JOIN lasku use index (PRIMARY) ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
		WHERE lasku.yhtio = '$kukarow[yhtio]'
		AND tilausrivi.tyyppi != 'D'
		AND tilausrivi.tyyppi = 'W'
		AND lasku.luontiaika >= '$vva1-$kka1-$ppa1 00:00:00'
		AND lasku.luontiaika < '$vvl1-$kkl1-$ppl1 00:00:00'
		$lisaa
		ORDER BY valmistuslinja, lasku.kohde DESC, lasku.luontiaika ASC"; 
	$res = mysql_query($query) or pupe_error($query);

	echo t("Valmistuksia")." ".mysql_num_rows($res)." ".t("kpl").".<br>\n";
	echo t("N�ytet��n valmistukset aikav�lille").": $ppa1.$kka1.$vva1 - $ppl1.$kkl1.$vvl1 <br>\n";

	if(@mysql_num_rows($res)>0){
		$headerivi = array(
			"tuoteno" 			=> t("tuotenumero")
			, "osasto" 			=> t("osasto")
			, "try" 			=> t("tuoteryhm�")
			, "valmistuskpl" 	=> t("valmistuksen m��r�")
			, "valmistuspvm" 	=> t("valmistuksen pvm")
			, "valmistustila" 	=> t("valmistuksen tila")
		);
		
		$tuoterivit  = array();
		
		$valmistusrivit = array(); // Tallennetaan t�nne, montako kunkin valmistuslinjan rivej� on
		while ($row = @mysql_fetch_array($res)) {
			$tuoterivi['tuoteno'] = $row['tuoteno'];
			$tuoterivi['osasto'] = $row['osasto'];
			$tuoterivi['try'] = $row['try'];
			$tuoterivi['valmistuskpl'] = $row['valmistuskpl'];
			$tuoterivi['valmistuspvm'] = $row['valmistuspvm'];
			$tuoterivi['valmistustila'] = $row['valmistustila'];
			$tuoterivi["valmistuslinja"] = ( !empty($row["valmistuslinja"]) ? $row['valmistuslinja'] : "Ei valmistuslinjaa" );
			
			$valmistusrivit[ $tuoterivi["valmistuslinja"] ] = $valmistusrivit[ $tuoterivi["valmistuslinja"] ] + 1;
			$tuoterivit[] = $tuoterivi;
		}

		// Kootaan raportti
		$ulos = "<form action='' method='post'><table>";
		
		// Header-rivi
		$header = "<tr>";
		foreach($headerivi as $arvo){
			$header .= "<th>$arvo</th>";
		}
		$header .= "</tr>";
		
		// Tuoterivit
		foreach( $tuoterivit as $tuoterivi ){
			
			// Tarkistetaan tarviiko valmistuslinjalle piirt�� v�liotsikkoa
			if(!isset($EDlinja) or $EDlinja != $tuoterivi['valmistuslinja']){
				$valmistuslinja = ( isset($tuoterivi['valmistuslinja']) ? $tuoterivi['valmistuslinja'] : t('Ei valmistuslinjaa') );
				$linjariveja = $valmistusrivit[ $tuoterivi['valmistuslinja'] ];
				$ulos .= "<tr><th colspan = '2'>$valmistuslinja / $linjariveja ".t('rivi�')."</th></tr>";
				$ulos .= $header;
			}
			$EDlinja = $tuoterivi['valmistuslinja'];
			
			// Sarakkeet riveille
			$ulos .= "<tr>";
			foreach( $tuoterivi as $avain => $arvo ){
				if(array_key_exists( $avain, $headerivi ) ){
					$ulos .= "<td>$arvo</td>";
				}
			}
			$ulos .= "</tr>";
		
		}
		$ulos .= "</table><input type='submit' name='muodostaValmistukset' value='".t('Muodosta valmistukset')."' /></form>";
		
		// N�ytet��n valmistukset
		echo $ulos;
	}
	else echo t('Ei valmistuksia haetulla aikav�lill�')."<br>\n";
	
	// Haetaan valmistettavat tuotteet
	$query = "	
	SELECT 
	lasku.kohde valmistuslinja
	, tilausrivi.tuoteno
	, tilausrivi.osasto
	, tilausrivi.try
	, tilausrivi.kpl+tilausrivi.tilkpl valmistuskpl
	, DATE_FORMAT( lasku.luontiaika, GET_FORMAT(DATE, 'EUR')) valmistuspvm
	, lasku.alatila valmistustila
	, group_concat(distinct tuoteperhe.isatuoteno separator ',') sisartuoteno
	, tuoteperhe.kerroin
	, abc_aputaulu.luokka abcluokka
	, abc_aputaulu.luokka_osasto abcluokka_osasto
	, abc_aputaulu.luokka_try abcluokka_try
	, group_concat(distinct tuotteen_toimittajat.toimittaja order by tuotteen_toimittajat.tunnus separator '/') toimittaja
	, group_concat(distinct tuotteen_toimittajat.osto_era order by tuotteen_toimittajat.tunnus separator '/') osto_era
	, group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno
	, group_concat(distinct tuotteen_toimittajat.toim_nimitys order by tuotteen_toimittajat.tunnus separator '/') toim_nimitys
	, group_concat(distinct tuotteen_toimittajat.ostohinta order by tuotteen_toimittajat.tunnus separator '/') ostohinta
	, group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '/') tuotekerroin
	FROM tilausrivi
	$lisaa2
	$abcjoin
	LEFT JOIN lasku use index (PRIMARY) ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus)
	LEFT JOIN tuoteperhe ON (tilausrivi.tuoteno = tuoteperhe.tuoteno)
	LEFT JOIN tuotteen_toimittajat ON (tilausrivi.tuoteno = tuotteen_toimittajat.tuoteno)
	WHERE lasku.yhtio = '$kukarow[yhtio]'
	AND tilausrivi.tyyppi != 'D'
	AND tilausrivi.tyyppi = 'W'
	AND lasku.luontiaika >= '$vva1-$kka1-$ppa1 00:00:00'
	AND lasku.luontiaika < '$vvl1-$kkl1-$ppl1 00:00:00'
	$lisaa
	ORDER BY valmistuslinja, lasku.kohde DESC, lasku.luontiaika ASC";
	$res = mysql_query($query) or pupe_error($query); echo $query;
	
}

// N�ytet��n k�ytt�liittym�
if ($tee == "" or !isset($ehdotusnappi)) {

	$abcnimi = $ryhmanimet[$abcrajaus];

	echo "	<form action='$PHP_SELF' method='post' autocomplete='off'>
			<input type='hidden' name='tee' value='RAPORTOI'>

			<table>";

	echo "<tr><th>".t("Osasto")."</th><td colspan='3'>";

	// tehd��n avainsana query
	$sresult = t_avainsana("OSASTO", "", "", $yhtiot);

	echo "<select name='osasto'>";
	echo "<option value=''>".t("N�yt� kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($osasto == $srow["selite"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
	}
	echo "</select>";

	echo "</td></tr>
			<tr><th>".t("Tuoteryhm�")."</th><td colspan='3'>";

	// tehd��n avainsana query
	$sresult = t_avainsana("TRY", "", "", $yhtiot);

	echo "<select name='tuoryh'>";
	echo "<option value=''>".t("N�yt� kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($tuoryh == $srow["selite"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
	}
	echo "</select>";

	echo "</td></tr>
			<tr><th>".t("Tuotemerkki")."</th><td colspan='3'>";

	//Tehd��n osasto & tuoteryhm� pop-upit
	$query = "	SELECT distinct tuotemerkki
				FROM tuote
				WHERE yhtio in ($yhtiot) and tuotemerkki != ''
				ORDER BY tuotemerkki";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuotemerkki'>";
	echo "<option value=''>".t("N�yt� kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($tuotemerkki == $srow["tuotemerkki"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
	}
	echo "</select>";

	echo "</td></tr>";

	// katotaan onko abc aputaulu rakennettu
	$query  = "select count(*) from abc_aputaulu where yhtio in ($yhtiot) and tyyppi in ('TK','TR','TP')";
	$abcres = mysql_query($query) or pupe_error($query);
	$abcrow = mysql_fetch_array($abcres);

	// jos on niin n�ytet��n t�ll�nen vaihtoehto
	if ($abcrow[0] > 0) {
		echo "<tr><th>".t("ABC-luokkarajaus/rajausperuste")."</th><td colspan='3'>";

		$sel = array();
		$sel[$abcrajaus] = "SELECTED";

		echo "<select name='abcrajaus' onchange='submit()'>
		<option value=''>".t("Ei rajausta")."</option>
		<option $sel[0] value='0'>".t("Luokka A-30")."</option>
		<option $sel[1] value='1'>".t("Luokka B-20 ja paremmat")."</option>
		<option $sel[2] value='2'>".t("Luokka C-15 ja paremmat")."</option>
		<option $sel[3] value='3'>".t("Luokka D-15 ja paremmat")."</option>
		<option $sel[4] value='4'>".t("Luokka E-10 ja paremmat")."</option>
		<option $sel[5] value='5'>".t("Luokka F-05 ja paremmat")."</option>
		<option $sel[6] value='6'>".t("Luokka G-03 ja paremmat")."</option>
		<option $sel[7] value='7'>".t("Luokka H-02 ja paremmat")."</option>
		<option $sel[8] value='8'>".t("Luokka I-00 ja paremmat")."</option>
		</select>";

		$sel = array();
		$sel[$abcrajaustapa] = "SELECTED";

		echo "<select name='abcrajaustapa'>
		<option $sel[TK] value='TK'>".t("Myyntikate")."</option>
		<option $sel[TR] value='TR'>".t("Myyntirivit")."</option>
		<option $sel[TP] value='TP'>".t("Myyntikappaleet")."</option>
		</select>
		</td></tr>";
	}

	echo "<tr><th>".t("Toimittaja")."</th><td colspan='3'><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";
	echo "<input type='hidden' name='edytunnus' value='$ytunnus'>";
	echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
	
	echo "</table><table><br>";

	echo "	<tr>
			<th></th><th colspan='3'>".t("Alkup�iv�m��r� (pp-kk-vvvv)")."</th>
			<th></th><th colspan='3'>".t("Loppup�iv�m��r� (pp-kk-vvvv)")."</th>
			</tr>";

	echo "	<tr><th>".t("Aikav�li")."</th>
			<td><input type='text' name='ppa1' value='$ppa1' size='5'></td>
			<td><input type='text' name='kka1' value='$kka1' size='5'></td>
			<td><input type='text' name='vva1' value='$vva1' size='5'></td>
			<td class='back'>&nbsp;-&nbsp;</td>
			<td><input type='text' name='ppl1' value='$ppl1' size='5'></td>
			<td><input type='text' name='kkl1' value='$kkl1' size='5'></td>
			<td><input type='text' name='vvl1' value='$vvl1' size='5'></td>
			</tr>";

	echo "</table><table><br>";

	$yhtiot = "'$kukarow[yhtio]'";

	//Valitaan varastot joiden saldot huomioidaan
	$query = "	SELECT *
				FROM varastopaikat
				WHERE yhtio in ($yhtiot)
				ORDER BY yhtio, nimitys";
	$vtresult = mysql_query($query) or pupe_error($query);

	$vlask = 0;

	if (mysql_num_rows($vtresult) > 0) {

		echo "<tr><th>".t("Huomioi saldot, myynnit ja ostot varastoittain:")."</th></tr>";

		while ($vrow = mysql_fetch_array($vtresult)) {

			$chk = "";
			if (is_array($valitutvarastot) and in_array($vrow["tunnus"], $valitutvarastot) != '') {
				$chk = "CHECKED";
			}

			echo "<tr><td><input type='checkbox' name='valitutvarastot[]' value='$vrow[tunnus]' $chk>";

			echo "$vrow[nimitys] ";

			if ($vrow["tyyppi"] != "") {
				echo " *$vrow[tyyppi]* ";
			}
			if ($useampi_maa == 1) {
				echo "(".maa($vrow["maa"]).")";
			}

			echo "</td></tr>";
		}
	}
	else {
		echo "<font class='error'>".t("Yht��n varastoa ei l�ydy, raporttia ei voida ajaa")."!</font>";
		exit;
	}

	echo "</table>";
	echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Suunnittele valmistus")."'></form>";
}

require ("../inc/footer.inc");

?>