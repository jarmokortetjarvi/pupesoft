<?php

if(!function_exists("yrititarkista")) {
	function yrititarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray;
		
		if (mysql_field_name($result, $i) == "nimi") {
			if (trim($t[$i]) == '') {
				$virhe[$i] = t("Tieto puuttuu");
			}
		}

		if (mysql_field_name($result, $i) == "oletus_rahatili" or
			mysql_field_name($result, $i) == "oletus_kulutili" or
			(mysql_field_name($result, $i) == "oletus_selvittelytili" and $t[$i] != "")) {

			$query = "	SELECT nimi
						FROM tili
						WHERE yhtio='$kukarow[yhtio]' and tilino = '$t[$i]'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) != 1) {
				//Olisiko siell� nimi
				if ($t[$i] != '') {
					$query = "	SELECT nimi, tilino
								FROM tili
								WHERE yhtio = '$kukarow[yhtio]' and nimi like '%$t[$i]%'
								ORDER BY tilino";
					$sresult = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($sresult) > 0) {
						$ulosarray[$i] = "<td><select name='$nimi'><option value = '' $sel>".t("Ei valintaa")."</option>";
						while ($vrow=mysql_fetch_array($sresult)) {
							$sel="";
							if ($trow[$i] == $vrow[0]) {
								$sel = "selected";
							}
							$ulosarray[$i] .= "<option value = '$vrow[tilino]' $sel>$vrow[tilino] $vrow[nimi]</option>";
						}
						$ulosarray[$i] .= "</select></td>";
						$virhe[$i] = t("Valitse tili");
					}

				}
				if ($virhe[$i] == '') $virhe[$i] = t("Tili puuttuu tai sit� ei l�ydy");
			}
		}

		if (mysql_field_name($result, $i) == "iban" and $t[$i] != "") {
			$pankkitili = tarkista_iban($t[$i]);
			if ($pankkitili == "") {
				$virhe[$i] = t("Pankkitili on virheellinen");
			}
			else {
				$t[$i]=$pankkitili;
			}			
		}

		if (mysql_field_name($result, $i) == "bic" and $t[$i] != "") {
			if (!tarkista_bic($t[$i])) {
				$virhe[$i] = t("BIC on virheellinen");
			}
		}

		if (mysql_field_name($result, $i) == "tilino") {

			if (strtoupper($yhtiorow['maa']) == 'FI') {

				if (is_numeric(substr($t[$i], 0, 1))) {

					$pankkitili = $t[$i];
					require 'pankkitilinoikeellisuus.php';

					if ($pankkitili == '') {
						$virhe[$i] = "".t("Pankkitili on virheellinen")."";
					}
					else {
						$t[$i]=$pankkitili;
					}
				}
			}
		}
	}
}


?>