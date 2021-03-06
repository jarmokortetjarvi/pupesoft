<?php

	$query  = "SELECT * FROM lasku WHERE tunnus = '{$otunnus}' AND yhtio = '{$kukarow['yhtio']}' AND tila = 'K'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$laskurow = mysql_fetch_assoc($result);
	}
	else {
		echo "<font class='error'>",t("VIRHE: Tilausta ei en�� l�ydy"),"!</font>";
		require('../inc/footer.inc');
		exit;
	}

	if (is_array($suuntalavat) and count($suuntalavat) > 0) {
		$suuntalavalisa = " AND suuntalavat.tunnus IN (".implode(",", $suuntalavat).") ";
	}
	else {
		echo "<font class='error'>",t("VIRHE: Et valinnut suuntalavaa"),"!</font>";
		require('../inc/footer.inc');
		exit;
	}

	$query = "	SELECT tilausrivi.*, suuntalavat.sscc, tuotteen_toimittajat.toim_tuoteno, tilausrivi.varattu+tilausrivi.kpl siskpl, tuote.eankoodi
				FROM tilausrivi
				JOIN suuntalavat ON (suuntalavat.yhtio = tilausrivi.yhtio AND suuntalavat.tila = 'S' AND suuntalavat.tunnus = tilausrivi.suuntalava $suuntalavalisa)
				JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
				LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON (tuotteen_toimittajat.yhtio = tuote.yhtio AND tuotteen_toimittajat.tuoteno = tuote.tuoteno AND tuotteen_toimittajat.liitostunnus = '{$laskurow['liitostunnus']}')
				WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
				AND tilausrivi.uusiotunnus = '{$otunnus}'
				AND tilausrivi.tyyppi = 'O'
				AND tilausrivi.kpl = 0
				AND tilausrivi.suuntalava != 0
				ORDER BY suuntalavat.sscc, tilausrivi.tuoteno";
	$tavaraetiketti_res = mysql_query($query) or pupe_error($query);

	$nimi = str_replace(array('�','�','�','�'), array('A','a','O','o'), $yhtiorow['nimi']);

	while ($row = mysql_fetch_assoc($tavaraetiketti_res)) {

		$row['nimitys'] = str_replace(array('�','�','�','�'), array('A','a','O','o'), $row['nimitys']);

		if (strlen($row['eankoodi']) < 14) {
			$viivakoodi1  = ">6";
			$viivakoodi1 .= substr($row['eankoodi'],0,1);
			$viivakoodi1 .= ">5";
			$viivakoodi1 .= substr($row['eankoodi'],1);
		}
		else {
			// yli 13 merkki� pitk�t EAN-koodit ovat virheellisi�.
			$viivakoodi1  = ">6";
			$viivakoodi1 .= substr($row['eankoodi'],0,2);
			$viivakoodi1 .= ">5";
			$viivakoodi1 .= substr($row['eankoodi'],2);
		}

		$viivakoodi2  = ">6";
		$viivakoodi2 .= substr($row['sscc'],0,1);
		$viivakoodi2 .= ">5";
		$viivakoodi2 .= substr($row['sscc'],1);

		$sivu = "*** ETIKETIN KOKO ***
            	 {D1530,1040,1470|}
                 {AX;+000,+000,+00|}
                 {AY;+05,0|}
                 {C|}

				 *** YHTI�N NIMI ***
				 {PC001;0100,0100,1,1,J,00,B|}
	             {RC001;$nimi|}

				 {LC;0050,0140,1000,0140,0,2|}

				 {PC002;0100,0180,05,05,J,00,B|}
	             {RC002;Tuotenumero|}

				 {PC003;0100,0250,1,1,K,00,B|}
	             {RC003;$row[tuoteno]|}

				 {LC;0050,0270,1000,0270,0,2|}

				 {PC004;0100,0305,05,05,J,00,B|}
	             {RC004;Toimittajan numero|}

				 {PC005;0100,0350,05,05,J,00,B|}
	             {RC005;$row[toim_tuoteno]|}

				 {LC;0050,0360,1000,0360,0,2|}

				 {PC006;0100,0395,05,05,J,00,B|}
	             {RC006;Tuotenimi|}

				 {PC007;0100,0440,08,08,J,00,B|}
	             {RC007;$row[nimitys]|}

				 {LC;0050,0460,1000,0460,0,2|}

				 {PC008;0100,0500,05,05,J,00,B|}
	             {RC008;SSCC|}

				 {PC009;0100,0560,1,1,J,00,B|}
	             {RC009;$row[sscc]|}

				 {LC;0050,0580,1000,0580,0,2|}

				 {PC010;0100,0620,05,05,J,00,B|}
	             {RC010;Tulopvm|}

				 {PC011;0100,0680,05,05,J,00,B|}
	             {RC011;".date("d.m.Y H:i:s")."|}

				 {LC;0600,0580,0600,0690,0,2|}

				 {PC016;0610,0620,05,05,J,00,B|}
	             {RC016;Maara|}

				 {PC017;0610,0680,1,1,J,00,B|}
	             {RC017;$row[siskpl] ".t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite")."|}

				 {LC;0050,0690,1000,0690,0,2|}

			     {XB00;0050,0710,A,3,04,0,0270,+0000000000,000,0,00|}
			     {RB00;$viivakoodi1|}

				 {PC012;0100,1030,05,05,J,00,B|}
	             {RC012;EAN|}

				 {PC013;0200,1030,05,05,J,00,B|}
	             {RC013;$row[eankoodi]|}

			     {XB01;0050,1060,A,3,04,0,0270,+0000000000,000,0,00|}
			     {RB01;$viivakoodi2|}

				 {PC014;0100,1365,05,05,J,00,B|}
	             {RC014;SSCC|}

				 {PC015;0200,1365,05,05,J,00,B|}
	             {RC015;$row[sscc]|}

				 *** TULOSTUS  ***
             	 {XS;I,0001,0002C3210|}
             	 ".chr(12);

		$sivu = escapeshellarg($sivu);

		if ($komento["Tavaraetiketti"] != '' and $komento["Tavaraetiketti"] != 'edi' and $komento["Tavaraetiketti"] != 'email') {
			$line = exec(" echo \"$sivu\" | $komento[Tavaraetiketti]");
		}
	}