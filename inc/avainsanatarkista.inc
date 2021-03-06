<?php

if(!function_exists("avainsanatarkista")) {
	function avainsanatarkista (&$t, $i, $result, $tunnus, &$virhe, $trow) {
		global $kukarow, $yhtiorow, $alias_set, $ulosarray, $varastot_array;

		static $seliteerrori, $laji, $kieli, $selitetark, $selitetemp, $seliteind;

		if (mysql_field_name($result, $i) == "selite") {
			$selitetemp = trim($t[$i]);
			$seliteind = $i;
		}

		if (mysql_field_name($result, $i) == "laji") {
			$laji = trim($t[$i]);

			// Synkattavien avainsanojen lajia ei saa muuttaa, muuten synkkaus menee out of sync
			if (isset($trow["laji"]) and $trow["laji"] != $laji and stripos($yhtiorow["synkronoi"], "avainsana") !== FALSE) {
				$abulisa = preg_match("/(^|,)(avainsana\|*([\|a-zA-Z_\-]*))($|,)/i", $yhtiorow["synkronoi"], $regs);
				$lajit = explode("|",strtolower($regs[3]));

				if (in_array(strtolower($trow["laji"]), $lajit)) {
					$virhe[$i] = t("Synkronoitavien avainsanojen lajia ei saa muuttaa")."!";
				}
			}
		}

		if (mysql_field_name($result, $i) == "kieli") {
			$kieli = trim($t[$i]);
		}

		if (mysql_field_name($result, $i) == "selite" and trim($t[$i]) == '') {
			$seliteerrori = 1;
		}

		if (mysql_field_name($result, $i) == "selite" and strtoupper($laji) == "ALVULK") {
			//	ALV pit�� olla pisteell�, ei pilkulla
			$t[$i] = round((float) str_replace(",", ".", $t[$i]), 2);
		}

		if (mysql_field_name($result, $i) == "selite" and strlen($t[$i]) > 3 and $laji == 'Y') {
			$virhe[$i] = t("Selite on liian pitk�, maksimipituus on kolme merkki�")."!";
		}

		if (mysql_field_name($result, $i) == "selite" and !is_numeric($t[$i]) and in_array($laji, array("TRY", "OSASTO", "PIIRI"))) {
			$virhe[$i] = t("Selitteen on oltava numeerinen")."!";
		}

		if (mysql_field_name($result, $i) == "selite" and $laji == 'VAKIOVIESTI' and trim($t[$i]) == '') {
			$virhe[$i] = t("Selite on pakollinen");
		}

		if ($seliteerrori == 1 and mysql_field_name($result, $i) == "selitetark" and trim($t[$i]) == '' and $laji != 'VAKIOVIESTI') {
			$virhe[$i] = t("Selite ja selitetark ei voi olla tyhji�")."!";
		}

		if (mysql_field_name($result, $i) == "selite" and $laji == "PARAMETRI") {
			preg_match_all('/([a-zA-Z_])+/', $t[$i], $match);

			if ($match[0][0] != $t[$i]) {
				$virhe[$i] = t("Vain aakkoset ja alaviivat ovat sallittuja merkkej�");
			}
		}

		if (mysql_field_name($result, $i) == 'selitetark' and $laji == "VARASTORYHMA" and !is_array($varastot_array) and count($varastot_array) == 0) {
			$virhe[$i] = t("Et valinnut yht��n varastoa")."!";
		}
		elseif (mysql_field_name($result, $i) == 'selitetark' and $laji == 'VARASTORYHMA') {
			if (is_array($varastot_array) and count($varastot_array) > 0) {
				$varastot = '';
				foreach($varastot_array as $varasto_apu) {
					$varastot .= "$varasto_apu,";
				}
				$varastot = substr($varastot, 0, -1);
				$t[$i] = $varastot;
			}
		}

		if (mysql_field_name($result, $i) == 'tunnus') {
			if ($kieli !='' and $selitetemp !='' and ($laji == 'TRY' or $laji == 'OSASTO')) {
				$query = "	SELECT kieli, selite, laji
							FROM avainsana
							WHERE yhtio = '$kukarow[yhtio]'
							AND kieli   = '$kieli'
							AND laji    = '$laji'
							AND selite  = '$selitetemp'
							and tunnus != '$trow[$i]'";
				$results = mysql_query($query);

				if (mysql_num_rows($results) > 0) {
					$virhe[$seliteind] = $virhe[$i] = t("Avainsana annetuilla tiedoilla l�ytyy kannasta. Duplikaatit ovat kiellettyj�!");
				}
			}
		}
	}
}

?>