<?php

$_GET["ohje"] = "off";

require ("parametrit.inc");

if ($verkkokauppa == "") die("Verkkokuapayhti� m��rittelem�tt�");

if (!function_exists("tilaus")) {
	function tilaus($tunnus, $muokkaa = "") {
		global $yhtiorow, $kukarow, $verkkokauppa;

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus' and liitostunnus = '$kukarow[oletus_asiakas]'";
		$result = mysql_query($query) or pupe_error($query);
		$laskurow = mysql_fetch_array($result);

		if ($muokkaa != "" and ($laskurow["tila"] != "N" or $laskurow["alatila"] != "" or $kukarow["kesken"] != $tunnus)) {
			$ulos .= "<font class='error'>".t("Tilausta ei voi en�� muokata")."!</font><br>";
			$muokkaa = "";
		}

		if ($muokkaa != "") {
			$ulos .= "	<form id = 'laskutiedot' name = 'laskutiedot' method='POST' action=\"javascript:ajaxPost('laskutiedot', 'verkkokauppa.php?', 'selain', false, true);\">
						<input type='hidden' name='tee' value = 'tallenna_osoite'>
						<input type='hidden' name='osasto' value = '$osasto'>
						<input type='hidden' name='tuotemerkki' value = '$tuotemerkki'>
						<input type='hidden' name='try' value = '$try'>";
		}				
	
		ob_start();
		require("naytatilaus.inc");
		$ulos .= ob_get_contents();
		ob_end_clean();

		return $ulos;
	}
}

if (!function_exists("menu")) {
	function menu($osasto="", $try="") {
		global $yhtiorow, $kukarow, $verkkokauppa_tuotemerkit;

		$val = "";

		if ($kukarow["kuka"] != "www") {
			$toimlisa = "<tr><td class='back'>&raquo; <a href=\"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=toim_tuoteno', 'selain', false, true);\">".t("Toimittajan koodilla")."</a></td></tr>";
		}
		else {
			$toimlisa = "";
		}

		if ($verkkokauppa_tuotemerkit) {
			$tuotemerkkilis = " and tuote.tuotemerkki != '' ";
		}
		else {
			$tuotemerkkilis = "";
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
			$vierow = mysql_fetch_array($vieres);
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

		if (isset($vierow) and $vierow["maa"] != "") {
			$kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
		}

		if ($kukarow["kieli"] == "") {
			$kukarow["kieli"] = "FI";
		}

		if ($osasto == "") {
			$val =  "<table id='rootMenu' name='rootMenu' class='menutable' style='visibility: hidden;'>";

			$result = t_avainsana("VERKKOKAULINKKI");

			while ($orow = mysql_fetch_array($result)) {
				if ($orow["selite"] == "ETUSIVU") $val .= "<tr><td class='menucell'><a class='menu' href = ''>".t("Etusivu")."</a></td></tr>";
				else $val .= "<tr><td class='menucell'><a class='menu' href = \"javascript:sndReq('selain', 'verkkokauppa.php?tee=uutiset&sivu=$orow[selite]', false, false);\">$orow[selitetark]</a></td></tr>";
			}


			if ($verkkokauppa_anon or $kukarow["kuka"] != "www") {
				$val .=  "<tr><td class='back'><br><font class='info'>".t("Tuotteet").":</font><hr></td></tr>";

				$ores = t_avainsana("OSASTO", "", " and avainsana.nakyvyys = '' ");

				while ($orow = mysql_fetch_array($ores)) {
					$target		= "T_".$orow["selite"];
					$parent		= "P_".$orow["selite"];

					$onclick	= "document.getElementById(\"$target\").style.display==\"none\"? sndReq(\"selain\", \"verkkokauppa.php?tee=uutiset&osasto=$orow[selite]\", \"\", false) : \"\";";
					$href 		= "javascript:sndReq(\"$target\", \"verkkokauppa.php?tee=menu&osasto=$orow[selite]\", \"$parent\", false, false);";
					$val .=  "<tr><td class='menucell'><a class = 'menu' id='$parent' onclick='$onclick' href='$href'>$orow[selitetark]</a></td></tr>
								<tr><td class='menuspacer'><div id='$target' style='display: none'></div></td></tr>";
				}

				$val .= "<tr><td class='back'><br><font class='info'>".t("Tuotehaku").":</font><hr></td></tr>
						 	<tr><td class='back'><form id = 'tuotehaku' name='tuotehaku'  action = \"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=nimi', 'selain', false, true);\" method = 'post'>
							<input type = 'text' size='12' name = 'tuotehaku'>
							<tr><td class='back'>&raquo; <a href=\"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=nimi', 'selain', false, true);\">".t("Nimityksell�")."</a></td></tr>
							<tr><td class='back'>&raquo; <a href=\"javascript:ajaxPost('tuotehaku', 'verkkokauppa.php?tee=selaa&hakutapa=koodilla', 'selain', false, true);\">".t("Tuotekoodilla")."</a></td></tr>
							$toimlisa
							</form>
							</td>
							</tr>";
			}

			$val .= "</table><script>setTimeout(\"document.getElementById('rootMenu').style.visibility='visible';\", 250)</script>";
		}
		elseif ($try == "" and ($verkkokauppa_anon or $kukarow["kuka"] != "www")) {
			$val = "<table class='menutable'>";

			$query = "	SELECT distinct avainsana.selite try,
						IFNULL((SELECT avainsana_kieli.selitetark
				        FROM avainsana as avainsana_kieli
				        WHERE avainsana_kieli.yhtio = avainsana.yhtio
				        and avainsana_kieli.laji = avainsana.laji
				        and avainsana_kieli.perhe = avainsana.perhe
				        and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) trynimi
						FROM tuote
						JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.try = avainsana.selite and avainsana.laji = 'TRY' and avainsana.kieli in ('$yhtiorow[kieli]', '') and avainsana.nakyvyys = '')
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.osasto = '$osasto'
						$kieltolisa
						$tuotemerkkilis
						and tuote.status != 'P'
						and tuote.hinnastoon IN ('W', 'V')
						ORDER BY avainsana.jarjestys, avainsana.selite+0";
			$tryres = mysql_query($query) or pupe_error($query);

			while ($tryrow = mysql_fetch_array($tryres)) {

				//	Oletuksena pimitet��n kaikki..
				$ok = 0;

				//	Tarkastetaan onko t��ll� sopivia tuotteita
				$query = "	SELECT *
				 			FROM tuote
							WHERE yhtio 	= '$kukarow[yhtio]'
							and osasto 		= '$osasto'
							and try 		= '$tryrow[try]'
							and status 	   != 'P'
							$kieltolisa
							$tuotemerkkilis
							and hinnastoon IN ('W','V')";
				$res = mysql_query($query) or pupe_error($query);

				while ($trow = mysql_fetch_array($res) and $ok == 0) {

					//	JOS asiakkaalle n�ytet��n vain tuotteet jossa on asiakasale/hinta tai jos tuote n�kyy verkkokaupassa vain jos asiakkaalla on asiakasale/hinta
					if ($trow["hinnastoon"] == "V" or $kukarow["naytetaan_tuotteet"] == "A") {

						if (!is_array($asiakasrow)) {
							$query = "	SELECT *
										FROM asiakas
										WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
							$asres = mysql_query($query) or pupe_error($query);
							$asiakasrow = mysql_fetch_array($asres);
						}

						$hinnat = alehinta(array(
									"valkoodi" => "EUR",
									"maa" => $yhtiorow["maa"],
									"vienti_kurssi" => 1,
									"liitostunnus" => $asiakasrow["tunnus"],
									"ytunnus" => $asiakasrow["ytunnus"]) , $trow, 1, '', '', '', "hintaperuste,aleperuste");

						if ($hinnat["hintaperuste"] >= 2 and $hinnat["hintaperuste"] <= 12) {
							$ok = 1;
						}
						if ($hinnat["aleperuste"] >= 5 or $hinnat["aleperuste"] <= 8) {
							$ok = 1;
						}
					}
					else {
						$ok = 1;
					}
				}

				if ($ok == 1) {
					$target	= "P_".$osasto."_".$tryrow["try"];
					$parent	= "T_".$osasto."_".$tryrow["try"];

					if ($verkkokauppa_tuotemerkit) {
						$href 	= "javascript:sndReq(\"$target\", \"verkkokauppa.php?tee=menu&osasto=$osasto&try=$tryrow[try]\", \"$parent\", false); sndReq(\"selain\", \"verkkokauppa.php?tee=selaa&osasto=$osasto&try=$tryrow[try]&tuotemerkki=\", \"\", false);";
						$val .=  "<tr><td class='menuspacer'>&nbsp;</td><td class='menucell'><a class = 'menu' id='$parent' href='$href'>$tryrow[trynimi]</a><div id=\"$target\" style='display: none'></div></td></tr>";
					}
					else {
						$val .=  "<tr><td class='menuspacer'>&nbsp;</td><td class='menucell'><a class = 'menu' name = 'menulinkki' id='$parent' onclick=\"var aEls = document.getElementsByName('menulinkki'); for (var iEl = 0; iEl < aEls.length; iEl++) { document.getElementById(aEls[iEl].id).className='menu';} this.className='menuselected'; self.scrollTo(0,0);\" href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto=$osasto&try=$tryrow[try]&tuotemerkki=', '', false);\">$tryrow[trynimi]</a></td></tr>";
					}
				}
			}
			$val .= "</table>";
		}
		elseif ($verkkokauppa_tuotemerkit and ($verkkokauppa_anon or $kukarow["kuka"] != "www")) {
			$val = "<table class='menutable'>";

			$query = "	SELECT distinct avainsana.selite as tuotemerkki,
						IFNULL((SELECT avainsana_kieli.selite
	        			FROM avainsana as avainsana_kieli
	        			WHERE avainsana_kieli.yhtio = avainsana.yhtio
	        			and avainsana_kieli.laji = avainsana.laji
	        			and avainsana_kieli.perhe = avainsana.perhe
	        			and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selite) selite
						FROM tuote
						JOIN avainsana ON (avainsana.yhtio = tuote.yhtio and tuote.tuotemerkki = avainsana.selite and avainsana.laji = 'TUOTEMERKKI' and avainsana.nakyvyys = '')
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.osasto = '$osasto'
						and tuote.try = '$try'
						$kieltolisa
						$tuotemerkkilis
						and tuote.status != 'P'
						and tuote.hinnastoon IN ('W', 'V')
						ORDER BY avainsana.jarjestys, avainsana.selite";
			$meres = mysql_query($query) or pupe_error($query);

			while ($merow = mysql_fetch_array($meres)) {

				//	Oletuksena pimitet��n kaikki..
				$ok = 0;

				//	Tarkastetaan onko t��ll� sopivia tuotteita
				$query = "	SELECT *
				 			FROM tuote
							WHERE yhtio 	= '$kukarow[yhtio]'
							and osasto 		= '$osasto'
							and try 		= '$try'
							and tuotemerkki = '$merow[tuotemerkki]'
							and status 	   != 'P'
							$kieltolisa
							and hinnastoon IN ('W', 'V')";
				$res = mysql_query($query) or pupe_error($query);

				while ($trow = mysql_fetch_array($res) and $ok == 0) {

					//	JOS asiakkaalle n�ytet��n vain tuotteet jossa on asiakasale/hinta tai jos tuote n�kyy verkkokaupassa vain jos asiakkaalla on asiakasale/hinta
					if ($trow["hinnastoon"] == "V" or $kukarow["naytetaan_tuotteet"] == "A") {

						if (!is_array($asiakasrow)) {
							$query = "	SELECT *
										FROM asiakas
										WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
							$asres = mysql_query($query) or pupe_error($query);
							$asiakasrow = mysql_fetch_array($asres);
						}

						$hinnat = alehinta(array(
									"valkoodi" => "EUR",
									"maa" => $yhtiorow["maa"],
									"vienti_kurssi" => 1,
									"liitostunnus" => $asiakasrow["tunnus"],
									"ytunnus" => $asiakasrow["ytunnus"]) , $trow, 1, '', '', '', "hintaperuste,aleperuste");

						if ($hinnat["hintaperuste"] >= 2 and $hinnat["hintaperuste"] <= 12) {
							$ok = 1;
						}
						if ($hinnat["aleperuste"] >= 5 or $hinnat["aleperuste"] <= 8) {
							$ok = 1;
						}
					}
					else {
						$ok = 1;
					}
				}

				if ($ok == 1) {
					$val .=  "<tr><td class='menuspacer'>&nbsp;</td><td class='menucell'><a class = 'menu' id='P_".$osasto."_".$try."_".$merow["selite"]."' name = 'menulinkki' onclick=\"var aEls = document.getElementsByName('menulinkki'); for (var iEl = 0; iEl < aEls.length; iEl++) { document.getElementById(aEls[iEl].id).className='menu';} this.className='menuselected'; self.scrollTo(0,0);\" href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto=$osasto&try=$try&tuotemerkki=$merow[tuotemerkki]', '', true);\">$merow[tuotemerkki]</a></td></tr>";
				}
			}
			$val .= "</table>";
		}

		return $val;
	}
}

if (!function_exists("uutiset")) {
	function uutiset($osasto="", $try="", $sivu="") {
		global $yhtiorow, $kukarow;

		if ($sivu != "") {
			$lisa = "and tyyppi = 'VERKKOKAUPPA' and kentta09 = '$sivu' ";
		}
		else {

			if ($osasto == "") {
				$lisa = "and tyyppi = 'VERKKOKAUPPA' and kentta09 = '' and kentta10 = ''";
			}
			elseif ($try == "") {
				$lisa = "and tyyppi = 'VERKKOKAUPPA' and kentta09 = '$osasto' and kentta10 = ''";
			}
			else {
				$lisa = "and tyyppi = 'VERKKOKAUPPA' and kentta09 = '$osasto' and kentta10 = '$try'";
			}
		}

		// Ekotetaan avoin kori
		$val = avoin_kori();

		$query = "	SELECT *
					FROM kalenteri
					WHERE yhtio = '$kukarow[yhtio]'  $lisa
					ORDER BY kokopaiva DESC , luontiaika DESC
					LIMIT 8";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 0) {
			$val .= "<table class='uutinen'>";

			while ($row = mysql_fetch_array($result)) {
				$val .= "<tr>";

				if ($row["kentta03"] > 0) {
					$val .= "<td class='back'><img class='uutinen' width='100px' src='view.php?id=$row[kentta03]'></td>";
				}
				else {
					$val .= "<td class='back'></td>";
				}

				$val .= "<td class='back'>";

				$search = "/#{2}([^#]+)#{2}(([^#]+)#{2}){0,1}/";
				preg_match_all($search, $row["kentta02"], $matches, PREG_SET_ORDER);

				if (count($matches) > 0) {
					$search = array();
					$replace = array();

					foreach($matches as $m) {

						//	Haetaan tuotenumero
						$query = "	SELECT tuoteno, nimitys
						 			FROM tuote
									WHERE yhtio = '$kukarow[yhtio]' and tuoteno like ('$m[1]%')";
						$tres = mysql_query($query) or pupe_error($query);

						//	T�m� me korvataan aina!
						$search[] = "/$m[0]/";

						if (mysql_num_rows($tres) <> 1) {
							$replace[]	= "";
						}
						else {
							$trow = mysql_fetch_array($tres);
							if (count($m) == 4) {
								$replace[]	= "<a href = \"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&hakutapa=koodilla&tuotehaku=$m[1]', false, true);\">$m[3]</a>";
							}
							else {
								$replace[]	= "<a href = \"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&hakutapa=koodilla&tuotehaku=$m[1]', false, true);\">$trow[nimitys]</a>";
							}
						}
					}

					$row["kentta02"] = preg_replace($search, $replace, $row["kentta02"]);
				}

				$val .= "<font class='head'>$row[kentta01]</font><br>$row[kentta02]</font><hr>";
				$val .= "</td></tr>";

			}
			$val .= "</table>";
		}

		return $val;
	}
}

if ($tee == "menu") {
	die(menu($osasto, $try));
}

if ($tee == "monistalasku") {
	//	Tehd��n t�m� funktiossa niin ei saada v��ri� muuttujia injisoitua
	function monistalasku($laskunro) {
		global $yhtiorow, $kukarow;

		$query = "	SELECT tunnus
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]' and liitostunnus='$kukarow[oletus_asiakas]' and laskunro = '$laskunro' and tila = 'U'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result)==1) {
			$row = mysql_fetch_array($result);

			//	Passataan oikea array
			$monistettavat[$row["tunnus"]] = "";
			$kklkm = 1;
			$tee = "MONISTA";
			$vain_monista = "utilaus";


			require("monistalasku.php");

			return end($tulos_ulos);
		}
		else {
			return false;
		}
	}

	$tilaus = monistalasku($laskunro);
	if ($tilaus===false) {
		echo "<font class='error'>".t("Tilauksen monistaminen ep�onnistui")."!!</font><br>";
	}
}

if ($tee == "jatkatilausta") {
	if ((int) $tilaus > 0) {
		$kukarow["kesken"] = $tilaus;
		$query = "	UPDATE kuka SET kesken = '$tilaus' WHERE yhtio ='$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("Aktivoitiin tilaus %s", $kieli, $tilaus)."</font><br>";

		$tee = "tilatut";
	}
}

if ($tee == "keskeytatilaus") {
	if ((int) $tilaus > 0) {
		$kukarow["kesken"] = 0;
		$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio ='$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("J�tettiin tilaus %s kesken", $kieli, $tilaus)."</font><br>";
	}
}

if ($tee == "uutiset") {
	die(uutiset($osasto, $try, $sivu));
}

if ($tee == "tuotteen_lisatiedot") {

	$query = "	SELECT kuvaus, lyhytkuvaus
				FROM tuote
				WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno' and hinnastoon in ('W','V')";
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($result);

	//	Eka rivi aina head
	if (trim($row["kuvaus"]) == "" and trim($row["lyhytkuvaus"]) == "") {
		$row["tekstit"] = t("Tuotteestamme ei ole t�ll�hetkell� lis�tietoja..");
	}
	else {
		$row["tekstit"] = $row["lyhytkuvaus"]."\n<br>".$row["kuvaus"];
		//$row["tekstit"] = preg_replace("/(.*)/", "<font class='head'>\$1</font>", $row["tekstit"], 1);
	}

	if ($row["tekstit"] != "") {
		$search = $replace = array();
		// Shit! joudutaan tutkimaan mille v�lille tehd��n li
		$search[] 	= "/#{2}\+/";
		$replace[] 	= "<ul class='ruutu'>";
		$search[] 	= "/#{2}\-/";
		$replace[]	= "</ul>";

		$search[] 	= "/\*{2}\+/";
		$replace[] 	= "<ul class='pallo'>";
		$search[] 	= "/\*{2}\-/";
		$replace[] 	= "</ul>";

		$search[] 	= "/\*{2}([^\+\-].*)|\#{2}([^\+\-].*)/";
		$replace[] 	= "<li>\$1\$2</li>";

		$search[]	= "/\?\?(.*)\?\?/m";
		$replace[]	= "<font class='italic'>\$1</font>";
		$search[]	= "/\!\!(.*)\!\!/m";
		$replace[]	= "<font class='bold'>\$1</font>";

		$tekstit = preg_replace($search, $replace, nl2br($row["tekstit"]));
	}
	else {
		$tekstit = "";
	}

	if ($kukarow["kuka"] == "www") {
		$liitetyypit = array("public");
	}
	else {
		$liitetyypit = array("extranet","public");
	}

	//	Haetaan kaikki liitetiedostot
	$query = "	SELECT liitetiedostot.tunnus, liitetiedostot.selite, liitetiedostot.kayttotarkoitus, avainsana.selitetark, avainsana.selitetark_2,
				(select tunnus from liitetiedostot l where l.yhtio=liitetiedostot.yhtio and l.liitos=liitetiedostot.liitos and l.liitostunnus=liitetiedostot.liitostunnus and l.kayttotarkoitus='TH' ORDER BY l.tunnus DESC LIMIT 1) peukalokuva
				FROM tuote
				JOIN liitetiedostot ON liitetiedostot.yhtio = tuote.yhtio and liitos = 'TUOTE' and liitetiedostot.liitostunnus=tuote.tunnus
				JOIN avainsana ON avainsana.yhtio = liitetiedostot.yhtio and avainsana.laji = 'LITETY' and avainsana.selite!='TH' and avainsana.selite=liitetiedostot.kayttotarkoitus
				WHERE tuote.yhtio = '$kukarow[yhtio]'
				and tuote.tuoteno = '$tuoteno'
				and tuote.hinnastoon in ('W','V')
				ORDER BY liitetiedostot.kayttotarkoitus IN ('TK') DESC, liitetiedostot.selite";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		$liitetiedostot = $edkaytto = "";

		while($row = mysql_fetch_array($result)) {
			if ($row["kayttotarkoitus"] == "TK") {
				if ($row["peukalokuva"] > 0) {
					$liitetiedostot .= "$row[selite]<br><a href='view.php?id=$row[tunnus]' target='_blank'><img src='view.php?id=$row[peukalokuva]'></a><br><font class='info'>".t("Klikkaa kuvaa")."</info><br>";
				}
				else {
					$liitetiedostot .= "<a href='view.php?id=$row[tunnus]'  class='liite'>$row[selite]</a><br>";
				}
			}
			else {
				if (in_array($row["selitetark_2"], $liitetyypit)) {

					if ($edkaytto != $row["kayttotarkoitus"]) {
						$liitetiedostot .= "<br><br><font class='bold'>$row[selitetark]</font><br>";
						$edkaytto = $row["kayttotarkoitus"];
					}

					$liitetiedostot .= "<a href='view.php?id=$row[tunnus]' class='liite'>$row[selite]</a><br>";
				}
			}
		}
	}

	if ($liitetiedostot == "") {
		$liitetiedostot = "Tuotteesta ei ole kuvia";
	}

	//	Vasemmalla meill� on kaikki tekstit
	echo "<table width='100%'><tr><td class='back' style='width:50px;'></td><td class='back'>$tekstit</td><td class='back'>$liitetiedostot</td></tr><tr><td class='back'><br></td></tr></table>";
}

if ($tee == "poistakori") {
	$query = "	SELECT tunnus
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila=''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		// l�yty vaan yks dellataan se
		//$ostoskori = mysql_fetch_array($result);
		$kalakori = mysql_fetch_array($result);

		$query = "	delete from tilausrivi
					where yhtio = '$kukarow[yhtio]' and
					tyyppi = 'L' and
					otunnus = '$kalakori[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	DELETE FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and
					tila = 'N' and
					tunnus = '$kukarow[kesken]' and
					alatila=''";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	delete from lasku
					where yhtio = '$kukarow[yhtio]' and
					tila = 'N' and
					tunnus = '$kalakori[tunnus]'";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	UPDATE kuka SET kesken = '' WHERE yhtio ='$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<center><font class ='message'>".t("Tilaus %s mit�t�ity", $kieli, $kukarow["kesken"])."</font></center>";
		$kukarow["kesken"] = 0;
	}
}

if ($tee == "poistarivi") {
	$query = "	SELECT tunnus
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila=''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		// l�yty vaan yks dellataan siit� rivi
		//$ostoskori = mysql_fetch_array($result);
		$kalakori = mysql_fetch_array($result);

		$query = "	DELETE FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and tunnus = '$rivitunnus' LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);
	}

	$tee = "tilatut";
}

if ($tee == "tallenna_osoite") {
	$query = "	SELECT tunnus, toimaika
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila=''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$kalakori = mysql_fetch_array($result);

		$query = "	UPDATE lasku SET
						toim_nimi 				= '$toim_nimi',
						toim_nimitark 			= '$toim_nimitark',
						toim_osoite 			= '$toim_osoite',
						toim_postino 			= '$toim_postino',
						toim_postitp 			= '$toim_postitp',
						tilausyhteyshenkilo		= '$tilausyhteyshenkilo',
						asiakkaan_tilausnumero	= '$asiakkaan_tilausnumero',
						kohde					= '$kohde',
						viesti					= '$viesti',
						comments				= '$comments',
						toimaika				= '$toimvv-$toimkk-$toimpp'
					WHERE yhtio = '$kukarow[yhtio]' and tila = 'N' and tunnus = '$kukarow[kesken]' LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);

		$query = "	UPDATE tilausrivi
					SET toimaika = '".$toimvv."-".$toimkk."-".$toimpp."'
					WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kalakori[tunnus]' and toimaika = '$kalakori[toimaika]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	$tee = "tilatut";
}

if ($tee == "tilaa") {
	$query = "	SELECT tunnus
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila=''";
	$result = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($result) == 1) {

		$laskurow = mysql_fetch_array($result);

		//	Hyv�ksynn�n kautta
		if ($kukarow["taso"] == 2) {
			$query  = "	UPDATE lasku set
						tila = 'N',
						alatila='F'
						WHERE yhtio='$kukarow[yhtio]'
						and tunnus='$kukarow[kesken]'
						and tila = 'N'
						and alatila = ''";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			// tulostetaan l�hetteet ja tilausvahvistukset tai sis�inen lasku..
			$silent = "JOO";
			require("tilaus-valmis.inc");
		}

		$ulos = "<font class='head'>Kiitos tilauksesta</font><br><br><font class='message'>Tilauksesi numero on $kukarow[kesken]</font><br>";


		// tilaus ei en�� kesken...
		$query	= "update kuka set kesken=0 where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
		$result = mysql_query($query) or pupe_error($query);
	}


	die($ulos);
}

if ($tee == "tilatut") {

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and
				tila = 'N' and
				tunnus = '$kukarow[kesken]' and
				alatila = ''";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		$tee = "selaa";
	}
	else {
		$laskurow = mysql_fetch_array($result);

		$ulos = "<font class='head'>".t("Tilauksen %s tuotteet", $kieli, $kukarow["kesken"])." </font><br>";

		if ($osasto != "" and $try != "") {
			$ulos .= "<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=selaa&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki')\">".t("Takaisin selaimelle")."</a>&nbsp;&nbsp;";
		}

		$query = "	SELECT count(*) rivei
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]' and tyyppi = 'L'";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);

		$ulos .= "<br><input type='button' onclick=\"if(confirm('".t("Oletko varma, ett� haluat j�tt�� tilauksen %s kesken?", $kieli, $laskurow["tunnus"])."')) { sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&tee=keskeytatilaus&tilaus=$laskurow[tunnus]', false, true); }\" value='".t("J�t� kesken")."'>&nbsp;&nbsp;";

		if ($row["rivei"] > 0) {
			$ulos .= "<input type='button' onclick=\"if(confirm('".t("Oletko varma, ett� haluat l�hett�� tilauksen eteenp�in?")."')) { sndReq('selain', 'verkkokauppa.php?tee=tilaa'); }\" value='".t("Tilaa tuotteet")."'>&nbsp;&nbsp;";
		}

		$ulos .= "<input type='button' onclick=\"if(confirm('".t("Oletko varma, ett� haluat mit�t�id� tilauksen?")."')) { sndReq('selain', 'verkkokauppa.php?tee=poistakori&osasto=$osasto&try=$try&tuotemerkki=$tuotemerkki'); }\" value='".t("Mit�t�i tilaus")."'>";

		$ulos .= "<br><br>";

		$ulos .= tilaus($kukarow["kesken"], "JOO");

		die($ulos);
	}
}

if ($tee == "asiakastiedot") {

	// Ekotetaan avoin kori
	echo avoin_kori();
	
	if ($nayta == "") $nayta = "asiakastiedot";

	if ($nayta == "asiakastiedot") {
		$query = "	SELECT *,
							concat_ws('<br>', nimi, nimitark, osoite, postino, postitp) laskutusosoite,
							concat_ws('<br>', toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp) toimitusosoite
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
		$result = mysql_query($query) or pupe_error($query);
		$asiakasrow = mysql_fetch_array($result);

		echo "<table>
				<tr>
					<th>".t("Laskutusosoite")."</th><th>".t("Toimitusosoite")."</th>
				</tr>
				<tr>
					<td>$asiakasrow[laskutusosoite]</td><td>$asiakasrow[toimitusosoite]</td>
				</tr>
			</table>";
	}
	elseif ($nayta == "tilaushistoria") {

		if ($kukarow["naytetaan_tilaukset"] != "O") {
			$selv = array();
			$selv[$vainomat] = "SELECTED";

			$vainoomat = "<div>
				<form id = 'vainoma' name='tilaushaku' action = \"javascript:ajaxPost('vainoma', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&hakutapa=$hakutapa&tilaushaku=$tilaushaku&tila=$tila', 'selain', false, true);\" method = 'post'>
					<select name='vainomat' onchange='submit();'>
						<option value=''>".t("N�yt� kaikkien tilaukset")."</option>
						<option value='x' $selv[x]>".t("N�yt� vain omat tilaukset")."</option>
					</select>
				</form>
				</div>";
		}
		else {
			$vainoomat = "";
			$vainomat  = "x";
		}

		echo "<table>";

		if (!($hakutapa == "tila" and $tilaustila == "kesken")) {
			echo "	<tr>
					<td class='back' colspan='5'>
					$vainoomat
					<br>
					".t("Tilaushaku").":<br>
					<form id = 'tilaushaku' name='tilaushaku' action = \"javascript:ajaxPost('tilaushaku', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&hakutapa=viitteet', 'selain', false, true);\" method = 'post'>
					<input type = 'text' size='50' name = 'tilaushaku' value='$tilaushaku'>
					<br>
					<input type='submit' onclick=\"javascript:ajaxPost('tilaushaku', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=haku&hakutapa=viitteet', 'selain', false, true);\" value='".t("Hae laskun viitteist�")."'><br>
					<input type='submit' onclick=\"javascript:ajaxPost('tilaushaku', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=haku&hakutapa=toimitusosoite', 'selain', false, true);\" value='".t("Hae laskun toimitusosoitteesta")."'><br>
					</form>
					<br>
					</td>
					</tr>";
		}

		$aika 		= "";
		$tilaukset  = array();
		$where 		= "";

		if ($vainomat != "") $vainlisa = " and laatija = '$kukarow[kuka]'";
		else $vainlisa = "";

		if ($tila == "haku") {
			$aika = "luontiaika";

			if (strlen($tilaushaku) > 2 or $tilaustila != "") {
				$where = " and tila in ('N','L') ";

				if ($hakutapa == "viitteet") {
					$where .= " and concat_ws(' ', viesti, comments, sisviesti2, sisviesti1, asiakkaan_tilausnumero) like ('%".mysql_real_escape_string($tilaushaku)."%') ";
				}
				elseif ($hakutapa == "toimitusosoite") {
					$where .= " and concat_ws(' ', toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp) like ('%".mysql_real_escape_string($tilaushaku)."%') ";
				}
				elseif ($hakutapa == "tila") {
					if ($tilaustila == "kesken") {
						$where = " and lasku.tila = 'N' and alatila = '' ";
					}
					else {
						$where = "";
					}
				}
			}
			else {
				echo "<tr><td class='back' colspan='5'><font class='error'>".t("Haussa on oltava v�hint��n 3 merkki�")."</font></td></tr>";
			}
		}
		elseif ($tila == "kesken") {
			$aika = "luontiaika";
			$where = " and tila = 'N' and alatila = '' ";
		}
		elseif ($tila == "kasittely") {
			$aika = "luontiaika";
			$where = " and tila = 'N' and alatila = 'F' ";
		}
		elseif ($tila == "odottaa") {
			$aika = "luontiaika";
			$where = " and tila = 'N' and alatila NOT IN ('', 'F') ";
		}
		elseif ($tila == "toimituksessa") {
			$aika = "lahetepvm";
			$where = " and tila = 'L' and alatila IN ('A', 'B', 'C', 'E') ";
		}
		elseif ($tila == "toimitettu") {
			$aika = "(select max(toimitettuaika) from tilausrivi where tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus)";
			$where = " and tila = 'L' and alatila IN ('D', 'J', 'V', 'X') ";
		}

		if ($where != "") {

			$query = "	SELECT lasku.*, date_format($aika, '%d. %m. %Y') aika,
						(	SELECT sum(tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * if (tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)))
							FROM tilausrivi
							WHERE tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tyyppi != 'D'
						) summa
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and liitostunnus = '$kukarow[oletus_asiakas]'
						$vainlisa
						$where";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {

				if ($tila == "kesken") {
					$aika = "Avattu";
				}
				elseif ($tila == "kasittely") {
					$aika = "Avattu";
				}
				elseif ($tila == "odottaa") {
					$aika = "Tilattu";
				}
				elseif ($tila == "toimituksessa") {
					$aika = "Ker�tty";
				}
				elseif ($tila == "toimitettu") {
					$aika = "Toimitettu";
				}

				$tilaukset[$tila] .= "	<tr>
										<th>".t("Tilaus")."</th>
										<th>".t($aika)."</th>
										<th>".t("Tilausviite")."</th>
										<th>".t("Summa")."</th>
										<td class='back'></td>
										<tr>";

				while ($laskurow = mysql_fetch_array($result)) {
					$lisa = "";

					if ($tilaus == $laskurow["tunnus"]) {
						$lisa .= "	<tr><td class='back' colspan='5'><br>".tilaus($laskurow["tunnus"])."<br></td></tr>";
					}

					$monista = $jatka ="";

					if ($laskurow["laskunro"] > 0) {
						$monista = " <a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&tee=monistalasku&laskunro=$laskurow[laskunro]', false, true);\" onclick=\"return confirm('".t("Oletko varma, ett� haluat monistaa tilauksen?")."');\">".t("Monista")."</a>";
					}

					if ($laskurow["tila"] == "N" and $laskurow["alatila"] == "") {
						if ($laskurow["tunnus"] != $kukarow["kesken"]) {
							$jatka = " <a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&tee=jatkatilausta&tilaus=$laskurow[tunnus]', false, true);\" onclick=\"return confirm('".t("Oletko varma, ett� haluat jatkaa tilausta %s?", $kieli, $laskurow["tunnus"])."');\">".t("Aktivoi")."</a>";
						}
						else {
							$jatka = t("Akviivinen");
						}
					}

					$tilaukset[$tila] .= "	<tr>
											<td>$laskurow[tunnus]</td>
											<td>$laskurow[aika]</td>
											<td>$laskurow[viesti]</td>
											<td>".number_format($laskurow["summa"], 2, ',', ' ')."</td>
											<td class='back'><a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=$tila&hakutapa=$hakutapa&tilaustila=$tilaustila&tilaus=$laskurow[tunnus]', false, true);\">".t("N�yt�")."</a> $jatka $monista</td>
											</tr>$lisa";
				}
			}
			else {
				$tilaukset[$tila] = "<tr><td class='back' colspan='5'>".t("Ei tilauksia")."</td></tr>";
			}
			$tilaukset[$tila] .= "<tr><td class='back' colspan='5'><br></td></tr>";
		}

		echo $tilaukset["haku"];

		if ($tilaustila != "kesken") {
			echo "<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=kesken', false, true);\">".t("Keskener�iset tilaukset")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[kesken]

				<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=kasittely', false, true);\">".t("Tilaukset jotka odottaa k�sittely�")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[kasittely]

				<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=odottaa', false, true);\">".t("Tilaukset jotka odottaa toimitusta")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[odottaa]

				<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=toimituksessa', false, true);\">".t("Toimituksessa olevat tilaukset")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[toimituksessa]

				<tr>
					<td class='back' colspan='5'>
						<a href=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&vainomat=$vainomat&tila=toimitettu', false, true);\">".t("Toimitetut tilaukset")."</a>
					</td>
				</tr>
				<tr>
					<td class='back'><br></td>
				</tr>
				$tilaukset[toimitettu]";
		}

		echo "</table>";
	}
}

if ($tee == "selaa") {

	$tuoteno = $toim_tuoteno = $nimitys = "";

	if ($hakutapa != "" and $tuotehaku == "") {
		die ("<font class='error'>".t("Anna jokin hakukriteeri")."</font>");
	}
	elseif ($hakutapa == "koodilla") {
		$tuotenumero = $tuotehaku;
	}
	elseif ($hakutapa == "nimi") {
		$nimitys = $tuotehaku;
	}
	elseif ($hakutapa == "toim_tuoteno") {
		$toim_tuoteno = $tuotehaku;
	}

	if ($tuotemerkki != "") {
		$ojarj	= "sorttauskentta, tuote_wrapper.tuotemerkki IN ('$tuotemerkki') DESC";
	}
	else {
		$ojarj	= "tuote_wrapper.tuotemerkki";
	}

	if ($kukarow["kuka"] != "www" and (int) $kukarow["kesken"] == 0) {
		require_once("luo_myyntitilausotsikko.inc");
		$tilausnumero = luo_myyntitilausotsikko($kukarow["oletus_asiakas"], $tilausnumero, "");
		$kukarow["kesken"] = $tilausnumero;
	}

	$submit_button = 1;

	require("tuote_selaus_haku.php");
}

if ($tee == "") {

	enable_ajax();

	if ($kukarow["kuka"] == "www") {
		$login_screen = "<form name='login' id= 'loginform' action='login_extranet.php' method='post'>
						<input type='hidden' id = 'location' name='location' value='$palvelin'>
						<font class='login'>".t("K�ytt�j�tunnus",$browkieli).":</font>
						<input type='text' value='' name='user' size='15' maxlength='30'>
						<font class='login'>".t("Salasana", $browkieli).":</font>
						<input type='password' name='salasana' size='15' maxlength='30'>
						<input type='submit' onclick='submit()' value='".t("Kirjaudu sis��n", $browkieli)."'>
						<br>
						$errormsg
						</form>";
	}
	else {
		$login_screen = "<input type='button' onclick=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria&tila=haku&hakutapa=tila&tilaustila=kesken', false, false);\" value='".t("Avoimet tilaukset")."'>|<input type='button' onclick=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot&nayta=tilaushistoria', false, false);\" value='".t("Tilaushistoria")."'>|<input type='button' onclick=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=asiakastiedot', false, false);\" value='".t("Asiakastiedot")."'>
			&nbsp;Tervetuloa, ".$kukarow["nimi"]."&nbsp;<input type='button' onclick=\"javascript:document.location='".$palvelin2."logout.php?location=".$palvelin2."verkkokauppa.php';\" value='".t("Kirjaudu ulos")."'>";
	}

	$verkkokauppa_ulos =  "<div class='login' id='login'>$login_screen</div>
					<div class='menu' id='menu'>".menu()."</div>";

	$tuotenumero = mysql_real_escape_string(trim($_GET["tuotenumero"]));

	if ($tuotenumero != "") {
		$verkkokauppa_ulos .= "	<div class='selain' id='selain'>
								<script TYPE=\"text/javascript\" language=\"JavaScript\">
								sndReq('selain', 'verkkokauppa.php?tee=selaa&hakutapa=koodilla&tuotehaku=$tuotenumero');
								</script>
								</div>";
	}
	else {
		$verkkokauppa_ulos .= "<div class='selain' id='selain'>".uutiset('','',"ETUSIVU")."</div>";
	}

	if (file_exists("verkkokauppa.template")) {
		echo str_replace("<verkkokauppa>", $verkkokauppa_ulos, file_get_contents("verkkokauppa.template"));
	}
	else {
		echo "Verkkokauppapohjan m��rittely puuttuu..<br>";
	}

	echo "</body></html>";
}

?>