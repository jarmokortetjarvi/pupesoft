<?php

if(!function_exists("tilitarkista")) {
	function tilitarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set;
		
		if (mysql_field_name($result, $i) == "tilino" and trim($t[$i]) != '') {

			$query   = "select tunnus from tili where yhtio='$kukarow[yhtio]' and tilino='$t[$i]' and tunnus != '$tunnus'";
			$sresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($sresult) > 0) {
				$virhe[$i] = t("T�m� tilinumero on jo perustettu.");
			}

		}

		if (mysql_field_name($result, $i) == "ulkoinen_taso" and trim($t[$i]) == "") {
			$virhe[$i] = t("Ulkoinen taso on sy�tett�v�")."!";
		}
	}
}


?>