<?php

	require ("inc/parametrit.inc");

	if ($tee== 'PERUSTA') {

		if(strpos($tuoteno, '####') !== FALSE) {
			$hakyhtio	= substr($tuoteno, strpos($tuoteno, '####')+4);
			$tuoteno 	= substr($tuoteno, 0, strpos($tuoteno, '####'));
		}
		else {
			$hakyhtio = $kukarow["yhtio"];
		}
		
		//	Trimmataan tyhj�t merkit
		$uustuoteno = trim($uustuoteno);
		
		$query = "	SELECT tunnus
					FROM tuote
					WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 0 ) {
			$tee = 'HAKU';
			$varaosavirhe = t("VIRHE: Uudella tuotenumerolla")." $uustuoteno ".t("l�ytyy jo tuote, ei voida perustaa")."!";
		}
		else {
			$query = "	SELECT *
						FROM tuote
						WHERE yhtio = '$hakyhtio' and tuoteno = '$tuoteno'";
			$stresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($stresult) == 0 ) {
				$tee = 'HAKU';
				$varaosavirhe = t("VIRHE: Vanha tuote")." $tuoteno ".t("on kadonnut, ei uskalleta tehd� mit��n")."!";
			}
			else {
				$otsikkorivi = mysql_fetch_array($stresult);
				
				$tuotepaikat_query = "	SELECT *
										FROM tuotepaikat
										WHERE tuoteno = '$tuoteno' and yhtio = '$kukarow[yhtio]'";
				$tuotepaikat_result = mysql_query($tuotepaikat_query) or pupe_error($tuotepaikat_query);
				
				if ($yhtiorow["tuotteen_oletuspaikka"] != "" and mysql_num_rows($tuotepaikat_result) == 0 and $otsikkorivi["ei_saldoa"] == "") {
					list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("-", $yhtiorow["tuotteen_oletuspaikka"]);

					if ($hyllyalue == "") {
						$hyllyalue = 0;
					}
					if ($hyllynro == "") {
						$hyllynro = 0;
					}
					if ($hyllyvali == "") {
						$hyllyvali = 0;
					}
					if ($hyllytaso == "") {
						$hyllytaso = 0;
					}

					$tuotepaikka_query = "	INSERT INTO tuotepaikat set
					 						yhtio			= '$kukarow[yhtio]',
								 			tuoteno     	= '$tuoteno',
								 			oletus      	= 'X',
						   		 			saldoaika   	= now(),
											hyllyalue   	= '$hyllyalue',
											hyllynro    	= '$hyllynro',
											hyllyvali   	= '$hyllyvali',
											hyllytaso   	= '$hyllytaso'
											luontiaika		= now(),
											laatija			= '$kukarow[kuka]',
											muutospvm		= now(),
											muuttaja		= '$kukarow[kuka]'";
					$tuotepaikka_result = mysql_query($tuotepaikka_query) or pupe_error($tuotepaikka_query);
				}

				// tehd��n vanhasta tuotteesta 1:1 kopio...
				$query = "INSERT into tuote set ";

				for ($i=0; $i<mysql_num_fields($stresult); $i++) {


					if (mysql_field_name($stresult,$i)=='yhtio') {
						$query .= "yhtio='$kukarow[yhtio]',";
					}
					// tuotenumeroksi tietenkin uustuoteno
					elseif (mysql_field_name($stresult,$i)=='tuoteno') {
						$query .= "tuoteno='$uustuoteno',";
					}
					// laatijaksi klikkaaja
					elseif (mysql_field_name($stresult,$i)=='laatija') {
						$query .= "laatija='$kukarow[kuka]',";
					}
					// luontiaika
					elseif (mysql_field_name($stresult,$i)=='luontiaika') {
						$query .= mysql_field_name($stresult,$i)."=now(),";
					}
					// luontiaika
					elseif (mysql_field_name($stresult,$i)=='muutospvm') {
						$query .= mysql_field_name($stresult,$i)."=now(),";
					}
					
					// n�m� kent�t tyhjennet��n
					elseif (mysql_field_name($stresult,$i)=='kehahin' or
							mysql_field_name($stresult,$i)=='vihahin' or
							mysql_field_name($stresult,$i)=='vihapvm' or
							mysql_field_name($stresult,$i)=='epakurantti25pvm' or
							mysql_field_name($stresult,$i)=='epakurantti50pvm' or
							mysql_field_name($stresult,$i)=='epakurantti75pvm' or
							mysql_field_name($stresult,$i)=='epakurantti100pvm') {
						$query .= mysql_field_name($stresult,$i)."='',";
					}
					// ja kaikki muut paitsi tunnus sellaisenaan
					elseif (mysql_field_name($stresult,$i)!='tunnus') {
						$query .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
					}
				}
				$query = substr($query,0,-1);

				$stresult = mysql_query($query) or pupe_error($query);
				$id = mysql_insert_id();

				$query = "	SELECT *
							FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
				$stresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($stresult) != 0 ) {
					while ($otsikkorivi = mysql_fetch_array($stresult)) {
						// tehd��n vanhoista tuotteen_toimittajista 1:1 kopio...
						$query = "insert into tuotteen_toimittajat set ";
						for ($i=0; $i<mysql_num_fields($stresult); $i++) {

							// tuotenumeroksi tietenkin uustuoteno
							if (mysql_field_name($stresult,$i)=='tuoteno') {
								$query .= "tuoteno='$uustuoteno',";
							}
							// ja kaikki muut paitsi tunnus sellaisenaan
							elseif (mysql_field_name($stresult,$i)!='tunnus') {
								$query .= mysql_field_name($stresult,$i)."='".$otsikkorivi[$i]."',";
							}
						}
						$query = substr($query,0,-1);

						$astresult = mysql_query($query) or pupe_error($query);
						$id2 = mysql_insert_id();
					}
				}
				$query = "	SELECT tunnus
							FROM tuote
							WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$uustuoteno'";
				$result = mysql_query($query) or pupe_error($query);
				$rivi = mysql_fetch_array($result);

				//	L�hetet��n mailia t�st� eteenp�in jos meill� on vastaanottajia
				if($yhtiorow["tuotekopio_email"] != "") {
					$header  = "From: <$yhtiorow[postittaja_email]>\n";
					$header .= "MIME-Version: 1.0\n" ;
					
					$query = "select * from yhtio where yhtio='$hakyhtio'";
					$yres = mysql_query($query) or pupe_error($query);
					$yrow = mysql_fetch_array($yres);
					
					$content = $kukarow["nimi"]." ".t("kopioi yhti�n")." $yrow[nimi] ".t("tuotteen")." '$tuoteno' ".t("yhti�n")." $yhtiorow[nimi] ".t("tuotteeksi")." '$uustuoteno'\n\n";
										
					mail($yhtiorow["tuotekopio_email"], t("Tuotteita kopioitu"), $content, $header, "-f $yhtiorow[postittaja_email]");
				}
				
				$toim 	= 'tuote';
				$tunnus = $rivi['tunnus'];
				$tee 	= '';

				require ("yllapito.php");
				
				exit;
			}
		}
	}

	if ($tee != 'PERUSTA') {
		echo "<font class='head'>".t("Kopioi tuote")."</font><hr>";
	}

	if ($tee == 'HAKU') {
		$konsernihaku = "KYLLA";
		
		if (strpos($tuoteno, '*') === FALSE) {
			$tuoteno = $tuoteno."*";
		}
		
		require("inc/tuotehaku.inc");
		
		//on vaan l�ytynyt 1 muuten tulis virhett� ja ulosta
		if ($tee == 'HAKU' and $ulos == '' and $varaosavirhe == '' and $tuoteno != '') {
			$tee = 'AVALITTU';
		}
	}

	if ($tee == 'AVALITTU' and $tuoteno != '') {
		$formi  = 'performi';
		$kentta = 'uustuoteno';

		echo "<table>";
		echo "<tr><th>".t("Kopioitava tuote")."</th></tr>";

		if(strpos($tuoteno, '####') !== FALSE) {
			$tu = substr($tuoteno, strpos($tuoteno, '####')+4)." - ".substr($tuoteno, 0, strpos($tuoteno, '####'));
		}
		else {
			$tu = $tuoteno;
		}

		echo "<tr><td>$tu</td>";

		echo "<tr><th>".t("Anna uusi tuotenumero")."<br>".t("joka perustetaan")."</th></tr>";
		echo "<tr><form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='PERUSTA'>";
		echo "<input type='hidden' name='tuoteno' value='$tuoteno'>";
		echo "<td><input type='text' name='uustuoteno' size='22' maxlength='20' value=''></td>";
		echo "<td class='back'><input type='Submit' value='".t("Kopioi")."'></td>";
		echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
		echo "</form></tr></table>";
	}

	if (($tee == 'HAKU' or $tee == "Y") and $ulos != '') {
			echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='AVALITTU'>";
			echo "<table><tr>";
			echo "<th>".t("Valitse listasta").":</th></tr>";
			echo "<tr><td>$ulos</td>";
			echo "<td class='back'><input type='Submit' value='".t("Valitse")."'></td>";
			echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
			echo "</tr></table>";
			echo "</form>";

			$varaosavirhe = "";
	}

	if($tee == '' or $tee == "Y") {
		$formi  = 'formi';
		$kentta = 'tuoteno';

		echo "<table><tr>";
		echo "<th>".t("Anna tuotenumero josta")."<br>".t("haluat kopioda tiedot")."</th>";

		echo "<tr><form action='$PHP_SELF' method='post' name='$formi' autocomplete='off'>";
		echo "<input type='hidden' name='tee' value='HAKU'>";
		echo "<td><input type='text' name='tuoteno' size='22' maxlength='20' value=''></td>";
		echo "<td class='back'><input type='Submit' value='".t("Jatka")."'></td>";
		echo "<td class='back'><font class='error'>$varaosavirhe</font></td>";
		echo "</form></tr></table>";
	}

	require("inc/footer.inc");
?>