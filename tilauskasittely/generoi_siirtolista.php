<?php
	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Luo siirtolista tuotepaikkojen h�lytysrajojen perusteella")."</font><hr>";

	if ($tee == 'M') {
		if ($kohdevarasto != '' and $lahdevarasto != '' and $kohdevarasto != $lahdevarasto) {
			$lask = 0;
			
			$query = "	UPDATE kuka SET kesken = 0 WHERE yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
			$delresult = mysql_query($query) or pupe_error($query);
			$kukarow['kesken'] = 0;
			
			$query = "SELECT * FROM varastopaikat WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kohdevarasto'";
			$result = mysql_query($query) or pupe_error($query);
			$varow = mysql_fetch_array($result);

			$query = "SELECT tuotepaikat.*, tuotepaikat.halytysraja-saldo tarve, concat_ws('-',hyllyalue, hyllynro, hyllyvali, hyllytaso) hyllypaikka, tuote.nimitys
						FROM tuotepaikat, tuote
						WHERE tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
						and tuotepaikat.yhtio = '$kukarow[yhtio]'
						and hyllyalue >= '$varow[alkuhyllyalue]' and hyllynro >= '$varow[alkuhyllynro]'
						and hyllyalue <= '$varow[loppuhyllyalue]' and hyllynro <= '$varow[loppuhyllynro]'
						and tuotepaikat.halytysraja != 0
						and tuotepaikat.halytysraja > saldo
						order by tuotepaikat.tuoteno";
			$resultti = mysql_query($query) or pupe_error($query);
			$luku = mysql_num_rows($result);

			$tehtyriveja = 0;
			$otsikoita = 0;
			if ((int) $olliriveja == 0 or $olliriveja == '') {
				$olliriveja = 20;
			}

			while ($pairow = mysql_fetch_array($resultti)) {

				if ($tehtyriveja == 0 or $tehtyriveja == (int) $olliriveja+1) {
					$jatka		= "kala";
					if ($kukarow['kesken'] != 0) {
						$query = "	UPDATE lasku SET alatila = 'J' WHERE tunnus = '$kukarow[kesken]'";
						$delresult = mysql_query($query) or pupe_error($query);
					}


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

					require ("otsik_siirtolista.inc");

					$query = "	SELECT *
								FROM lasku
								WHERE tunnus = '$kukarow[kesken]'";
					$aresult = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($aresult) == 0) {
						echo "<font class='message'>".t("VIRHE: Tilausta ei l�ydy")."!<br><br></font>";
						exit;
					}
					$laskurow = mysql_fetch_array($aresult);

					$tehtyriveja = 1;
					$otsikoita ++;

				}

				if ($pairow['tilausmaara'] > 0 and $pairow['tarve'] > 0 and $pairow['tilausmaara'] > $pairow['tarve']) {
					$pairow['tarve'] = $pairow['tilausmaara'];
				}

				$query = "	SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso, concat_ws('-',hyllyalue, hyllynro, hyllyvali, hyllytaso) hyllypaikka,
							varastopaikat.nimitys, varastopaikat.tunnus, tuotepaikat.oletus,
							if(saldo >= $pairow[tarve],0,1) tarpeeks, saldo,
							concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0')) ihmepaikka
							FROM tuotepaikat, varastopaikat
							WHERE tuotepaikat.yhtio = varastopaikat.yhtio
							and concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0')) >= concat(rpad(upper(alkuhyllyalue) ,3,'0'),lpad(alkuhyllynro ,2,'0'))
							and concat(rpad(upper(tuotepaikat.hyllyalue) ,3,'0'),lpad(tuotepaikat.hyllynro ,2,'0')) <= concat(rpad(upper(loppuhyllyalue) ,3,'0'),lpad(loppuhyllynro ,2,'0'))
							and tuotepaikat.yhtio = '$kukarow[yhtio]'
							and tuotepaikat.tuoteno = '$pairow[tuoteno]'
							and varastopaikat.tunnus = '$lahdevarasto'
							and saldo > 0
							order by tarpeeks asc, tuotepaikat.oletus desc
							limit 1";
				$result2 = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result2) != 0 and $pairow['tarve'] > 0) {

					while($uuspairow = mysql_fetch_array($result2)) {
						if ($pairow['tarve'] > $uuspairow['saldo']) {
							$siirretaan = $uuspairow['saldo'];
						}
						else {
							$siirretaan = $pairow['tarve'];
						}

						$query = "	SELECT sum(varattu)
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$pairow[tuoteno]'
									and varattu > 0
									and tyyppi in ('L','G','V')
									and hyllyalue = '$uuspairow[hyllyalue]' and hyllynro = '$uuspairow[hyllynro]' and hyllyvali = '$uuspairow[hyllyvali]' and hyllytaso = '$uuspairow[hyllytaso]'";
						$vararesult = mysql_query($query) or pupe_error($query);
						$vararow=mysql_fetch_array($vararesult);

						if ($vararow[0]== '') {
							$vararow[0] = 0;
						}

						$jaljella	= $uuspairow['saldo']-$vararow[0];

						if ($siirretaan > $jaljella) {
							$siirretaan = $jaljella;
						}

						if ($siirretaan > 0 and $jaljella > 0) {
							//echo "<tr><td>$pairow[tuoteno]</td><td>$pairow[nimitys]</td><td>$pairow[halytysraja]</td><td>$pairow[saldo]</td><td>$uuspairow[saldo]</td><td>$vararow[0]</td><td>$pairow[tarve]</td><td>$siirretaan</td><td>$pairow[hyllypaikka]</td><td>$uuspairow[hyllypaikka]</td><td>$varow[nimitys]</td><td>$uuspairow[nimitys]</td><td>$uuspairow[tunnus]</td><td>$uuspairow[oletus]</td><td>$uuspairow[tarpeeks]</td></tr>";

							$query = "	SELECT *
										FROM tuote
										WHERE tuoteno='$pairow[tuoteno]' and yhtio='$kukarow[yhtio]'";
							$rarresult = mysql_query($query) or pupe_error($query);

							if(mysql_num_rows($rarresult) == 1) {

								$trow = mysql_fetch_array($rarresult);
								$toimaika 	= $laskurow["toimaika"];
								$kerayspvm	= $laskurow["kerayspvm"];
								$tuoteno	= $pairow["tuoteno"];
								$kpl		= $siirretaan;
								$paikka		= "$uuspairow[hyllyalue]#$uuspairow[hyllynro]#$uuspairow[hyllyvali]#$uuspairow[hyllytaso]";
								$hinta 		= "";
								$netto 		= "";
								$ale 		= "";
								$var		= "";

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

							}
							else {
								echo t("VIRHE: Tuotetta ei l�ydy")."!<br>";
							}
							$lask++;
						}
					}
				}
			}
			echo "</table><br>";
			//echo "lask = $lask<br><br>";
			if ($lask == 0) {
				echo "<font class='error'>".t("Yht��n rivi� ei voitu toimittaa l�hdevarastosta kohdevarastoon")."!!!!</font>";
				$query = "	SELECT count(*) FROM tilausrivi WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]'";
				$okdelresult = mysql_query($query) or pupe_error($query);
				
				if (mysql_num_rows($okdelresult) == 0 and $kukarow['kesken'] != 0) {
					$query = "	UPDATE lasku SET tila = 'D', alatila = 'G' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[kesken]'";
					$delresult = mysql_query($query) or pupe_error($query);
				}
				elseif ($kukarow['kesken'] != 0){
					echo "<font class='error'>".t("APUAAAA tilauksella $kukarow[kesken] on rivej� vaikka luultiin ett� ei olisi!!!!!")."<br></font>";
				}
				
			}
			else {
				echo "<font class='message'>".t("Luotiin")." $otsikoita ".t("siirtolistaa")."</font><br><br><br>";
				$query = "	UPDATE lasku SET alatila = 'J' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[kesken]'";
				$delresult = mysql_query($query) or pupe_error($query);
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
		echo "<td colspan='4'><select name='lahdevarasto'><option value=''>".t("Valitse")."</option>";

		$query  = "SELECT tunnus, nimitys, maa FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares))
		{
			$sel='';
			if ($varow['tunnus']==$lahdevarasto) $sel = 'selected';
			
			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maakoodi'])) {
				$varastomaa = strtoupper($varow['maa']);
			}
			
			echo "<option value='$varow[tunnus]' $sel>$varastomaa $varow[nimitys]</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><th>".t("Kohdevarasto, eli varasto jonne l�hetet��n").":</th>";
		echo "<td colspan='4'><select name='kohdevarasto'><option value=''>".t("Valitse")."</option>";

		$query  = "SELECT tunnus, nimitys, maa FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		while ($varow = mysql_fetch_array($vares))
		{
			$sel='';
			if ($varow['tunnus']==$kohdevarasto) $sel = 'selected';
			
			$varastomaa = '';
			if (strtoupper($varow['maa']) != strtoupper($yhtiorow['maakoodi'])) {
				$varastomaa = strtoupper($varow['maa']);
			}

			echo "<option value='$varow[tunnus]' $sel>$varastomaa $varow[nimitys]</option>";
		}

		echo "</select></td></tr>";
		
		echo "<tr><th>".t("Rivej� per tilaus (tyhj� = 20)").":</th><td><input type='text' size='8' value='' name='olliriveja'></td>";

		echo "</table><br>
		<input type = 'submit' value = '".t("Generoi siirtolista")."'>
		</form>";
	}

	require ("../inc/footer.inc");
?>