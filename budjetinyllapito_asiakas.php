<?php

	if (isset($_REQUEST["tee"])) {
		if ($_REQUEST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if ($_REQUEST["kaunisnimi"] != '') $_REQUEST["kaunisnimi"] = str_replace("/","",$_REQUEST["kaunisnimi"]);
	}

	require ("inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);
		exit;
	}
	else {

		if (!isset($toim)) $toim = '';
		if (!isset($tkausi)) $tkausi = '';
		if (!isset($ytunnus)) $ytunnus = '';
		if (!isset($asiakasid)) $asiakasid = 0;
		if (!isset($toimittajaid)) $toimittajaid = 0;
		if (!isset($submit_button)) $submit_button = '';
		if (!isset($liitostunnukset)) $liitostunnukset = '';
		if (!isset($budj_taulunrivit)) $budj_taulunrivit = array();

		if (isset($vaihdaasiakas)) {
			$ytunnus 		 = "";
			$asiakasid 		 = 0;
			$toimittajaid	 = 0;
			$liitostunnukset = "";
		}

		if ($toim == "TOIMITTAJA") {
			echo "<font class='head'>".t("Budjetin yll�pito toimittaja")."</font><hr>";

			$budj_taulu = "budjetti_toimittaja";
			$budj_sarak = "toimittajan_tunnus";
		}
		else {
			echo "<font class='head'>".t("Budjetin yll�pito asiakas")."</font><hr>";

			$budj_taulu = "budjetti_asiakas";
			$budj_sarak = "asiakkaan_tunnus";
		}

		if (isset($muutparametrit)) {
			foreach (explode("##", $muutparametrit) as $muutparametri) {
				list($a, $b) = explode("=", $muutparametri);

				if (strpos($a, "[") !== FALSE) {
					$i = substr($a, strpos($a, "[")+1, strpos($a, "]")-(strpos($a, "[")+1));
					$a = substr($a, 0, strpos($a, "["));

					${$a}[$i] = $b;
				}
				else {
					${$a} = $b;
				}
			}
		}

		if (isset($luvut) and count($toim) > 0 and $submit_button != '') {
			$paiv  = 0;
			$lisaa = 0;

			foreach ($luvut as $liitostunnus => $rivi) {
				foreach ($rivi as $kausi => $solut) {
					foreach ($solut as $try => $solu) {

						if ($tuoteryhmittain == "") {
							$try = "";
						}

						$solu = str_replace(",", ".", $solu);

						if ($solu == '!' or $solu = (float) $solu) {

							if ($solu == '!') $solu = 0;

							$solu = (float) $solu;

							$query = "	SELECT summa
										FROM $budj_taulu
										WHERE yhtio 			= '$kukarow[yhtio]'
										AND $budj_sarak		 	= '$liitostunnus'
										AND kausi 				= '$kausi'
										AND dyna_puu_tunnus 	= ''
										AND osasto 				= ''
										AND try 				= '$try'";
							$result = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($result) > 0) {

								$budjrow = mysql_fetch_assoc($result);

								if ($budjrow['summa'] != $solu) {

									if ($solu == 0.00) {
										$query = "	DELETE FROM $budj_taulu
													WHERE yhtio 			= '$kukarow[yhtio]'
													AND $budj_sarak		 	= '$liitostunnus'
													AND kausi 				= '$kausi'
													AND dyna_puu_tunnus 	= ''
													AND osasto 				= ''
													AND try 				= '$try'";
									}
									else {
										$query	= "	UPDATE $budj_taulu SET
													summa = $solu,
													muuttaja = '$kukarow[kuka]',
													muutospvm = now()
													WHERE yhtio 			= '$kukarow[yhtio]'
													AND $budj_sarak		 	= '$liitostunnus'
													AND kausi 				= '$kausi'
													AND dyna_puu_tunnus 	= ''
													AND osasto 				= ''
													AND try 				= '$try'";
									}
									$result = mysql_query($query) or pupe_error($query);
									$paiv++;
								}
							}
							else {
								$query = "	INSERT INTO $budj_taulu SET
											summa 				= $solu,
											yhtio 				= '$kukarow[yhtio]',
											kausi 				= '$kausi',
											$budj_sarak		 	= '$liitostunnus',
											osasto 				= '',
											try 				= '$try',
											dyna_puu_tunnus 	= '',
											laatija 			= '$kukarow[kuka]',
											luontiaika 			= now(),
											muutospvm 			= now(),
											muuttaja 			= '$kukarow[kuka]'";
								$result = mysql_query($query) or pupe_error($query);
								$lisaa++;
							}
						}
					}
				}
			}
			echo "<font class='message'>".t("P�ivitin")." $paiv. ".t("Lis�sin")." $lisaa.</font><br /><br />";
		}

		if ($toim == "TOIMITTAJA" and $ytunnus != '' and $toimittajaid == 0) {

			$muutparametrit = "";

			unset($_POST["toimittajaid"]);

			foreach ($_POST as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $a => $b) {
						$muutparametrit .= $key."[".$a."]=".$b."##";
					}
				}
				else {
					$muutparametrit .= $key."=".$value."##";
				}
			}

			require ("inc/kevyt_toimittajahaku.inc");

			echo "<br />";

			if (trim($ytunnus) == '') {
				$submit_button = '';
			}
			else {
				$submit_button = 'OK';
			}
		}
		elseif ($toim == "" and $ytunnus != '' and $asiakasid == 0) {

			$muutparametrit = "";

			unset($_POST["asiakasid"]);

			foreach ($_POST as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $a => $b) {
						$muutparametrit .= $key."[".$a."]=".$b."##";
					}
				}
				else {
					$muutparametrit .= $key."=".$value."##";
				}
			}

			require ("inc/asiakashaku.inc");

			echo "<br />";

			if (trim($ytunnus) == '') {
				$submit_button = '';
			}
			else {
				$submit_button = 'OK';
			}
		}

		if (isset($_FILES['userfile']) and is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$ext = strtoupper($path_parts['extension']);

			if ($ext != "XLS") {
				die ("<font class='error'><br>".t("Ainoastaan .xls tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size'] == 0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
			}

			require_once ('excel_reader/reader.php');

			// ExcelFile
			$data = new Spreadsheet_Excel_Reader();

			// Set output Encoding.
			$data->setOutputEncoding('CP1251');
			$data->setRowColOffset(0);
			$data->read($_FILES['userfile']['tmp_name']);

			echo "<br /><br /><font class='message'>".t("Tarkastetaan l�hetetty tiedosto")."...<br><br></font>";

			$headers	 		= array();
			$budj_taulunrivit 	= array();
			$liitostunnukset 	= "";

			for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
				$headers[] = trim($data->sheets[0]['cells'][0][$excej]);
			}

			for ($excej = (count($headers)-1); $excej > 0 ; $excej--) {
				if ($headers[$excej] != "") {
					break;
				}
				else {
					unset($headers[$excej]);
				}
			}

			// Huomaa n�m� jos muutat excel-failin sarakkeita!!!!
			if ($toim == "TOIMITTAJA") {
				$lukualku = 3;				
			}
			else {
				$lukualku = 4;
			}
			
			if ($headers[$lukualku] == "Tuoteryhm�") {
				$lukualku++;
				$tuoteryhmittain = "on";
			}
			
			for ($excei = 1; $excei < $data->sheets[0]['numRows']; $excei++) {

				$liitun = $data->sheets[0]['cells'][$excei][0];

				$liitostunnukset .= $liitun.",";

				if ($tuoteryhmittain != "") {
					$try = $data->sheets[0]['cells'][$excei][$lukualku-1];
				}
				else {
					$try = "";
				}

				for ($excej = $lukualku; $excej < count($headers); $excej++) {
					$kasiind = str_replace("-", "", $headers[$excej]);

					$budj_taulunrivit[$liitun][$kasiind][$try] = trim($data->sheets[0]['cells'][$excei][$excej]);
				}
			}

			$liitostunnukset = substr($liitostunnukset, 0, -1);
		}
		
		#echo "<pre>",var_dump($budj_taulunrivit),"</pre>";

		if ($asiakasid > 0 or $toimittajaid > 0 or $liitostunnukset != "") {
			if ($toim == "TOIMITTAJA") {
				echo "<form method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='submit' name='vaihdaasiakas' value='",t("Vaihda tomittaja / nollaa excelrajaus"),"' />
						</form><br><br>";
			}
			else {
				echo "<form method='post'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='submit' name='vaihdaasiakas' value='",t("Vaihda asiakas / nollaa excelrajaus"),"' />
						</form><br><br>";
			}
		}

		echo "<form method='post' enctype='multipart/form-data'>
				<input type='hidden' name='toim' value='$toim'>";

		echo "<table>";

		$query = "	SELECT *
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY tilikausi_alku desc";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<tr><th>",t("Tilikausi"),"</th><td><select name='tkausi'>";

		while ($vrow = mysql_fetch_assoc($vresult)) {
			$sel = $tkausi == $vrow['tunnus'] ? ' selected' : '';
			echo "<option value = '$vrow[tunnus]'$sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
		}

		echo "</select></td></tr>";

		if ($liitostunnukset != "") {
			echo "<tr><th>",t("Rajaus"),"</th><td>".t("Excel-tiedostosta")."</td>";
			echo "<input type='hidden' name='liitostunnukset' value='$liitostunnukset'>";
		}
		else {
			if ($toim == "TOIMITTAJA") {
				echo "<tr><th>",t("Valitse toimittaja"),"</th>";

				if ($toimittajaid > 0) {
					$query = "	SELECT *
								from toimi
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$toimittajaid'";
					$result = mysql_query($query) or pupe_error($query);
					$toimirow = mysql_fetch_assoc($result);

					echo "<td>$toimirow[nimi] $toimirow[nimitark]<br>
							$toimirow[toim_nimi] $toimirow[toim_nimitark]
							<input type='hidden' name='toimittajaid' value='$toimittajaid' /></td>";
				}
				else {
					echo "<td><input type='text' name='ytunnus' value='$ytunnus' /></td></tr>";
				}
			}
			else {
				echo "<tr><th>",t("Valitse asiakas"),"</th>";

				if ($asiakasid > 0) {
					$query = "	SELECT *
								from asiakas
								where yhtio = '$kukarow[yhtio]'
								and tunnus = '$asiakasid'";
					$result = mysql_query($query) or pupe_error($query);
					$asiakasrow = mysql_fetch_assoc($result);

					echo "<td>$asiakasrow[nimi] $asiakasrow[nimitark]<br>
							$asiakasrow[toim_nimi] $asiakasrow[toim_nimitark]
							<input type='hidden' name='asiakasid' value='$asiakasid' /></td>";
				}
				else {
					echo "<td><input type='text' name='ytunnus' value='$ytunnus' /></td></tr>";
				}

				echo "<tr><th>".t("tai rajaa asiakaskategorialla")."</th><td>";

				$monivalintalaatikot = array('DYNAAMINEN_ASIAKAS', '<br>ASIAKASOSASTO', 'ASIAKASRYHMA');
				$monivalintalaatikot_normaali = array();

				require ("tilauskasittely/monivalintalaatikot.inc");

				echo "</td></tr>";
			}
		}

		$chk = "";

		if ($tuoteryhmittain != "") {
			$chk = "CHECKED";
		}

		echo "<tr><th>",t("Tuoteryhmitt�in"),"</th><td><input type='checkbox' name='tuoteryhmittain' $chk></td></tr>";
		echo "<tr><th>",t("Lue budjettiluvut tiedostosta"),"</th><td><input type='file' name='userfile' /></td>";
		echo "</table><br>";

		echo t("Budjettiluvun voi poistaa huutomerkill� (!)"),"<br />";

		echo "<br />";
		echo "<input type='submit' name='submit_button' id='submit_button' value='",t("N�yt�/Tallenna"),"' /><br>";

		if (!isset($lisa)) {
			$lisa = "";
		}
		if (!isset($lisa_dynaaminen)) {
			$lisa_dynaaminen = "";
		}
		if (!isset($lisa_parametri)) {
			$lisa_parametri = "";
		}

		if (trim($tkausi) != '') {
			$query = "	SELECT *
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus  = '$tkausi'";
			$vresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vresult) == 1) $tilikaudetrow = mysql_fetch_array($vresult);
		}

		if ($toimittajaid > 0) {
			$lisa .= " and toimi.tunnus = $toimittajaid ";
		}

		if ($asiakasid > 0) {
			$lisa .= " and asiakas.tunnus = $asiakasid ";
		}

		if ($submit_button != "" and ($asiakasid > 0 or $toimittajaid > 0 or $lisa != "" or $lisa_parametri != "" or $lisa_dynaaminen != "" or $liitostunnukset != "") and is_array($tilikaudetrow)) {

			if (!@include('Spreadsheet/Excel/Writer.php')) {
				echo "<font class='error'>",t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta."),"</font><br>";
				exit;
			}

			//keksit��n failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

			$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
			$workbook->setVersion(8);
			$worksheet =& $workbook->addWorksheet('Sheet 1');

			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();

			$excelrivi 	 = 0;
			$excelsarake = 0;

			if ($toim == "TOIMITTAJA") {
				$worksheet->write($excelrivi, $excelsarake, t("Toimittajan tunnus"), $format_bold);
				$excelsarake++;
			}
			else {
				$worksheet->write($excelrivi, $excelsarake, t("Asiakkaan tunnus"), $format_bold);
				$excelsarake++;
			}

			$worksheet->write($excelrivi, $excelsarake, t("Ytunnus"), $format_bold);
			$excelsarake++;

			if ($toim == "") {
				$worksheet->write($excelrivi, $excelsarake, t("Asiakasnro"), $format_bold);
				$excelsarake++;
			}
			
			$worksheet->write($excelrivi, $excelsarake, t("Nimi"), $format_bold);
			$excelsarake++;

			if ($liitostunnukset != "") {
				// Excelist� tulleet asiakkaat ylikirjaavaat muut rajaukset
				if ($toim == "TOIMITTAJA") {
					$lisa = " and toimi.tunnus in ($liitostunnukset) ";
				}
				else {
					$lisa = " and asiakas.tunnus in ($liitostunnukset) ";
				}

				$lisa_parametri = "";
				$lisa_dynaaminen = "";
			}

			if ($toim == "TOIMITTAJA") {
				$query = "	SELECT toimi.tunnus toimittajan_tunnus, toimi.ytunnus, toimi.ytunnus toimittajanro, toimi.nimi, toimi.nimitark
				 			#,IF(STRCMP(TRIM(CONCAT(toim_nimi, ' ', toim_nimitark)), TRIM(CONCAT(nimi, ' ', nimitark))) != 0, toim_nimi, '') toim_nimi,
							#IF(STRCMP(TRIM(CONCAT(toim_nimi, ' ', toim_nimitark)), TRIM(CONCAT(nimi, ' ', nimitark))) != 0, toim_nimitark, '') toim_nimitark
							FROM toimi
							$lisa_parametri
							$lisa_dynaaminen
							WHERE toimi.yhtio = '$kukarow[yhtio]'
							$lisa";
			}
			else {
				$query = "	SELECT asiakas.tunnus asiakkaan_tunnus, asiakas.ytunnus, asiakas.asiakasnro, asiakas.nimi, asiakas.nimitark,
							IF(STRCMP(TRIM(CONCAT(asiakas.toim_nimi, ' ', asiakas.toim_nimitark)), TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark))) != 0, asiakas.toim_nimi, '') toim_nimi,
							IF(STRCMP(TRIM(CONCAT(asiakas.toim_nimi, ' ', asiakas.toim_nimitark)), TRIM(CONCAT(asiakas.nimi, ' ', asiakas.nimitark))) != 0, asiakas.toim_nimitark, '') toim_nimitark
							FROM asiakas
							$lisa_parametri
							$lisa_dynaaminen
							WHERE asiakas.yhtio = '$kukarow[yhtio]'
							$lisa";
			}

			$result = mysql_query($query) or pupe_error($query);

			echo "<br />";
			echo "<table>";

			if ($toim == "TOIMITTAJA") {
				echo "<tr><th>",t("Toimittaja"),"</th>";
			}
			else {
				echo "<tr><th>",t("Asiakas"),"</th>";
			}

			if ($tuoteryhmittain != "") {
				echo "<th>",t("Tuoteryhm�"),"</th>";

				$worksheet->write($excelrivi, $excelsarake, t("Tuoteryhm�"), $format_bold);
				$excelsarake++;
			}

			$raja 		= '0000-00';
			$rajataulu 	= array();
			$sarakkeet	= 0;

			while ($raja < substr($tilikaudetrow['tilikausi_loppu'], 0, 7)) {

				$vuosi 	= substr($tilikaudetrow['tilikausi_alku'], 0, 4);
				$kk 	= substr($tilikaudetrow['tilikausi_alku'], 5, 2);
				$kk += $sarakkeet;

				if ($kk > 12) {
					$vuosi++;
					$kk -= 12;
				}

				if ($kk < 10) $kk = '0'.$kk;

				$rajataulu[$sarakkeet] = $vuosi.$kk;
				$sarakkeet++;

				$raja = $vuosi."-".$kk;

			 	echo "<th>$raja</th>";

				$worksheet->write($excelrivi, $excelsarake, $raja, $format_bold);
				$excelsarake++;
			}

			echo "</tr>";

			$excelrivi++;
			$xx = 0;

			function piirra_budj_rivi ($row, $tryrow = "") {
				global $kukarow, $toim, $worksheet, $excelrivi, $budj_taulu, $rajataulu, $budj_taulunrivit, $xx, $budj_sarak, $sarakkeet;

				$excelsarake = 0;

				$worksheet->writeNumber($excelrivi, $excelsarake, $row[$budj_sarak]);
				$excelsarake++;

				$worksheet->writeString($excelrivi, $excelsarake, $row['ytunnus']);
				$excelsarake++;

				if ($toim == "") {
					$worksheet->writeString($excelrivi, $excelsarake, $row['asiakasnro']);
					$excelsarake++;
				}
			
				$worksheet->writeString($excelrivi, $excelsarake, $row['nimi'].' '.$row['nimitark']);
				$excelsarake++;

				echo "<tr><td>$row[ytunnus] $row[asiakasnro]<br>$row[nimi] $row[nimitark]<br>$row[toim_nimi] $row[toim_nimitark]</td>";

				if (is_array($tryrow)) {
					echo "<td>$tryrow[selite] $tryrow[selitetark]</td>";

					$worksheet->write($excelrivi, $excelsarake, $tryrow["selite"]);
					$excelsarake++;

					$try = $tryrow["selite"];
					$try_ind = $try;
				}
				else {
					$try = "";
					$try_ind = 0;
				}

				for ($k = 0; $k < $sarakkeet; $k++) {
					$ik = $rajataulu[$k];

					if (isset($budj_taulunrivit[$row[$budj_sarak]][$ik][$try_ind])) {
						$nro = (float) trim($budj_taulunrivit[$row[$budj_sarak]][$ik][$try_ind]);
					}
					else {
						$query = "	SELECT *
									FROM $budj_taulu
									WHERE yhtio				= '$kukarow[yhtio]'
									and kausi		 		= '$ik'
									and $budj_sarak			= '$row[$budj_sarak]'
									and dyna_puu_tunnus		= ''
									and osasto				= ''
									and try					= '$try'";
						$xresult = mysql_query($query) or pupe_error($query);
						$nro = '';

						if (mysql_num_rows($xresult) == 1) {
							$brow = mysql_fetch_assoc($xresult);
							$nro = $brow['summa'];
						}
					}

					echo "<td>";

					if (is_array($tryrow)) {
						echo "<input type='text' name = 'luvut[{$row[$budj_sarak]}][{$ik}][{$tryrow["selite"]}]' value='{$nro}' size='8'></td>";
					}
					else {
						echo "<input type='text' name = 'luvut[{$row[$budj_sarak]}][{$ik}][]' value='{$nro}' size='8'>";
					}

					echo "</td>";

					$worksheet->writeNumber($excelrivi, $excelsarake, $nro);
					$excelsarake++;
				}

				echo "</tr>";

				$xx++;
				$excelrivi++;
			}


			if ($tuoteryhmittain != "") {
				// Haetaan tuoteryhm�t
				$res = t_avainsana("TRY");


				while ($tryrow = mysql_fetch_assoc($res)) {
					while ($row = mysql_fetch_assoc($result)) {
						piirra_budj_rivi($row, $tryrow);
					}

					mysql_data_seek($result, 0);
				}
			}
			else {
				while ($row = mysql_fetch_assoc($result)) {
					piirra_budj_rivi($row);
				}
			}

			$workbook->close();

			echo "</table></form><br />";

			echo "<form method='post'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Budjettimatriisi_asiakas.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<table>";
			echo "<tr><th>",t("Tallenna raportti (xls)"),":</th>";
			echo "<td class='back'><input type='submit' value='",t("Tallenna"),"'></td></tr>";
			echo "</table></form><br />";

		}
		else {
			echo "</form>";
		}
	}

	require ("inc/footer.inc");

?>