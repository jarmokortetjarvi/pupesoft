<?php

	/* FILEFORMAATTI (pupe_ennakkotilaustensyotto.xls):
	Tuoteno
	Nimitys
	Koko/M��r� 1
	Koko/M��r� 2
	Koko/M��r� 3
	Koko/M��r� ...
	Koko/M��r� N
	*/

	if ($tee == "file_matriisi") {
		if (is_uploaded_file($_FILES['userfile']['tmp_name']) === TRUE) {
			$timeparts = explode(" ",microtime());
			$starttime = $timeparts[1].substr($timeparts[0],1);

			$path_parts = pathinfo($_FILES['userfile']['name']);
			$name	= strtoupper($path_parts['filename']);
			$ext	= strtoupper($path_parts['extension']);

			if ($ext != "TXT" and $ext != "CSV" and $ext != "XLS") {
				die ("<font class='error'><br>".t("Ainoastaan .txt, .csv tai .xls tiedostot sallittuja")."!</font>");
			}

			if ($_FILES['userfile']['size']==0) {
				die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
			}

			if (strtoupper($ext) == "XLS") {
				require_once ('excel_reader/reader.php');

				// ExcelFile
				$data = new Spreadsheet_Excel_Reader();

				// Set output Encoding.
				$data->setOutputEncoding('CP1251');
				$data->setRowColOffset(0);
				$data->read($_FILES['userfile']['tmp_name']);
			}
			else {
				$file = fopen($_FILES['userfile']['tmp_name'],"r") or die (t("Tiedoston avaus ep�onnistui")."!");
			}

			// luetaan tiedosto loppuun ja tehd��n array koko datasta
			$excelrivi = array();

			if (strtoupper($ext) == "XLS") {
				for ($excei = 0; $excei < $data->sheets[0]['numRows']; $excei++) {
					for ($excej = 0; $excej < $data->sheets[0]['numCols']; $excej++) {
						$excelrivi[$excei][$excej] = $data->sheets[0]['cells'][$excei][$excej];
					}
				}
			}
			else {
				$excei = 0;

				while ($rivi = fgets($file)) {
					// luetaan rivi tiedostosta..
					$poista	 = array("'", "\\");
					$rivi	 = str_replace($poista,"",$rivi);
					$rivi	 = explode("\t", trim($rivi));

					$excej = 0;
					foreach ($rivi as $riv) {
						$excelrivi[$excei][$excej] = $riv;
						$excej++;
					}
					$excei++;
				}
				fclose($file);
			}

			#echo "<table>";

			$rivilask1 = 1;

			for ($er1 = 0; $er1 < count($excelrivi); $er1++) {

				#echo "<tr>";

				for ($er2 = 0; $er2 < count($excelrivi[$er1]); $er2++) {
					#echo "<td>$excelrivi[$er1][$er2]</td>";
				}

				// T�m� taitaa olla tuoterivi
				if (trim($excelrivi[$er1][0]) != "" and trim($excelrivi[$er1][1]) != "") {

					$tuokoodi = trim($excelrivi[$er1][0]);

					// Haetaan rivi jossa on kokosarakkeiden "otsikot", eli koot
					for ($er3 = $er1; $er3 >= 0; $er3--) {
						if (preg_match("/koot|size|koko/i", $excelrivi[$er3][2]) and !preg_match("/onesize|one size/i", $excelrivi[$er3][2])) {
							$kokorivi = $er3+1;
							break;
						}
					}

					// K�yd��n l�pi k�ytt�j�n sy�tt�m�t m��r�t, sarakkeesta "C" eteenp�in
					for ($er4 = 2; $er4 < count($excelrivi[$er1]); $er4++) {

						// M��r�
						$kpl = (float) str_replace(",", ".", $excelrivi[$er1][$er4]);

						if ($kpl != 0) {

							// Koko
							$koko = $excelrivi[$kokorivi][$er4];

							$ohitus = 0;
							$tyoteloyty = FALSE;

							#echo "<th>$koko/$kpl</th>";

							$query = "	SELECT *
										FROM tuote
										WHERE yhtio = '$kukarow[yhtio]'
										AND (tuoteno like '{$mikrotila_sesonki}\_{$tuokoodi}% $koko' or tuoteno like '{$mikrotila_sesonki}\_{$tuokoodi}%\_$koko')
										AND status != 'P'";
							$rarresult = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($rarresult) == 1) {
								$trow = mysql_fetch_assoc($rarresult);
								$tyoteloyty = TRUE;

								#echo "<th>$trow[tuoteno]: $kpl</th>";
							}

							if (!$tyoteloyty) {
								$query = "	SELECT *
											FROM tuote
											WHERE yhtio = '$kukarow[yhtio]'
											AND (tuoteno like '{$tuokoodi}% $koko' or tuoteno like '{$tuokoodi}%\_$koko')
											AND status != 'P'";
								$rarresult = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($rarresult) == 1) {
									$trow = mysql_fetch_assoc($rarresult);
									$tyoteloyty = TRUE;

									#echo "<th>$trow[tuoteno]: $kpl</th>";
								}
							}

							if (!$tyoteloyty) {
								$query = "	SELECT *
											FROM tuote
											WHERE yhtio = '$kukarow[yhtio]'
											AND (tuoteno like '{$mikrotila_sesonki}\_{$tuokoodi}% $koko' or tuoteno like '{$mikrotila_sesonki}\_{$tuokoodi}%\_$koko')";
								$rarresult = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($rarresult) == 1) {
									$trow = mysql_fetch_assoc($rarresult);
									$tyoteloyty = TRUE;

									#echo "<th>$trow[tuoteno]: $kpl</th>";
								}
							}

							if (!$tyoteloyty) {
								$query = "	SELECT *
											FROM tuote
											WHERE yhtio = '$kukarow[yhtio]'
											AND (tuoteno like '{$tuokoodi}% $koko' or tuoteno like '{$tuokoodi}%\_$koko')";
								$rarresult = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($rarresult) == 1) {
									$trow = mysql_fetch_assoc($rarresult);
									$tyoteloyty = TRUE;

									#echo "<th>$trow[tuoteno]: $kpl</th>";
								}
							}

							if (!$tyoteloyty) {
								echo "<font class='message'>VIRHE: Rivill� $rivilask1: ".t("Tuotetta")." $mikrotila_sesonki $tuokoodi $koko ".t("ei l�ydy")."!</font><br>";
								$ohitus = 1;
							}


							if ($ohitus == 0) {

								$tuoteno 			= $trow["tuoteno"];
								$toimaika			= $laskurow["toimaika"];
								$kerayspvm			= $laskurow["toimaika"];
								$hinta				= "";
								$netto				= "";
								$ale				= "";
								$var				= "";
								$alv				= "";
								$paikka				= "";
								$varasto 			= $laskurow["varasto"];
								$rivitunnus			= "";
								$korvaavakielto		= "";
								$varataan_saldoa	= "";
								$var				= "";
								$perhekielto		= "";
								$jtkielto 		 	= $laskurow['jtkielto'];

						        if ($laskurow["tilaustyyppi"] == "E") {
						        	$varataan_saldoa = "EI";
						        }
						        else {
						        	$varataan_saldoa = "";
						        }

						        //lis�t��n rivi
						        require ("lisaarivi.inc");

								if ($rivilask1 % 10 == 0) flush();

							}
						}
					}
				}

				$rivilask1++;
				#echo "</tr>";
			}

			#echo "</table><br>";
		}

		$tuoteno	= '';
		$kpl		= '';
		$var		= '';
		$hinta		= '';
		$netto		= '';
		$ale		= '';
		$rivitunnus	= '';
		$kommentti	= '';
		$kerayspvm	= '';
		$toimaika	= '';
		$paikka		= '';
		$alv		= '';
		$varasto 	= '';
		$perheid 	= '';
		$perheid2  	= '';
		$tee 		= 'Y';
	}

	if ($tee == 'mikrotila_matriisi') {
		echo "<font class='head'>$otsikko</font><hr>";

		echo "<form method='post' name='sendfile' enctype='multipart/form-data' action='$PHP_SELF'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				<input type='hidden' name='tilausnumero' value='$tilausnumero'>
				<input type='hidden' name='tee' value='file_matriisi'>
				<br>
				<table>";

		echo "<tr><th>".t("Valitse tiedosto").":</th><td><input name='userfile' type='file'></td></tr>";

		echo "<tr><th>".t("Valitse sesonki:")."</th>
				<td><select name='mikrotila_sesonki'>
				<option value='SS10'>".t("SS10")."
				<option value='FW10'>".t("FW10")."
				<option value='SS11'>".t("SS11")."
				<option value='FW11'>".t("FW11")."
				<option value='SS12' SELECTED>".t("SS12")."
				<option value='FW12'>".t("FW12")."
				<option value='SS13'>".t("SS13")."
				<option value='FW13'>".t("FW13")."
				</select></td></tr>";
		echo "<tr><td colspan='2' class='back'><input type='submit' value='".t("Lis�� tuotteet")."'></td></tr>";
		echo "</table></form>";
	}
?>