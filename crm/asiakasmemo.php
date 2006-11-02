<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Asiakasmemo")."</font><hr>";

    if ($ytunnus == '') {
		echo "<br><table>";
		echo "<tr>
				<th>".t("Asiakkaan nimi").": </th>
				<form action = '$PHP_SELF' method = 'post'>
				<td class='back'><input type='text' size='30' name='ytunnus'></td>
				<td class='back'><input type='submit' value='".t("Jatka")."'></td>
				</tr>";
		echo "</form>";
		echo "</table>";
	}

	if ($ytunnus != '') {
		require ("../inc/asiakashaku.inc");
	}

	///* Asiakas on valittu *///
	if ($ytunnus != '') {

		///* Jos ollaan käyty ylläpidossa *///
		if ($yllapidossa == 'asiakas') {
			$yhtunnus = '';
		}

		if  ($yllapidossa == 'yhteyshenkilo') {
			$yhtunnus = $yllapidontunnus;
		}

		///* Allrightin asiakasanalyysi*///
		if ($tee == "ASANALYYSI") {

			echo "<table>";
			echo "<form action='$PHP_SELF' method='POST'>
					<input type='hidden' name='tee' value='LISAAASANALYYSI'>
					<input type='hidden' name='ytunnus' value='$ytunnus'>
					<input type='hidden' name='yhtunnus' value='$yhtunnus'>";

			echo "<tr><th colspan='2' align='left'><br>".t("Asiakasvertailu:")."</th></tr>";
			echo "<tr><td>".t("Miten tuotteet esilla:")."</td><td><textarea name='esilla' cols='40' rows=5' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Myymalan ileisilme:")."</td><td><textarea name='yleisilme' cols='40' rows='5' wrap='hard'></textarea></td></tr>";
			echo "<tr><th colspan='2' align='left'><br>".t("Tuotteiden jakauma merkeittain:")."</th></tr>";
			echo "<tr><td>".t("Puvut:")."</td><td><textarea name='puvut' 		cols='40' rows='2' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Kyparat:")."</td><td><textarea name='kyparat' 	cols='40' rows='2' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Saappaat:")."</td><td><textarea name='saappaat' 	cols='40' rows='2' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Hanskat:")."</td><td><textarea name='hanskat'		cols='40' rows='2' wrap='hard'></textarea></td></tr>";
			echo "<tr><th><br></th><th></th></tr>";
			echo "<tr><td>".t("Muut huomiot:")."</td><td><textarea name='muuta' 	cols='40' rows='5' wrap='hard'></textarea></td></tr>";
			echo "<tr><td>".t("Keskustelut/sovitut extrat:")."</td><td><textarea name='extrat' 	cols='40' rows='5' wrap='hard'></textarea></td></tr>";
			echo "<tr><th align='left'><input type='submit' name='submit' value='".t("Tallenna")."'></th></tr>";
			echo "</form></table>";

		}

		///* Lisätään uusi memeotietue*///
		if ($tee == "UUSIMEMO") {
			if ($korjaus == '') {
				if ($viesti != '') {
					$kysely = "	INSERT INTO kalenteri
								SET tapa = '$tapa',
								asiakas  = '$ytunnus',
								henkilo  = '$yhtunnus',
								kuka     = '$kukarow[kuka]',
								yhtio    = '$kukarow[yhtio]',
								tyyppi   = 'Memo',
								pvmalku  = now(),
								kentta01 ='$viesti'";
					$result = mysql_query($kysely) or pupe_error($kysely);
				}
			}

			else {
				$kysely = "	UPDATE kalenteri
							SET tapa = '$tapa',
							asiakas  = '$ytunnus',
							henkilo  = '$kyhtunnus',
							kuka     = '$kukarow[kuka]',
							yhtio    = '$kukarow[yhtio]',
							tyyppi   = 'Memo',
							pvmalku  = now(),
							kentta01 ='$viesti'
							WHERE tunnus='$korjaus'";
				$result = mysql_query($kysely) or pupe_error($kysely);
			}
			$tee = '';
		}

		///* Lisätänn asaiasanalyysi tietokantaan *///
		if ($tee == "LISAAASANALYYSI") {
			if ($esilla != '' || $yleisilme != '') {
				$kysely = "	INSERT INTO kalenteri
							SET tapa = 'asiakasanalyysi',
							asiakas  = '$ytunnus',
							henkilo  = '$yhtunnus',
							kuka     = '$kukarow[kuka]',
							yhtio    = '$kukarow[yhtio]',
							kentta01 = '$esilla',
							kentta02 = '$yleisilme',
							kentta03 = '$puvut',
							kentta04 = '$kyparat',
							kentta05 = '$saappaat',
							kentta06 = '$hanskat',
							kentta07 = '$muuta',
							kentta08 = '$extrat',
							tyyppi   = 'Memo',
							pvmalku  = now()";
				$result = mysql_query($kysely) or pupe_error($kysely);
			}
			$tee = '';
		}

		if ($tee == "POISTAMEMO") {

			$kysely = "	UPDATE kalenteri
						SET
						tyyppi = concat('DELETED ',tyyppi)
						WHERE tunnus='$tunnus'
						and yhtio='$kukarow[yhtio]'
						and asiakas='$ytunnus'";
			$result = mysql_query($kysely) or pupe_error($kysely);

			$tee = '';
		}

		if ($tee == "KORJAAMEMO") {

			// Haetaan viimeisin muistiinpano
			$query = "	SELECT tapa tapa, asiakas ytunnus, henkilo yhtunnus, kuka laatija, kentta01 viesti, pvmalku paivamaara, tunnus
						FROM kalenteri
						WHERE asiakas='$ytunnus' and tyyppi='Memo' and tapa!='asiakasanalyysi' and yhtio='$kukarow[yhtio]'
						ORDER BY tunnus desc
						LIMIT 1";
			$res = mysql_query($query) or pupe_error($query);
			$korjrow = mysql_fetch_array($res);

			$ktapa     = $korjrow["tapa"];
			$kviesti   = $korjrow["viesti"];
			$kyhtunnus = $korjrow["yhtunnus"];
			$ktunnus   = $korjrow["tunnus"];

			$tee = "";
		}


		if ($tee == '') {

			///* Yhteyshenkilön tiedot, otetaan valitun yhteyshenkilön tiedot talteen  *///
			$query = "	SELECT *
						FROM yhteyshenkilo
						WHERE asiakas='$ytunnus'
						ORDER BY nimi";
			$result = mysql_query($query) or pupe_error($query);

			$yhenkilo = "<form action='$PHP_SELF' method='POST'>
						<input type='hidden' name='ytunnus' value='$ytunnus'>
						<select name='yhtunnus' Onchange='submit()'>
						<option value='kaikki'>".t("Yleistiedot")."</option>";



			while ($row = mysql_fetch_array($result)) {

				if($yhtunnus == $row["tunnus"]) {
					$sel      = 'SELECTED';
					$yemail   = $row["email"];
					$ynimi    = $row["nimi"];
					$yfax     = $row["fax"];
					$ygsm     = $row["gsm"];
					$ypuh     = $row["puh"];
					$ywww     = $row["www"];
					$ytitteli = $row["titteli"];
					$yfakta   = $row["fakta"];
				}

				else {
					$sel = '';
				}
				$yhenkilo .= "<option value='$row[tunnus]' $sel>$row[nimi]</option>";
			}
			$yhenkilo .= "</select></form>";

			//Näytetään asiakkaan tietoja jos yhteyshenkilöä ei olla valittu
			if ($yhtunnus == "kaikki" or $yhtunnus == '') {
				$yemail   = $asiakasrow["email"];
				$ynimi    = "";
				$ygsm     = $asiakasrow[""];
				$ypuh     = $asiakasrow["puhelin"];
				$yfax     = $asiakasrow["fax"];
				$ywww     = "";
				$ytitteli = "";
				$yfakta   = $asiakasrow["fakta"];
			}

			///* Asiakaan tiedot ja yhteyshenkilön tiedot *///
			echo "<table>";

			echo "<tr>";
			echo "<th align='left'>".t("Laskutusasiakas:")." </th>";
			echo "<th align='left'>".t("Toimitusasiakas:")." </th>";
			echo "<th align='left'>".t("Muut tiedot:")."</th>";
			echo "<th align='left'>".t("Toiminnot:")."</th>";
			echo "</tr>";


			//asiakkaan toimitusosoite
			if ($asiakasrow['toim_osoite']=='') {
				$asiakasrow['toim_nimi']     = $asiakasrow['nimi'];
				$asiakasrow['toim_nimitark'] = $asiakasrow['nimitark'];
				$asiakasrow['toim_osoite']   = $asiakasrow['osoite'];
				$asiakasrow['toim_postino']  = $asiakasrow['postino'];
				$asiakasrow['toim_postitp']  = $asiakasrow['postitp'];
			}


			echo "<tr>";
			echo "<td>$asiakasrow[nimi]</td><td>$asiakasrow[toim_nimi]</td><td>$yhenkilo</td>";

			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'yllapito.php'
						and alanimi = 'yhteyshenkilo'";
			$result = mysql_query($query) or pupe_error($query);


			if (mysql_num_rows($result) > 0) {
				echo "<td><a href='../yllapito.php?toim=yhteyshenkilo&uusi=1&liitostunnus=$ytunnus&lopetus=$PHP_SELF!!!!ytunnus=$ytunnus'>".t("Luo uusi yhteyshenkilö")."</a></td>";
			}
			else {
				echo "<td>".t("(Luo uusi yhteyshenkilö)")."</td>";
			}

			echo "</tr>";
			echo "<tr>";
			echo "<td>$asiakasrow[nimitark]</td><td>$asiakasrow[toim_nimitark]</td><td>".t("Puh.")." $ypuh</td>";


			if (mysql_num_rows($result) > 0 and $yhtunnus != '') {
				echo "<td><a href='../yllapito.php?toim=yhteyshenkilo&tunnus=$yhtunnus&lopetus=$PHP_SELF!!!!ytunnus=$ytunnus'>".t("Muuta yhteyshenkilön tietoja")."</a></td>";
			}
			else {
				echo "<td></td>";
			}

			echo "</tr>";
			echo "<tr>";
			echo "<td>$asiakasrow[osoite]</td><td>$asiakasrow[toim_osoite]</td><td>".t("Fax.")." $yfax</td>";

			// Päivämäärät rappareita varten
			$kka = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
			$vva = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
			$ppa = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
			$kkl = date("m");
			$vvl = date("Y");
			$ppl = date("d");

			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'raportit/osastoseuranta.php'
						and alanimi = ''";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {
				echo "<td><a href='../raportit/osastoseuranta.php?ytunnus=$ytunnus&from=asiakasmemo.php&tee=go&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl'>".t("Osastoseuranta")."</a></td>";
			}
			else {
				echo "<td>".t("(Osastoseuranta)")."</td>";
			}

			echo "</tr>";
			echo "<tr>";
			echo "<td>$asiakasrow[postino] $asiakasrow[postitp]</td><td>$asiakasrow[toim_postino] $asiakasrow[toim_postitp]</td><td>".t("Gsm.")." $ygsm</td>";


			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'raportit/tuoryseuranta.php'
						and alanimi = ''";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {
				echo "<td><a href='../raportit/tuoryseuranta.php?ytunnus=$ytunnus&from=asiakasmemo.php&tee=go&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl'>".t("Tuoteryhmäseuranta")."</a></td>";
			}
			else {
				echo "<td>".t("(Tuoteryhmäseuranta)")."</td>";
			}

			echo "</tr>";
			echo "<tr>";
			echo "<td>$asiakasrow[fakta]</td><td></td><td>".t("Email.")." $yemail</td>";

			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'raportit/aletaulukko.php'
						and alanimi = ''";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {
				echo "<td><a href='../raportit/aletaulukko.php?ytunnus=$ytunnus&from=asiakasmemo.php'>".t("Näytä alennustaulukko")."</a></td>";
			}
			else {
				echo "<td><u>".t("(Näytä alennustaulukko)")."</u></td>";
			}

			echo "</tr>";

			if ($yfakta != '' or $ytitteli != '' or $ynimi != '') {
				echo "<tr><td colspan='2'>Valittu yhteyshenkilö: $ytitteli $ynimi</td><td colspan='2'>$yfakta</td></tr>";
			}


			echo "</table><br>";

			///* Syötä memo-tietoa *///
			echo "<table width='620'>";

			if ($yhtunnus > 0) {
				echo "<tr><th>".t("Lisää kommentti")."</th><th colspan='2'>".t("Yhteyshenkilö:")." $ynimi</th></tr>";
			}
			else {
				echo "<tr><th colspan='3'>".t("Lisää kommentti")."</th></tr>";
			}


			echo "	<tr>
					<td colspan='3'>
					<form action='$PHP_SELF' method='POST'>
					<input type='hidden' name='tee' value='UUSIMEMO'>
					<input type='hidden' name='korjaus' value='$ktunnus'>
					<input type='hidden' name='kyhtunnus' value='$kyhtunnus'>
					<input type='hidden' name='ytunnus' value='$ytunnus'>
					<input type='hidden' name='yhtunnus' value='$yhtunnus'>
					<textarea cols='83' rows='3' name='viesti' wrap='hard'>$kviesti</textarea></td>
					</tr>
					<tr>
					<td>";


			$query = "	SELECT selite, selitetark
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]' and laji = 'KALETAPA'
						ORDER BY selite";
			$vresult = mysql_query($query) or pupe_error($query);


			echo t("Tapa:")." <select name='tapa'>";


			while ($vrow=mysql_fetch_row($vresult)) {
				$sel="";

				if ($ktapa == $vrow[1]) {
					$sel = "selected";
				}
				echo "<option value = '$vrow[1]' $sel>$vrow[1]";
			}

			echo "</select></td>";

			echo "	<td align='right'>
					<input type='submit' name='submit' value='".t("Tallenna")."'>
					</form>
					</td>";


			echo "	<td align='right'>
					<form action='$PHP_SELF' method='POST'>
					<input type='hidden' name='tee' value='KORJAAMEMO'>
					<input type='hidden' name='yhtunnus' value='$yhtunnus'>
					<input type='hidden' name='ytunnus' value='$ytunnus'>
					<input type='submit' name='submit' value='".t("Korjaa viimeisintä")."'>
					</form>
					</td>";

			echo "</table>";

			///* Haetaan memosta sisalto asiakkaan kohdalta *///
			echo "<table width='620'>";
			echo "<tr><td class='back'><br></td></tr>";


			if ($naytapoistetut == '') {
				$lisadel = " and left(tyyppi,7) != 'DELETED'";
			}
			else {
				$lisadel = "";
			}

			$query = "	SELECT tyyppi, tapa, kalenteri.asiakas ytunnus, yhteyshenkilo.nimi yhteyshenkilo, if(kuka.nimi!='',kuka.nimi, kalenteri.kuka) laatija, kentta01 viesti, left(pvmalku,10) paivamaara,
						kentta02, kentta03, kentta04, kentta05, kentta06, kentta07, kentta08, kalenteri.tunnus
						FROM kalenteri
						LEFT JOIN yhteyshenkilo ON kalenteri.yhtio=yhteyshenkilo.yhtio and kalenteri.henkilo=yhteyshenkilo.tunnus
						LEFT JOIN kuka ON kalenteri.yhtio=kuka.yhtio and kalenteri.kuka=kuka.kuka
						WHERE kalenteri.asiakas='$ytunnus'
						$lisadel
						and kalenteri.yhtio='$kukarow[yhtio]'";

			if($yhtunnus != '') {
				$query .= " and henkilo='$yhtunnus'";
			}

			$query .= "	ORDER by kalenteri.tunnus desc";
			$res = mysql_query($query) or pupe_error($query);


			while ($memorow = mysql_fetch_array($res)) {
				if($memorow["tapa"] == "asiakasanalyysi") {
					echo "<tr>
						<th>$memorow[tyyppi]</th><th>$memorow[laatija]</th><th>$memorow[laatija]@$memorow[paivamaara]
						</th><th>".t("Tapa:")." $memorow[tapa]</th><th>".t("Yhteyshenkilö:")." $memorow[yhteyshenkilo]</th>
						</tr>
						<tr>
						<td colspan='4'><pre>".t("Miten tuotteet esilla:")."<br>$memorow[viesti]<br><br>".t("Myymalan yleisilme:")."<br>$memorow[kentta02]<br><br>".t("Tuotteiden jakauma merkeittain:")."<br>Puvut:<br>$memorow[kentta03]<br>".t("Kyparat:")."<br>$memorow[kentta04]<br>".t("Saappaat:")."<br>$memorow[kentta05]<br>".t("Hanskat:")."<br>$memorow[kentta06]<br>".t("Muut huomiot:")."<br>$memorow[kentta07]<br><br>".t("Keskustelut/sovitut extrat")."<br>$memorow[kentta08]<br></pre></td>
						</tr>";

				}
				else{

					echo "<tr>
							<th>$memorow[tyyppi]</th>
							<th>$memorow[laatija]</th>
							<th>$memorow[paivamaara]</th>
							<th>".t("Tapa:")." $memorow[tapa]</th>
							<th>".t("Yhteyshenkilö:")." $memorow[yhteyshenkilo]</th>";

					if (substr($memorow['tyyppi'],0,7) != 'DELETED') {
						echo "	<th><a href='$PHP_SELF?tunnus=$memorow[tunnus]&ytunnus=$ytunnus&yhtunnus=$yhtunnus&tee=POISTAMEMO'>Poista</a></th>";
					}
					else {
						echo "<th></th>";
					}

					echo "	</tr>
							<tr>
							<td colspan='6'>";

					echo "$memorow[viesti]";

					echo "
							</td>
						</tr>";
				}
			}

			echo "</table>";
			echo "<br>";
			echo "	<th><a href='$PHP_SELF?naytapoistetut=OK&ytunnus=$ytunnus&yhtunnus=$yhtunnus'>Näytä poistetut</a></th>";
		}
   	}

	require ("../inc/footer.inc");

?>