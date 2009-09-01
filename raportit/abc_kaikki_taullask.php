<?php

	echo "<font class='head'>".t("ABC-Analyysi�: ABC-pitk�listaus")."<hr></font>";

	if ($toim == "kulutus") {
		$myykusana = t("Kulutus");
	}
	else {
		$myykusana = t("Myynti");
	}

	if ($asiakasanalyysi) {
		$astusana = t("Asiakas");
	}
	else {
		$astusana = t("Tuote");
	}

	if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
		$saapumispp = $saapumispp;
		$saapumiskk = $saapumiskk;
		$saapumisvv	= $saapumisvv;
	}
	elseif (trim($saapumispvm) != '') {
		list($saapumisvv, $saapumiskk, $saapumispp) = split('-', $saapumispvm);
	}

	// piirrell��n formi
	echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
	echo "<input type='hidden' name='aja' value='AJA'>";
	echo "<input type='hidden' name='tee' value='PITKALISTA'>";
	echo "<input type='hidden' name='toim' value='$toim'>";

	// Monivalintalaatikot (osasto, try tuotemerkki...)
	// M��ritell��n mitk� latikot halutaan mukaan
	$lisa  = "";
	$ulisa = "";
	$mulselprefix = "abc_aputaulu";

	if ($asiakasanalyysi) {
		$monivalintalaatikot = array("ASIAKASOSASTO", "ASIAKASRYHMA");
	}
	else {
		$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "TUOTEMYYJA", "TUOTEOSTAJA");
	}

	require ("../tilauskasittely/monivalintalaatikot.inc");

	echo "<br>";
	echo "<table style='display:inline;'>";
	echo "<tr>";
	echo "<th>".t("Valitse luokka").":</th>";
	echo "<td><select name='luokka'>";
	echo "<option value=''>".t("Valitse luokka")."</option>";

	$sel = array();
	$sel[$luokka] = "selected";

	$i=0;
	foreach ($ryhmanimet as $nimi) {
		echo "<option value='$i' $sel[$i]>$nimi</option>";
		$i++;
	}

	echo "</select></td>";

	if (!$asiakasanalyysi) {
		echo "</tr>";

		echo "<tr>";
		echo "<th>".t("Sy�t� viimeinen saapumisp�iv�").":</th>";
		echo "	<td><input type='text' name='saapumispp' value='$saapumispp' size='2'>
				<input type='text' name='saapumiskk' value='$saapumiskk' size='2'>
				<input type='text' name='saapumisvv' value='$saapumisvv'size='4'></td></tr>";

		echo "<tr>";
		echo "<th>".t("Varastopaikoittain").":</th>";

		$sel = "";
		if ($paikoittain == 'JOO') {
			$sel = "CHECKED";
		}

		echo "<td><input type='checkbox' name='paikoittain' value='JOO' $sel></td>";
	}
	echo "<td class='back'><input type='submit' name='ajoon' value='".t("Aja raportti")."'></td>";
	echo "</tr>";
	echo "</form>";
	echo "</table><br>";

	if ($aja == "AJA" and isset($ajoon)) {

		if (@include('Spreadsheet/Excel/Writer.php')) {

			//keksit��n failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Sheet 1');

			$excelrivi = 0;
			$excelsarake = 0;

			$worksheet->writeString($excelrivi, $excelsarake++, t("ABC"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Osaston luokka"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Ryhm�n luokka"));
			$worksheet->writeString($excelrivi, $excelsarake++, $astusana);
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Toim_tuoteno"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Nimitys"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Osasto"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Ryhm�"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Merkki"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Malli"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Mallitarkenne"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Myyj�"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Ostaja"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Viim. saapumispvm"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Saldo"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Tulopvm"));
			$worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("tot"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Kate").t("tot"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Kate%"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Kateosuus"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Vararvo"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Kierto"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Kate")."% x ".t("Kierto"));
			$worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("m��r�"));
			$worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("er�").t("m��r�"));
			$worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("er�").$yhtiorow["valkoodi"]);
			$worksheet->writeString($excelrivi, $excelsarake++, $myykusana.t("rivit"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Puuterivit"));
			$worksheet->writeString($excelrivi, $excelsarake++, t("Palvelutaso"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Ostoer�").t("m��r�"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Ostoer�").$yhtiorow["valkoodi"]);
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Ostorivit"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("KustannusMyynti"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("KustannusOsto"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("KustannusYht"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Kate-Kustannus"));
			if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, t("Tuotepaikka"));
			$excelrivi++;
		}

		if (count($haku) > 0) {
			foreach ($haku as $kentta => $arvo) {
				if (strlen($arvo) > 0 and $kentta != 'kateosuus') {
					$lisa  .= " and abc_aputaulu.$kentta like '%$arvo%'";
					$ulisa2 .= "&haku[$kentta]=$arvo";
				}
				if (strlen($arvo) > 0 and $kentta == 'kateosuus') {
					$hav = "HAVING abc_aputaulu.kateosuus like '%$arvo%' ";
					$ulisa2 .= "&haku[$kentta]=$arvo";
				}
			}
		}

		$saapumispvmlisa = "";

		if (trim($saapumispp) != '' and trim($saapumiskk) != '' and trim($saapumisvv) != '') {
			$saapumispvm = "$saapumisvv-$saapumiskk-$saapumispp";
			$saapumispvmlisa = " and abc_aputaulu.saapumispvm <= '$saapumispvm' ";
		}

		$luokkalisa = "";

		if ($luokka != "") {
			$luokkalisa = " and luokka = '$luokka' ";
		}

		$query = "	SELECT
					distinct luokka
					FROM abc_aputaulu
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi  = '$abcchar'
					$luokkalisa
					ORDER BY luokka";
		$luokkares = mysql_query($query) or pupe_error($query);

		while ($luokkarow = mysql_fetch_array($luokkares)) {

			//kauden yhteismyynnit ja katteet
			$query = "	SELECT
						sum(summa) yhtmyynti,
						sum(kate)  yhtkate
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = '$abcchar'
						and luokka = '$luokkarow[luokka]'
						$lisa
						$saapumispvmlisa";
			$sumres = mysql_query($query) or pupe_error($query);
			$sumrow = mysql_fetch_array($sumres);

			$sumrow['yhtkate'] = (float) $sumrow['yhtkate'];
			$sumrow['yhtmyynti'] = (float) $sumrow['yhtmyynti'];

			$query = "	SELECT *,
						if ($sumrow[yhtkate] = 0, 0, kate/$sumrow[yhtkate]*100)	kateosuus,
						katepros * varaston_kiertonop kate_kertaa_kierto,
						kate - kustannus_yht total
						FROM abc_aputaulu
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi	= '$abcchar'
						and luokka	= '$luokkarow[luokka]'
						$saapumispvmlisa
						$lisa
						$hav
						ORDER BY $abcwhat desc";
			$res = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($res)) {

				if (!$asiakasanalyysi) {
					$query = "	SELECT tuote.tuoteno, tuote.nimitys, tuote.tuotemerkki, group_concat(distinct tuotteen_toimittajat.toim_tuoteno) toim_tuoteno
								FROM tuotteen_toimittajat
								JOIN tuote ON tuote.yhtio=tuotteen_toimittajat.yhtio and tuote.tuoteno=tuotteen_toimittajat.tuoteno
								WHERE tuotteen_toimittajat.tuoteno='$row[tuoteno]'
								and tuotteen_toimittajat.yhtio='$kukarow[yhtio]'
								group by tuote.tuoteno";
					$tuoresult = mysql_query($query) or pupe_error($query);
					$tuorow = mysql_fetch_array($tuoresult);

					$query = "	SELECT distinct myyja, nimi
								FROM kuka
								WHERE yhtio='$kukarow[yhtio]'
								AND myyja = '$row[myyjanro]'
								AND myyja != ''
								ORDER BY myyja";
					$myyjaresult = mysql_query($query) or pupe_error($query);
					$myyjarow = mysql_fetch_array($myyjaresult);

					$query = "	SELECT distinct myyja, nimi
								FROM kuka
								WHERE yhtio='$kukarow[yhtio]'
								AND myyja = '$row[ostajanro]'
								AND myyja != ''
								ORDER BY myyja";
					$ostajaresult = mysql_query($query) or pupe_error($query);
					$ostajarow = mysql_fetch_array($ostajaresult);
				}


				//haetaan varastopaikat ja saldot
				if ($asiakasanalyysi) {
					$query = "	SELECT ytunnus, nimi
								FROM asiakas
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$row[tuoteno]'";
				}
				elseif ($paikoittain == 'JOO') {
					$query = "	SELECT concat_ws(' ', hyllyalue, hyllynro, hyllyvali, hyllytaso) paikka, saldo
								from tuotepaikat
								where tuoteno 	= '$row[tuoteno]'
								and yhtio 		= '$kukarow[yhtio]'";
				}
				else {
					$query = "	SELECT sum(saldo) saldo
								from tuotepaikat
								where tuoteno	= '$row[tuoteno]'
								and yhtio 		= '$kukarow[yhtio]'";

				}
				$paikresult = mysql_query($query) or pupe_error($query);

				while ($paikrow = mysql_fetch_array($paikresult)) {

					if ($asiakasanalyysi) {
						$row["tuoteno"] = $paikrow["ytunnus"];
						$row["nimitys"] = $paikrow["nimi"];
					}
					else {
						$row["nimitys"] = t_tuotteen_avainsanat($tuorow, 'nimitys');
					}

					// Lis�t��n rivi exceltiedostoon
					if (isset($workbook)) {
						$excelsarake = 0;

						$worksheet->writeString($excelrivi, $excelsarake++,  $ryhmanimet[$row["luokka"]]);
						$worksheet->writeString($excelrivi, $excelsarake++,  $ryhmanimet[$row["luokka_osasto"]]);
						$worksheet->writeString($excelrivi, $excelsarake++,  $ryhmanimet[$row["luokka_try"]]);
						$worksheet->writeString($excelrivi, $excelsarake++,  $row["tuoteno"]);
						if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++,  $tuorow["toim_tuoteno"]);
						$worksheet->writeString($excelrivi, $excelsarake++,  $row["nimitys"]);
						$worksheet->writeString($excelrivi, $excelsarake++,  $row["osasto"]);
						$worksheet->writeString($excelrivi, $excelsarake++,  $row["try"]);
						if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++,  $tuorow["tuotemerkki"]);
						if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $row["malli"]);
						if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $row["mallitarkenne"]);
						if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $myyjarow["nimi"]);
						if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $ostajarow["nimi"]);
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++,  tv1dateconv($row["saapumispvm"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++,  $row["saldo"]);
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++,  tv1dateconv($row["tulopvm"]));
						$worksheet->write($excelrivi, $excelsarake++,  sprintf('%.1f',$row["summa"]));
						$worksheet->write($excelrivi, $excelsarake++,  sprintf('%.1f',$row["kate"]));
						$worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["katepros"]));
						$worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["kateosuus"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["vararvo"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["varaston_kiertonop"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["kate_kertaa_kierto"]));
						$worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["kpl"]));
						$worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["myyntierankpl"]));
						$worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["myyntieranarvo"]));
						$worksheet->write($excelrivi, $excelsarake++, sprintf('%.0f',$row["rivia"]));
						$worksheet->write($excelrivi, $excelsarake++, sprintf('%.0f',$row["puuterivia"]));
						$worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["palvelutaso"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["ostoerankpl"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["ostoeranarvo"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.0f',$row["osto_rivia"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["kustannus"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["kustannus_osto"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["kustannus_yht"]));
						if (!$asiakasanalyysi) $worksheet->write($excelrivi, $excelsarake++, sprintf('%.1f',$row["total"]));
						if (!$asiakasanalyysi) $worksheet->writeString($excelrivi, $excelsarake++, $paikrow["paikka"]);
						$excelrivi++;
					}
				}
			}
		}

		if(isset($workbook)) {
			// We need to explicitly close the workbook
			$workbook->close();

			echo "<br><br><table>";
			echo "<tr><th>".t("Tallenna Excel").":</th>";
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='exceltee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='ABC_listaus.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
			echo "</table><br>";
		}
	}
?>