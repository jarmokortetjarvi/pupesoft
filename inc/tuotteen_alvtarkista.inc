<?php

if(!function_exists("tuotteen_alvtarkista")) {
	function tuotteen_alvtarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;
		
		if (mysql_field_name($result, $i) == "tuoteno") {
			$query = "SELECT yhtio FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '".trim($t[$i])."'";
			$chkressiresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($chkressiresult) == 0) {
				$virhe[$i] = t("Tuotenumero ei l�ydy");
			}
		}

		if (mysql_field_name($result, $i) == "maa") {
			if ($t[$i] == $yhtiorow["maa"]) {
				$virhe[$i] = t("T�m� on turhaa, sy�t� t�m� alv tuotetietojen taakse.");
			}
		}

		if (mysql_field_name($result, $i) == "alv") {
			if (!is_numeric($t[$i]) or $t[$i] > 100) {
				$virhe[$i] = t("Virheellinen ALV");
			}
		}

		if ((mysql_field_name($result, $i) == "tilino" or 
			mysql_field_name($result, $i) == "tilino_eu" or 
			mysql_field_name($result, $i) == "tilino_ei_eu" or 
			mysql_field_name($result, $i) == "tilino_marginaali" or 
			mysql_field_name($result, $i) == "tilino_osto_marginaali") and $t[$i] != 0) {
			
			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);
			if (mysql_num_rows($sresult) != 1) {
				$virhe[$i] = t("Tili puuttuu tai sit� ei l�ydy");
			}
		}
	}
}


?>