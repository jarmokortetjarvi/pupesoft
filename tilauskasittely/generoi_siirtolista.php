<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Luo siirtolista tuotepaikkojen h�lytysrajojen perusteella")."</font><hr>";

	if ($tee == 'M') {
		if ($kohdevarasto != '' and !empty($lahdevarastot) and !in_array($kohdevarasto, $lahdevarastot)) {

			$lisa = "";
			$abcjoin = "";

			if ($osasto != "") {
				$lisa .= " and tuote.osasto='$osasto' ";
			}

			if ($tuoteryhma != "") {
				$lisa .= " and tuote.try='$tuoteryhma' ";
			}

			if ($tuotemerkki != "") {
				$lisa .= " and tuote.tuotemerkki='$tuotemerkki' ";
			}

			if ($abcrajaus != "") {
				// joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
				$abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and
							abc_aputaulu.tuoteno = tuote.tuoteno and
							abc_aputaulu.tyyppi = '$abcrajaustapa' and
							(abc_aputaulu.luokka <= '$abcrajaus' or abc_aputaulu.luokka_osasto <= '$abcrajaus' or abc_aputaulu.luokka_try <= '$abcrajaus'))";
			}

			if ($toimittaja != "") {
				$query = "select distinct tuoteno from tuotteen_toimittajat where yhtio='$kukarow[yhtio]' and toimittaja='$toimittaja'";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) > 0) {
					$lisa .= " and tuote.tuoteno in (";
					while ($toimirow = mysql_fetch_array($result)) {
						$lisa .= "'$toimirow[tuoteno]',";
					}
					$lisa = substr($lisa,0,-1).")";
				}
				else {
					echo "<font class='error'>".t("Toimittaa ei l�ytynyt")."! ".t("Ajetaan ajo ilman rajausta")."!</font><br><br>";
				}
			}

			$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
			$delresult = mysql_query($query) or pupe_error($query);
			$kukarow['kesken'] = 0;

			$query = "SELECT * FROM varastopaikat WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kohdevarasto'";
			$result = mysql_query($query) or pupe_error($query);
			$varow = mysql_fetch_array($result);
			
			//katotaan tarvetta
			$query = "SELECT tuotepaikat.*, tuotepaikat.halytysraja-saldo tarve, concat_ws('-',hyllyalue, hyllynro, hyllyvali, hyllytaso) hyllypaikka, tuote.nimitys
						FROM tuotepaikat
						JOIN tuote on (tuote.yhtio = tuotepaikat.yhtio and tuote.tuoteno = tuotepaikat.tuoteno $lisa)
						$abcjoin
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
						and concat(rpad(upper('$varow[alkuhyllyalue]'),  5, '0'),lpad(upper('$varow[alkuhyllynro]'),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and concat(rpad(upper('$varow[loppuhyllyalue]'), 5, '0'),lpad(upper('$varow[loppuhyllynro]'), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
						and tuotepaikat.halytysraja != 0
						and tuotepaikat.halytysraja > saldo
						order by tuotepaikat.tuoteno";
			$resultti = mysql_query($query) or pupe_error($query);
			$luku = mysql_num_rows($result);

			if ((int) $olliriveja == 0 or $olliriveja == '') {
				$olliriveja = 20;
			}
			
			//	Otetaan luodut otsikot talteen
			$otsikot = array();
			
			//	Varmistetaan ettei olla miss��n kesken
			$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
			$delresult = mysql_query($query) or pupe_error($query);
			$kukarow["kesken"] = 0;
			
			// tehd��n jokaiselle valitulle lahdevarastolle erikseen
			foreach ($lahdevarastot as $lahdevarasto) {
				//	Varmistetaan ett� aloitetaan aina uusi otsikko uudelle varastolle
				$tehtyriveja = 0;
				
				// menn��n aina varmasti alkuun
				mysql_data_seek($resultti, 0);
				while ($pairow = mysql_fetch_array($resultti)) {
				
					//katotaan paljonko sinne on jo menossa
					$query = "SELECT sum(varattu) varattu
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
								JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tuoteno = '$pairow[tuoteno]'
								and varattu > 0
								and tyyppi = 'G'
								and lasku.clearing = '$kohdevarasto'";
					$vanresult = mysql_query($query) or pupe_error($query);
					$vanhatrow = mysql_fetch_array($vanresult);

					if ($pairow['tilausmaara'] > 0 and $pairow['tarve'] > 0) {
						$pairow['tarve'] = $pairow['tilausmaara'];
					}
				
					//ja v�hennet��n se tarpeesta
					//$pairow['tarve'] = $pairow['tarve'] - $vanhatrow['varattu'];
				
					//ei l�hetet� lis�� jos on jo matkalla
					if ($vanhatrow['varattu'] > 0) {
						$pairow['tarve'] = 0;
					}

					//katotaan myyt�viss� m��r�
					list(, , $saldo_myytavissa) = saldo_myytavissa($pairow["tuoteno"], "KAIKKI", $lahdevarasto);
				
					$saldo_myytavissa = (float) $saldo_myytavissa;
				
					if ($saldo_myytavissa > 0 and $pairow['tarve'] > 0) {
			
						if ($pairow['tarve'] >= $saldo_myytavissa) {
							$siirretaan = floor($saldo_myytavissa / 2);
						}
						else {
							$siirretaan = $pairow['tarve'];
						}

						if ($siirretaan > 0) {
						
							//	Onko meill� jo otsikko vai pit��k� tehd� uusi?
							if ($tehtyriveja == 0 or $tehtyriveja == (int) $olliriveja+1) {
								
								$jatka		= "kala";
							
								$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
								$delresult = mysql_query($query) or pupe_error($query);

								$kukarow["kesken"] = 0;

								$tilausnumero = $kukarow["kesken"];
								$clearing 	= $kohdevarasto;
								$toimpp 	= $kerpp = date(j);
								$toimkk 	= $kerkk = date(n);
								$toimvv 	= $kervv = date(Y);
								$comments 	= $kukarow["nimi"]." Generoi h�lytysrajojen perusteella";
								$viesti 	= $kukarow["nimi"]." Generoi h�lytysrajojen perusteella";
								$varasto 	= $lahdevarasto;
								$toim = "SIIRTOLISTA";

								//kato et t�� toimii!!! siis osoitteet!!!!
								require ("otsik_siirtolista.inc");

								$query = "	SELECT *
											FROM lasku
											WHERE tunnus = '$kukarow[kesken]'";
								$aresult = mysql_query($query) or pupe_error($query);

								if (mysql_num_rows($aresult) == 0) {
									echo "<font class='message'>".t("VIRHE: Tilausta ei l�ydy")."!<br><br></font>";
									exit;
								}

								$query = "select nimitys from varastopaikat where yhtio='{$kukarow["yhtio"]}' and tunnus = '$lahdevarasto'";
								$varres = mysql_query($query) or pupe_error($query);
								$varrow = mysql_fetch_array($varres);
								echo "<br><font class='message'>".t("Tehtiin siirtolistalle otsikko %s l�hdevarasto on %s", $kieli, $kukarow["kesken"], $varrow["nimitys"])."</font><br>";

								//	Otetaan luotu otsikko talteen
								$otsikot[] = $kukarow["kesken"];
								
								$laskurow = mysql_fetch_array($aresult);
							}
						
							$query = "	SELECT *
										FROM tuote
										WHERE tuoteno='$pairow[tuoteno]' and yhtio='$kukarow[yhtio]'";
							$rarresult = mysql_query($query) or pupe_error($query);

							if(mysql_num_rows($rarresult) == 1) {
								$trow = mysql_fetch_array($rarresult);
								$toimaika 			= $laskurow["toimaika"];
								$kerayspvm			= $laskurow["kerayspvm"];
								$tuoteno			= $pairow["tuoteno"];
								$kpl				= $siirretaan;
								$jtkielto 			= $laskurow['jtkielto'];
								$varasto			= $lahdevarasto;
								$hinta 				= "";
								$netto 				= "";
								$ale 				= "";
								$var				= "";							
								$korvaavakielto		= 1;
								$perhekielto		= 1;
								$orvoteikiinnosta	= "EITOD";
							
								require ('lisaarivi.inc');

								$tuoteno	= '';
								$kpl		= '';
								$hinta		= '';
								$ale		= '';
								$alv		= 'X';
								$var		= '';
								$toimaika	= '';
								$kerayspvm	= '';
								$kommentti	= '';

								$tehtyriveja ++;
							
								//echo "<font class='info'>".t("Siirtolistalle lis�ttiin %s tuotetta %s", $kieli, $siirretaan." ".$trow["yksikko"], $trow["tuoteno"])."</font><br>";							

							}
							else {
								echo t("VIRHE: Tuotetta ei l�ydy")."!<br>";
							}
						}
					}
				}
			}
			echo "</table><br>";

			if (count($otsikot) == 0) {
				echo "<font class='error'>".t("Kohdevaraston h�lyrajat on paukkunut eik� kamaa saatu mist��n toimitettua")."!!!!</font><br>";
			}
			else {
				echo "<font class='message'>".t("Luotiin %s siirtolistaa", $kieli, count($otsikot))."</font><br><br><br>";
				
				if($kesken != "X") {
					$query = "	UPDATE lasku SET alatila = 'J' WHERE yhtio = '$kukarow[yhtio]' and tunnus IN (".implode(",",$otsikot).")";
					$delresult = mysql_query($query) or pupe_error($query);
					echo "<font class='message'>".t("Siirtolistat siirretiin tulostusjonoon", $kieli, count($otsikot))."</font><br><br><br>";
				}
				else {
					echo "<font class='message'>".t("Siirtolistat j�tettiin kesken")."</font><br><br><br>";
				}
			}

			$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
			$delresult = mysql_query($query) or pupe_error($query);
			$tee = "";
		}
		else {
			echo "<font class='error'>".t("Varastonvalinnassa on virhe")."<br></font>";
			$tee = '';
		}
	}

	if ($tee == '') {
		// T�ll� ollaan, jos olemme sy�tt�m�ss� tiedostoa ja muuta
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value='M'>
				<table>";
				
		echo "<tr><th>".t("L�hdevarasto, eli varasto josta ker�t��n").":</th>";
		echo "<td colspan='4'>";

		$query  = "SELECT tunnus, nimitys, maa FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares))
		{
			$sel='';
			if (is_array($lahdevarastot) && in_array($varow['tunnus'], $lahdevarastot)) $sel = 'checked';

			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
				$varastomaa = '(' . maa(strtoupper($varow['maa'])) . ')';
			}

			echo "<input type='checkbox' name='lahdevarastot[]' value='$varow[tunnus]' $sel />$varow[nimitys] $varastomaa<br />";
		}

		echo "</td></tr>";

		echo "<tr><th>".t("Kohdevarasto, eli varasto jonne l�hetet��n").":</th>";
		echo "<td colspan='4'><select name='kohdevarasto'><option value=''>".t("Valitse")."</option>";

		$query  = "SELECT tunnus, nimitys, maa FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares))
		{
			$sel='';
			if ($varow['tunnus']==$kohdevarasto) $sel = 'selected';

			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maa'])) {
				$varastomaa = strtoupper($varow['maa']);
			}

			echo "<option value='$varow[tunnus]' $sel>$varastomaa $varow[nimitys]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><th>".t("Rivej� per tilaus (tyhj� = 20)").":</th><td><input type='text' size='8' value='$olliriveja' name='olliriveja'></td>";
		
		if($kesken == "X") {
			$c = "checked";
		}
		else {
			$c = "";
		}
		echo "<tr><th>".t("J�t� tilaus kesken").":</th><td><input type='checkbox' name = 'kesken' value='X' $c></td>";

		echo "<tr><th>".t("Osasto")."</th><td>";

		// tehd��n avainsana query
		$sresult = avainsana("OSASTO", $kukarow['kieli']);

		echo "<select name='osasto'>";
		echo "<option value=''>".t("Kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($osasto == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}
		echo "</select>";


		echo "</td></tr><tr><th>".t("Tuoteryhm�")."</th><td>";

		//Tehd��n osasto & tuoteryhm� pop-upit
		// tehd��n avainsana query
		$sresult = avainsana("TRY", $kukarow['kieli']);

		echo "<select name='tuoteryhma'>";
		echo "<option value=''>".t("Kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tuoteryhma == $srow["selite"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}
		echo "</select>";


		echo "</td></tr>
				<tr><th>".t("Tuotemerkki")."</th><td>";

		//Tehd��n osasto & tuoteryhm� pop-upit
		$query = "	SELECT distinct tuotemerkki
					FROM tuote
					WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != ''
					ORDER BY tuotemerkki";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<select name='tuotemerkki'>";
		echo "<option value=''>".t("Kaikki")."</option>";

		while ($srow = mysql_fetch_array($sresult)) {
			$sel = '';
			if ($tuotemerkki == $srow["tuotemerkki"]) {
				$sel = "selected";
			}
			echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
		}
		echo "</select>";

		echo "</td></tr>
			<tr><th>".t("Toimittaja")."</th><td><input type='text' size='20' name='toimittaja' value='$toimittaja'></td></tr>";

		$query  = "select count(*) from abc_aputaulu where yhtio='$kukarow[yhtio]' and tyyppi in ('TK','TR','TP')";
		$abcres = mysql_query($query) or pupe_error($query);
		$abcrow = mysql_fetch_array($abcres);

		// jos on niin n�ytet��n t�ll�nen vaihtoehto
		if ($abcrow[0] > 0) {
			echo "<tr><th>".t("ABC-luokkarajaus/rajausperuste")."</th><td>";

			$sel = array();
			$sel[$abcrajaus] = "SELECTED";

			echo "<select name='abcrajaus'>
			<option value=''>Ei rajausta</option>
			<option $sel[0] value='0'>Luokka A-30</option>
			<option $sel[1] value='1'>Luokka B-20 ja paremmat</option>
			<option $sel[2] value='2'>Luokka C-15 ja paremmat</option>
			<option $sel[3] value='3'>Luokka D-15 ja paremmat</option>
			<option $sel[4] value='4'>Luokka E-10 ja paremmat</option>
			<option $sel[5] value='5'>Luokka F-05 ja paremmat</option>
			<option $sel[6] value='6'>Luokka G-03 ja paremmat</option>
			<option $sel[7] value='7'>Luokka H-02 ja paremmat</option>
			<option $sel[8] value='8'>Luokka I-00 ja paremmat</option>
			</select>";

			$sel = array();
			$sel[$abcrajaustapa] = "SELECTED";

			echo "<select name='abcrajaustapa'>
			<option $sel[TK] value='TK'>Myyntikate</option>
			<option $sel[TR] value='TR'>Myyntirivit</option>
			<option $sel[TP] value='TK'>Myyntikappaleet</option>
			</select>
			</td></tr>";
		}

		echo "</table><br>
		<input type = 'submit' value = '".t("Generoi siirtolista")."'>
		</form>";
	}

	require ("../inc/footer.inc");
?>
