<?php

if(!function_exists("liitetiedostottarkista")) {
	function liitetiedostottarkista (&$t, $i, $result, &$tunnus, &$virhe, $trow) {
		global $kukarow, $errori, $_FILES, $thumb_size_x, $thumb_size_y;
		static $liite_liitos, $liite_liitostunnus, $liite_datai, $liite_selite, $liite_kieli, $liite_kayttotarkoitus;

		if (mysql_field_name($result, $i) == "liitos") {
			$liite_liitos = $t[$i];
		}

		if (mysql_field_name($result, $i) == "liitostunnus") {
			$liite_liitostunnus = $t[$i];
		}

		if (mysql_field_name($result, $i) == "data") {
			$liite_datai = $i;
		}

		if (mysql_field_name($result, $i) == "selite") {
			$liite_selite = $t[$i];
		}

		if (mysql_field_name($result, $i) == "kieli") {
			$liite_kieli = $t[$i];
		}

		if (mysql_field_name($result, $i) == "kayttotarkoitus") {
			$liite_kayttotarkoitus = $t[$i];
		}

		if (mysql_field_name($result, $i) == "jarjestys") {

			if ((int) $t[$i] == 0) {
				$query = "	SELECT max(jarjestys) jarjestys
							FROM liitetiedostot
							WHERE yhtio 		= '$kukarow[yhtio]'
							and liitos   		= '$liite_liitos'
							and liitostunnus 	= '$liite_liitostunnus'";
				$jres = pupe_query($query);
				$jrow = mysql_fetch_array($jres);

				$t[$i] = $jrow["jarjestys"]+1;
			}

			$liite_jarjestys = $t[$i];

			if ($tunnus == "" or $_FILES["liite_data"]["size"] > 0) {

				$retval = tarkasta_liite("liite_data");

				if ($retval !== TRUE) {
					// Pit�� aina setata my�s "t�m�n" kent�n virhe, muuten luedata ei toimi
					$virhe[$i] = $virhe[$liite_datai] = $retval;
					$errori = 1;
				}

				//	Onko meill� jokin kuvatyyppi?
				list($type, $crap) = explode("/", $_FILES["liite_data"]["type"]);

				//tarkastetaan l�ytyyk� kuva jo kannasta
				if ($liite_kayttotarkoitus == "TK" and $type == "image" and strtoupper($liite_liitos) == 'TUOTE') {
					$kayttotarkoitus_lisa = " and kayttotarkoitus in ('TK','TH') ";
				}
				elseif ($liite_kayttotarkoitus == "TH" and $type == "image" and strtoupper($liite_liitos) == 'TUOTE') {
					$kayttotarkoitus_lisa = " and kayttotarkoitus = 'TH' ";
				}
				else {
					$kayttotarkoitus_lisa = " and kayttotarkoitus = 'MU' ";
				}

				$file_name = mysql_real_escape_string($_FILES['liite_data']['name']);

				//ehk� kieli whereen
				$query = "  SELECT filename, kayttotarkoitus
							FROM liitetiedostot
							where yhtio 		= '$kukarow[yhtio]'
							and liitos 			= '$liite_liitos'
							and liitostunnus	= '$liite_liitostunnus'
							$kayttotarkoitus_lisa
							and filename 		= '$file_name'";
				$result = pupe_query($query);

				if (mysql_num_rows($result) > 0) {
					$checkrow = mysql_fetch_array($result);

					// Pit�� aina setata my�s "t�m�n" kent�n virhe, muuten luedata ei toimi
					$virhe[$i] = $virhe[$liite_datai] = t("VIRHE! Tiedosto %s %s l�ytyy jo tuotteelle", $kieli, $file_name, $checkrow['kayttotarkoitus']);
					$errori = 1;
				}
				else {

					$uusi_filu = "";

					if (isset($thumb_size_x) and trim($thumb_size_x) != '' and isset($thumb_size_y) and trim($thumb_size_y) != '') {
						$thumb_size_x = (int) $thumb_size_x;
						$thumb_size_y = (int) $thumb_size_y;
					}
					else {
						$thumb_size_x = 96;
						$thumb_size_y = 65;
					}

					// Muutetaan tuotekuvan koko sopivaksi
					if ($liite_kayttotarkoitus == "TK" and $type == "image" and strtoupper($liite_liitos) == 'TUOTE') {
						$temp_file = $_FILES["liite_data"];

						$uusi_filu = muuta_kuvan_koko($thumb_size_y, $thumb_size_x, "thumb", $liite_liitos, "liite_data");

						$liite_kayttotarkoitus = "TH";
						$uusi_tunnus = tallenna_liite("liite_data", $liite_liitos, $liite_liitostunnus, $liite_selite, $liite_kayttotarkoitus, $tunnus, $liite_jarjestys, $liite_kieli);

						if ($uusi_filu != "") {
							unlink($uusi_filu);
						}

						$_FILES["liite_data"] = $temp_file;

						$uusi_filu = muuta_kuvan_koko(480, 640, "normaali", $liite_liitos, "liite_data");

						$uusi_tunnus = tallenna_liite("liite_data", $liite_liitos, $liite_liitostunnus, $liite_selite, $liite_kayttotarkoitus, $tunnus, $liite_jarjestys, $liite_kieli);

						if ($uusi_filu != "") {
							unlink($uusi_filu);
						}
					}
					elseif ($liite_kayttotarkoitus == "TH" and $type == "image" and strtoupper($liite_liitos) == 'TUOTE') {
						$uusi_filu = muuta_kuvan_koko($thumb_size_y, $thumb_size_x, "thumb", $liite_liitos, "liite_data");

						$uusi_tunnus = tallenna_liite("liite_data", $liite_liitos, $liite_liitostunnus, $liite_selite, $liite_kayttotarkoitus, $tunnus, $liite_jarjestys, $liite_kieli);

						if ($uusi_filu != "") {
							unlink($uusi_filu);
						}
					}
					else {
						$uusi_tunnus = tallenna_liite("liite_data", $liite_liitos, $liite_liitostunnus, $liite_selite, $liite_kayttotarkoitus, $tunnus, $liite_jarjestys, $liite_kieli);
					}

					// palautetaan t�m� yll�pito.phplle
					$tunnus = $uusi_tunnus;
				}
			}
		}
	}
}
?>