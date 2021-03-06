<?php

if(!function_exists("asiakastarkista")) {
	function asiakastarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray, $ytunnus_select, $asiakasnro_select, $toim, $asiak_laji, $errori;

		static $ytunnus_tunnus, $ytunnus, $ovttunnus, $apuverkkotunnus, $apuverkkotunnusi, $apulasku_email, $apulasku_emaili, $apu_maa, $apu_toim_maa;

		if (mysql_field_name($result, $i) == "valkoodi") {
			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu");
			}
		}

		if (mysql_field_name($result, $i) == "laji") {
			$asiak_laji = $t[$i];
		}

		if (mysql_field_name($result, $i) == "ytunnus") {
			if ($ytunnus_select != '') {
				// Jos selectoitu drop downista niin katotaan, ett� t�m� on viel� vapaa
				//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksil�iv�t tiedot on oltava konsernitasolla-yksil�lliset
				$query = "	SELECT group_concat(concat('\'',yhtio.yhtio,'\'')) yhtiot
							FROM yhtio
							JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
							where konserni	 = '$yhtiorow[konserni]'
							and (synkronoi = '$toim' or synkronoi like '$toim,%' or synkronoi like '%,$toim,%' or synkronoi like '%,$toim')";
				$sresult = pupe_query($query);
				$srowapu = mysql_fetch_array($sresult);

				if ($srowapu["yhtiot"] != "") {
					$tarkyhtio = $srowapu["yhtiot"];
				}
				else {
					$tarkyhtio = "'$kukarow[yhtio]'";
				}

				$query   = "select tunnus from asiakas where yhtio in ($tarkyhtio) and ytunnus='$ytunnus_select' and tunnus != '$tunnus'";
				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) > 0) {
					if ($alias_set == "VAHITTAISMYYNTI") {
						$virhe[$i] = t("Valitsemasi asiakasnumero ei ollutkaan vapaa.");
						$errori = 1;
					}
					else {
						$virhe[$i] = t("Valitsemasi ytunnus ei ollutkaan vapaa.");
						$errori = 1;
					}
				}
				else {
					$t[$i] = $ytunnus_select;
				}
			}
		}

		if (mysql_field_name($result, $i) == "ytunnus" and $virhe[$i] == "") {

			$ytunnus = $t[$i];
			$ytunnus_tunnus = $i;

			if ($ytunnus == "") {
				$virhe[$i] = t("Y-tunnus puuttuu")."!";
				$errori = 1;
			}

		}

		if ((mysql_field_name($result, $i) == "osasto" or mysql_field_name($result, $i) == "ryhma" or mysql_field_name($result, $i) == "piiri") and trim($t[$i]) != '') {

			if (mysql_field_name($result, $i) == 'osasto')  $kptyyppi = "ASIAKASOSASTO";
			if (mysql_field_name($result, $i) == 'ryhma') 	$kptyyppi = "ASIAKASRYHMA";
			if (mysql_field_name($result, $i) == 'piiri') 	$kptyyppi = "PIIRI";

			$tpque = "SELECT tunnus FROM avainsana WHERE yhtio = '$kukarow[yhtio]' and LAJI = '$kptyyppi' and SELITE = '$t[$i]'";
			$tpres = pupe_query($tpque);

			if (mysql_num_rows($tpres) == 0) {
				$virhe[$i] .= t("Avainsanaa ei l�ydy")."!";
			}
		}

		if (mysql_field_name($result, $i) == "asiakasnro") {
			if ($asiakasnro_select != '') {
				// Jos selectoitu drop downista niin katotaan, ett� t�m� on viel� vapaa
				//jos konsernin asiakkaat synkronoidaan niin asiakkaiden yksil�iv�t tiedot on oltava konsernitasolla-yksil�lliset
				$query = "	SELECT ifnull(group_concat(concat('\'',yhtio.yhtio,'\'')), '') yhtiot
							FROM yhtio
							JOIN yhtion_parametrit ON yhtion_parametrit.yhtio=yhtio.yhtio
							where konserni	 = '$yhtiorow[konserni]'
							and (synkronoi = '$toim' or synkronoi like '$toim,%' or synkronoi like '%,$toim,%' or synkronoi like '%,$toim')";
				$sresult = pupe_query($query);
				$srowapu = mysql_fetch_array($sresult);

				if ($srowapu["yhtiot"] != "") {
					$srowapu = mysql_fetch_array($sresult);

					if ($srowapu["yhtiot"] != '') {
						$tarkyhtio = $srowapu["yhtiot"];
					}
					else {
						$tarkyhtio = "'$kukarow[yhtio]'";
					}
				}
				else {
					$tarkyhtio = "'$kukarow[yhtio]'";
				}

				$query   = "SELECT tunnus from asiakas where yhtio in ($tarkyhtio) and asiakasnro='$asiakasnro_select' and tunnus != '$tunnus'";
				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) > 0) {
					$virhe[$i] = t("Valitsemasi asiakasnumero ei ollutkaan vapaa.");
					$errori = 1;
				}
				else {
					$t[$i] = $asiakasnro_select;
				}
			}

			if ($yhtiorow['asiakkaan_tarkenne'] != '') {

				if ($asiakasnro_select != '') {
					$asiakas_numero = $asiakasnro_select;
				}
				else {
					$asiakas_numero = $t[$i];
				}

				$query   = "select tunnus from asiakas where yhtio = '$kukarow[yhtio]' and ytunnus='$ytunnus' and asiakasnro='$asiakas_numero' and tunnus != '$tunnus'";
				$sresult = pupe_query($query);

				if (mysql_num_rows($sresult) > 0) {

					if ($alias_set == "VAHITTAISMYYNTI") {
						$virhe[$i] .= t("Samalla tiedoilla on useita asiakkaita! Lis�� tarkenne Asiakasnumeroon/Henkil�tunnukseen.");
						$errori = 1;
					}
					else {
						$virhe[$i] .= t("Samalla tiedoilla on useita asiakkaita! Lis�� tarkenne Ytunnukseen/Asiakasnumeroon.");
						$errori = 1;
					}
				}
			}

		}

		if (mysql_field_name($result, $i) == "ovttunnus") {
			$ovttunnus = $t[$i];
		}

		if (mysql_field_name($result, $i) == "toim_ovttunnus" and $yhtiorow['asiakkaan_tarkenne'] == '') {

			$query   = "select tunnus from asiakas where yhtio = '$kukarow[yhtio]' and ytunnus='$ytunnus' and ovttunnus='$ovttunnus' and toim_ovttunnus='$t[$i]' and tunnus != '$tunnus'";
			$sresult = pupe_query($query);

			if (mysql_num_rows($sresult) > 0) {
				if ($alias_set == "VAHITTAISMYYNTI") {
					$virhe[$i] = t("Samalla tiedoilla on useita asiakkaita! Lis�� tarkenne Asiakasnumeroon/Henkil�tunnukseen.");
					$errori = 1;
				}
				else {
					$virhe[$i] = t("Samalla tiedoilla on useita asiakkaita! Lis�� tarkenne Ytunnukseen/Ovttunnukseen/Toim_ovttunnukseen.");
					$errori = 1;
				}
			}
		}

		if (mysql_field_name($result, $i) == "verkkotunnus") {
			// verkkotunnus tulee datassa ennen CHN kentt��, joten otetaan t�m� talteen
			$apuverkkotunnus  = trim($t[$i]);
			$apuverkkotunnusi = $i;
		}

		if (mysql_field_name($result, $i) == "lasku_email") {
			// lasku_email tulee datassa ennen CHN kentt��, joten otetaan t�m� talteen
			$apulasku_email  = trim($t[$i]);
			$apulasku_emaili = $i;
		}

		if ((mysql_field_name($result, $i) == "tilino" or
			mysql_field_name($result, $i) == "tilino_eu" or
			mysql_field_name($result, $i) == "tilino_ei_eu" or
			mysql_field_name($result, $i) == "tilino_marginaali" or
			mysql_field_name($result, $i) == "tilino_osto_marginaali") and $t[$i] != '') {

			$query = "	SELECT yhtio
						FROM tili
						WHERE yhtio = '$kukarow[yhtio]'
						and tilino = '$t[$i]'";
			$sresult = pupe_query($query);

			if (mysql_num_rows($sresult) != 1) {
				$virhe[$i] = t("Tili� ei l�ydy");
				$errori = 1;
			}
		}

		if (mysql_field_name($result, $i) == "chn") {
			if (isset($t[$i]) and trim($t[$i]) != '') {
				// jos ollaan valittu s�hk�posti niin lasku_email pit�� sy�tt��!
				if (trim($t[$i]) == '666' and $apulasku_email == "") {
					// Pit�� aina setata my�s "t�m�n" kent�n virhe, muuten luedata ei toimi
					$virhe[$i] = $virhe[$apulasku_emaili] = t("Olet valinnut laskutustavaksi s�hk�postin ja lasku_email on tyhj�! Laskutus ei onnistu")."!";
					$errori = 1;
				}

				// jos ollaan valittu jotain muuta kuin PAPERI tai EMAIL niin verkkotunnus pit�� sy�tt��!
				if (trim($t[$i]) != '999' and trim($t[$i]) != '112' and trim($t[$i]) != '666' and trim($t[$i]) != '667' and trim($t[$i]) != '100' and $apuverkkotunnus == "") {
					// Pit�� aina setata my�s "t�m�n" kent�n virhe, muuten luedata ei toimi
					$virhe[$i] = $virhe[$apuverkkotunnusi] = t("Olet valinnut laskutustavaksi muun kuin Paperi ja verkkotunnus on tyhj�! Laskutus ei onnistu")."!";
					$errori = 1;
				}
			}
		}

		if ((mysql_field_name($result, $i) == "email" or mysql_field_name($result, $i) == "lasku_email") and trim($t[$i]) != '') {

			// Tarkistetaan s�hk�postiosoite
			$email_virhe = tarkista_email($t[$i]);

			if ($email_virhe != "") {
				$virhe[$i] = t("S�hk�postin domain ei l�ydy")."! ($email_virhe)";
				$errori = 1;
			}

		}

		if (mysql_field_name($result, $i) == "maksuehto" and $t[$i] > 0) {

			$query = "	SELECT tunnus
						from maksuehto
						where yhtio='$kukarow[yhtio]'
						and tunnus='$t[$i]'
						and (sallitut_maat = '' or sallitut_maat like '%$apu_maa%')";
			$sresult = pupe_query($query);

			if (mysql_num_rows($sresult) == 0) {
				$virhe[$i] = t("T�t� maksuehtoa ei saa k�ytt�� asiakkaalla t�ss� maassa.");
				$errori = 1;
			}
		}

		if (mysql_field_name($result, $i) == "toimitustapa" and trim($t[$i]) != '') {

			$query   = "SELECT tunnus
						from toimitustapa
						where yhtio='$kukarow[yhtio]' and selite='$t[$i]'
						and (sallitut_maat = '' or sallitut_maat like '%$apu_toim_maa%')";
			$sresult = pupe_query($query);

			if (mysql_num_rows($sresult) == 0) {
				$virhe[$i] = t("T�t� toimitustapaa ei saa k�ytt�� asiakkaalla t�ss� maassa.");
				$errori = 1;
			}
		}

		if (mysql_field_name($result, $i) == "panttitili" and trim($t[$i]) == '') {

			if (trim($t[$i]) != trim($trow[$i])) {
				$query = "	SELECT *
							FROM panttitili
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND asiakas = '{$tunnus}'
							AND status = ''
							AND kaytettypvm = '0000-00-00'
							AND kaytettytilausnro = 0
							AND tuoteno != ''";
				$chk_res = pupe_query($query);

				if (mysql_num_rows($chk_res) > 0) {
					$virhe[$i] = t("Asiakkaalla panttitilill� aktiivisia tuotteita, panttitili� ei voi kytke� pois p��lt�").".";
					$errori = 1;
				}
			}
		}

		if (mysql_field_name($result, $i) == "toim_maa") {
			$apu_toim_maa = $t[$i];
		}

		if (mysql_field_name($result, $i) == "maa") {

			$apu_maa = $t[$i];

			if (strtoupper($t[$i]) == 'FI' and $asiak_laji != "R" and $asiak_laji != "H" and ($yhtiorow["ytunnus_tarkistukset"] == "" or $yhtiorow["ytunnus_tarkistukset"] == "A")) {

				require ("tarkistaytunnus.inc");

				if ($ok == 0) {
					// Pit�� aina setata my�s "t�m�n" kent�n virhe, muuten luedata ei toimi
					$virhe[$i] = $virhe[$ytunnus_tunnus] = t("Virheellinen y-tunnus")."! $t[$i] -> $ytunnus";
					$errori = 1;
				}
				else {
					 $t[$ytunnus_tunnus] = $ytunnus;
				}
			}
			elseif ($yhtiorow["maa"] == 'FI' and strtoupper($t[$i]) == 'SE' and strtoupper(substr($t[$ytunnus_tunnus], 0, 2)) != "SE" and $asiak_laji != "H") {
				// Pit�� aina setata my�s "t�m�n" kent�n virhe, muuten luedata ei toimi
				$virhe[$i] = $virhe[$ytunnus_tunnus] = t("Virheellinen y-tunnus")."! Laskutusosoite ruotsissa. Ytunnus pit�� alkaa SE!";
				$errori = 1;
			}
		}
	}
}
