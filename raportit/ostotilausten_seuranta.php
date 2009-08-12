<?php

	if (file_exists("../inc/parametrit.inc")) {
		require ("../inc/parametrit.inc");
	}

	if ($tee == 'NAYTATILAUS') {
		require ("raportit/naytatilaus.inc");
		require ("inc/footer.inc");
		die();
	}

	js_popup();

	echo "<font class='head'>".t("Ostotilausten seuranta")."</font><hr>";

	if ($toimittajahaku != '') {
		$ytunnus = $toimittajahaku;
		$pvm = array('ppa' => $ppa, 'kka' => $kka, 'vva' => $vva, 'ppl' => $ppl, 'kkl' => $kkl, 'vvl' => $vvl, 'toimittaja' => $toimittajahaku);
		$muutparametrit = urlencode(serialize($pvm));
		require('../inc/kevyt_toimittajahaku.inc');
	}

	if ($muutparametrit != '') {
		$pvm = unserialize(urldecode($muutparametrit));
		$ppa = $pvm['ppa'];
		$kka = $pvm['kka'];
		$vva = $pvm['vva'];
		$ppl = $pvm['ppl'];
		$kkl = $pvm['kkl'];
		$vvl = $pvm['vvl'];
		$toimittajahaku = $pvm['toimittaja'];
	}

	if ($toimittajaid == '' and $toimittajahaku != '') {
		$tee = '';
	}

	if ($toimittajaid != '') {
		$toimittajahaku = $ytunnus;
	}

	// Tarvittavat p�iv�m��r�t
	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	echo "<table>";
	echo "<form action='' method='post' autocomplete='off'>";

	echo "<tr><th>",t("Toimittaja"),"</th><td colspan='2' nowrap><input type='text' name='toimittajahaku' value='$toimittajahaku'></td></tr>";

	echo "<tr><th>",t("P�iv�m��r�v�li")," (",t("pp-kk-vvvv"),")</th>";
	
	echo "<td><input type='text' name='ppa' value='$ppa' size='3'>
	<input type='text' name='kka' value='$kka' size='3'>
	<input type='text' name='vva' value='$vva' size='5'></td>
	<td><input type='text' name='ppl' value='$ppl' size='3'>
	<input type='text' name='kkl' value='$kkl' size='3'>
	<input type='text' name='vvl' value='$vvl' size='5'></td>";

	echo "<td class='back'><input type='submit' name='submit' id='submit' value='",t("Luo raportti"),"'></td></tr>";
	echo "<input type='hidden' name='tee' id='tee' value='aja'>";
	echo "</form></table>";

	if (!is_numeric($ppa) or !is_numeric($kka) or !is_numeric($vva) or !is_numeric($ppl) or !is_numeric($kkl) or !is_numeric($vvl)) {
		echo "<br/><font class='error'>",t("Virheellinen p�iv�m��r�"),"!</font>";
		$tee = '';
	}

	if ($tee == 'aja') {

		$ppa = (int) $ppa;
		$kka = (int) $kka;
		$vva = (int) $vva;
		$ppl = (int) $ppl;
		$kkl = (int) $kkl;
		$vvl = (int) $vvl;

		if ($toimittajaid != '') {
			$toimittajaid = mysql_real_escape_string($toimittajaid);
			$toimittajalisa =  " and toimi.tunnus = '$toimittajaid' ";
		}

		$query = "	SELECT nimi, ytunnus, tunnus
					FROM toimi
					WHERE yhtio = '{$kukarow['yhtio']}'
					$toimittajalisa";
		$res = mysql_query($query) or pupe_error($query);

		echo "<table>";

		$vaihtuuko_toimittaja = '';

		while ($toimittajarow = mysql_fetch_assoc($res)) {

			$query = "	SELECT count(*) riveja,
						sum(if(tilausrivi.kpl != 0, 1, 0)) riveja_varastossa, 
						sum(tilausrivi.kpl+tilausrivi.varattu) kpl, 
						sum(tilausrivi.kpl) kpl_varastossa, 
						sum((tilausrivi.kpl+tilausrivi.varattu)*tilausrivi.hinta*(1-tilausrivi.ale/100)) arvo, 
						lasku.tunnus ltunnus, 
						lasku.lahetepvm,
						sum(tuote.tuotemassa*(tilausrivi.varattu+tilausrivi.kpl)) massa,
						sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok
						FROM lasku 
						JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi = 'O')
						JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno)
						WHERE lasku.yhtio = '{$kukarow['yhtio']}'
						AND lasku.tila = 'O'
						AND lasku.liitostunnus = '{$toimittajarow['tunnus']}'
						and lasku.lahetepvm >='$vva-$kka-$ppa 00:00:00'
						and lasku.lahetepvm <='$vvl-$kkl-$ppl 23:59:59'
						GROUP BY lasku.tunnus
						ORDER BY lasku.ytunnus, lasku.lahetepvm";
			$tilrivi_res = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tilrivi_res) > 0) {

				$ed_tunn = '';

				$yht_paino = 0;
				$yht_kpl = 0;
				$yht_varastossa_kpl = 0;
				$yht_rivit = 0;
				$yht_varastossa_rivit = 0;
				$yht_arvo = 0;
				$yht_tavara_summa = 0;
				$yht_kulu_summa = 0;
				$yht_eturahti = 0;

				while ($tilrivi_row = mysql_fetch_assoc($tilrivi_res)) {

					// keikka
					$query = "	SELECT distinct laskunro, rahti_etu, lasku.tunnus, lasku.mapvm
								FROM lasku
								JOIN tilausrivi USE INDEX (uusiotunnus_index) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus and tilausrivi.tyyppi = 'O' and tilausrivi.otunnus = '$tilrivi_row[ltunnus]')
								WHERE lasku.yhtio = '$kukarow[yhtio]' and
								lasku.tila = 'K' and
								lasku.vanhatunnus = 0 and
								lasku.liitostunnus = '$toimittajarow[tunnus]'
								ORDER BY laskunro";
					$result = mysql_query($query) or pupe_error($query);

					$i = 1;
					$x = mysql_num_rows($result);
					
					if ($vaihtuuko_toimittaja != $toimittajarow['tunnus']) {
						echo "<tr>";
						echo "<td class='back' colspan='17' style='vertical-align: top;' nowrap><br/><font class='head'>{$toimittajarow['ytunnus']} {$toimittajarow['nimi']}</font><br/></td>";
						echo "</tr>";

						echo "<tr>";
						echo "<th>",t("Tilno"),"</th>";
						echo "<th>",t("Tilvko"),"</th>";
						echo "<th>",t("Tilpvm"),"</th>";
						echo "<th>",t("Paino"),"</th>";
						echo "<th>",t("M��r�"),"</th>";
						echo "<th>",t("Rivim��r�"),"</th>";
						echo "<th>",t("Tilauksen"),"<br/>",t("arvo"),"<br/>$yhtiorow[valkoodi]</th>";
						echo "<th>",t("Keikka"),"</th>";
						echo "<th>",t("Tavaralaskun"),"<br/>",t("luontiaika"),"</th>";
						echo "<th>",t("Summa"),"<br/>$yhtiorow[valkoodi]</th>";
						echo "<th>",t("Viesti"),"</th>";
						echo "<th>",t("Kululaskun"),"<br/>",t("luontiaika"),"</th>";
						echo "<th>",t("Summa"),"<br/>$yhtiorow[valkoodi]</th>";
						echo "<th>",t("Viesti"),"</th>";
						echo "<th>",t("Eturahti"),"<br/>$yhtiorow[valkoodi]</th>";
						echo "<th>",t("Saldopvm"),"</th>";
						echo "<th>",t("Valmispvm"),"</th>";
						echo "<th>&nbsp;</th>";
						echo "</tr>";
					}

					echo "<tr class='aktiivi'>";

					echo "<td rowspan='$x' style='vertical-align: top;'>";

					$varastossa_riveja = '';
					$varastossa_kpl = '';

					if ($ed_tunn != $tilrivi_row['ltunnus']) {

						while ($keikkarow = mysql_fetch_assoc($result)) {
							if ($keikkarow['mapvm'] == '0000-00-00') {
								$varastossa_riveja = $tilrivi_row['riveja_varastossa']."/";
								$varastossa_kpl = (float) $tilrivi_row['kpl_varastossa']."/";
							}
						}

						echo "<a href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=OSTO&tunnus=$tilrivi_row[ltunnus]'>$tilrivi_row[ltunnus]</a>";
					}
					else {
						echo "&nbsp;";
					}
					echo "</td>";

					echo "<td style='vertical-align: top;' rowspan='$x'>".date("W", strtotime($tilrivi_row['lahetepvm']))."/".substr($tilrivi_row['lahetepvm'], 2, 2)."</td>";				
					echo "<td style='vertical-align: top;' rowspan='$x'>".tv1dateconv($tilrivi_row['lahetepvm'])."</td>";

					$osumapros = '';
					
					if (round($tilrivi_row["kplok"] / $tilrivi_row["kpl"] * 100, 2) != 100) {
						$osumapros = "~";
					}

					echo "<td style='vertical-align: top; text-align: right;' rowspan='$x'>";
					if ($tilrivi_row['massa'] != 0) {
						echo "$osumapros".sprintf('%.02f', $tilrivi_row['massa']);
						$yht_paino += $tilrivi_row['massa'];
					}
					echo "</td>";
					
					echo "<td style='vertical-align: top; text-align: right;' rowspan='$x' nowrap>$varastossa_kpl".(float) $tilrivi_row['kpl']."</td>";
					echo "<td style='vertical-align: top; text-align: right;' rowspan='$x' nowrap>$varastossa_riveja$tilrivi_row[riveja]</td>";
					echo "<td style='vertical-align: top; text-align: right;' nowrap rowspan='$x'>".sprintf('%.02f', $tilrivi_row['arvo'])."</td>";

					$yht_varastossa_kpl += substr($varastossa_kpl, 0, -1);
					$yht_kpl += $tilrivi_row['kpl'];
					$yht_varastossa_rivit += substr($varastossa_riveja, 0, -1);
					$yht_rivit += $tilrivi_row['riveja'];
					$yht_arvo += $tilrivi_row['arvo'];

					if (mysql_num_rows($result) > 0) {
						mysql_data_seek($result, 0);
					}

					while ($keikkarow = mysql_fetch_assoc($result)) {

						echo "<td style='vertical-align: top;'><a href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=OSTO&tunnus=$keikkarow[tunnus]'>$keikkarow[laskunro]</a></td>";

						$query  = "	SELECT ostoreskontran_lasku.summa * if(ostoreskontran_lasku.maksu_kurssi <> 0, ostoreskontran_lasku.maksu_kurssi, ostoreskontran_lasku.vienti_kurssi) summa_euroissa,
									ostoreskontran_lasku.luontiaika,
									concat(ostoreskontran_lasku.asiakkaan_tilausnumero, ' ', ostoreskontran_lasku.viesti) numero,
									ostoreskontran_lasku.tunnus
									FROM lasku liitosotsikko 
									JOIN lasku ostoreskontran_lasku ON (ostoreskontran_lasku.yhtio=liitosotsikko.yhtio and ostoreskontran_lasku.tunnus=liitosotsikko.vanhatunnus)
									WHERE liitosotsikko.yhtio		= '$kukarow[yhtio]'
									AND liitosotsikko.laskunro = '$keikkarow[laskunro]'
									AND liitosotsikko.vanhatunnus <> 0
									AND liitosotsikko.tila = 'K'
									AND ostoreskontran_lasku.vienti in ('C', 'J', 'F', 'K', 'I', 'L')"; // tavaralaskut
						$ostolaskures = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($ostolaskures) > 0) {
							echo "<td style='vertical-align: top;' nowrap>";
							while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
								echo tv1dateconv($ostolaskurow['luontiaika'])."<br>";
							}
							echo "</td>";

							mysql_data_seek($ostolaskures, 0);

							echo "<td style='vertical-align: top; text-align: right;' nowrap>";
							while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
								echo sprintf('%.02f', $ostolaskurow['summa_euroissa'])."<br>";
								$yht_tavara_summa += $ostolaskurow['summa_euroissa'];
							}
							echo "</td>";

							mysql_data_seek($ostolaskures, 0);

							echo "<td style='vertical-align: top;' nowrap>";
							while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
								if (trim($ostolaskurow['numero']) != '') {
									echo "<div id='$ostolaskurow[tunnus]' class='popup'>";
									echo $ostolaskurow['numero'];
									echo "</div>";
									echo " <a onmouseout=\"popUp(event,'$ostolaskurow[tunnus]')\" onmouseover=\"popUp(event,'$ostolaskurow[tunnus]')\"><img src='$palvelin2/pics/lullacons/info.png'></a>";
								}
								echo "<br>";								
							}
							echo "</td>";
						}
						else {
							echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
						}


						$query  = "	SELECT ostoreskontran_lasku.summa * if(ostoreskontran_lasku.maksu_kurssi <> 0, ostoreskontran_lasku.maksu_kurssi, ostoreskontran_lasku.vienti_kurssi) summa_euroissa,
									ostoreskontran_lasku.luontiaika,
									concat(ostoreskontran_lasku.asiakkaan_tilausnumero, ' ', ostoreskontran_lasku.viesti) numero,
									ostoreskontran_lasku.tunnus
									FROM lasku liitosotsikko 
									JOIN lasku ostoreskontran_lasku ON (ostoreskontran_lasku.yhtio=liitosotsikko.yhtio and ostoreskontran_lasku.tunnus=liitosotsikko.vanhatunnus)
									WHERE liitosotsikko.yhtio		= '$kukarow[yhtio]'
									AND liitosotsikko.laskunro = '$keikkarow[laskunro]'
									AND liitosotsikko.vanhatunnus <> 0
									AND liitosotsikko.tila = 'K'
									AND ostoreskontran_lasku.vienti in ('B', 'E', 'H')"; // rahtilaskut
						$ostolaskures = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($ostolaskures) > 0) {

							echo "<td style='vertical-align: top; text-align: right;' nowrap>";
							while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
								echo tv1dateconv($ostolaskurow['luontiaika'])."<br>";
							}
							echo "</td>";

							mysql_data_seek($ostolaskures, 0);

							echo "<td style='vertical-align: top; text-align: right;' nowrap>";
							while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
								echo sprintf('%.02f', $ostolaskurow['summa_euroissa'])."<br>";
								$yht_kulu_summa += $ostolaskurow['summa_euroissa'];
							}
							echo "</td>";

							mysql_data_seek($ostolaskures, 0);

							echo "<td style='vertical-align: top;' nowrap>";

							while ($ostolaskurow = mysql_fetch_assoc($ostolaskures)) {
								if (trim($ostolaskurow['numero']) != '') {
									echo "<div id='$ostolaskurow[tunnus]' class='popup'>";
									echo $ostolaskurow['numero'];
									echo "</div>";
									echo " <a onmouseout=\"popUp(event,'$ostolaskurow[tunnus]')\" onmouseover=\"popUp(event,'$ostolaskurow[tunnus]')\"><img src='$palvelin2/pics/lullacons/info.png'></a>";
								}
								echo "<br>";
							}
							echo "</td>";
						}
						else {
							echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
						}

						if ($keikkarow['rahti_etu'] == 0) {
							$keikkarow['rahti_etu'] = '';
						}
						else {
							$yht_eturahti += $keikkarow['rahti_etu'];
						}

						echo "<td style='vertical-align: top; text-align: right;' nowrap>$keikkarow[rahti_etu]</td>";
						
						$query = "	SELECT group_concat(DISTINCT laskutettuaika separator '<br/>') laskettuaika
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]'
									AND otunnus = $tilrivi_row[ltunnus]
									AND uusiotunnus = $keikkarow[tunnus]
									AND tyyppi = 'O'";
						$saldoille_res = mysql_query($query) or pupe_error($query);						
						$saldoille_row = mysql_fetch_assoc($saldoille_res);

						echo "<td style='vertical-align: top;' nowrap>".tv1dateconv($saldoille_row['laskettuaika'])."</td>";
						echo "<td style='vertical-align: top;' nowrap>".tv1dateconv($keikkarow['mapvm'])."</td>";

						echo "<td style='vertical-align: top;'>";
						if ($keikkarow['mapvm'] == '0000-00-00' and $saldoille_row['laskettuaika'] == '0000-00-00') {
							echo "<img src='".$palvelin2."pics/lullacons/bot-plain-red.png'/>";
						}
						elseif ($keikkarow['mapvm'] == '0000-00-00') {
							echo "<img src='".$palvelin2."pics/lullacons/bot-plain-yellow.png'/>";
						}
						else {
							echo "<img src='".$palvelin2."pics/lullacons/bot-plain-green.png'/>";
						}
						echo "</td>";
						
						if ($i < $x and mysql_num_rows($result) > 1) {
							echo "</tr><tr class='aktiivi'>";
						}
						$i++;
						
					}

					if (mysql_num_rows($result) == 0) {
						echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td><img src='".$palvelin2."pics/lullacons/bot-plain-white.png'/></td>";
					}

					echo "</tr><tr class='aktiivi'>";

					$ed_tunn = $tilrivi_row['ltunnus'];
					$vaihtuuko_toimittaja = $toimittajarow['tunnus'];

				}

				$yht_varastossa_kpl = $yht_varastossa_kpl != 0 ? (float) $yht_varastossa_kpl.'/' : '';
				$yht_varastossa_rivit = $yht_varastossa_rivit != 0 ? (float) $yht_varastossa_rivit.'/' : '';
				$yht_paino = $yht_paino != 0 ? $yht_paino : '';
				$yht_arvo = $yht_arvo != 0 ? sprintf('%.02f', $yht_arvo) : '';
				$yht_tavara_summa = $yht_tavara_summa != 0 ? sprintf('%.02f', $yht_tavara_summa) : '';
				$yht_kulu_summa = $yht_kulu_summa != 0 ? sprintf('%.02f', $yht_kulu_summa) : '';
				$yht_eturahti = $yht_eturahti != 0 ? sprintf('%.02f', $yht_eturahti) : '';

				echo "<tr>";
				echo "<td class='spec'>",t("Yhteens�"),"</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "<td class='spec' style='text-align:right;'>$yht_paino</td>";
				echo "<td class='spec' style='text-align:right;'>$yht_varastossa_kpl",(float) $yht_kpl,"</td>";
				echo "<td class='spec' style='text-align:right;'>$yht_varastossa_rivit",(float) $yht_rivit,"</td>";
				echo "<td class='spec' style='text-align:right;'>$yht_arvo</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "<td class='spec' style='text-align: right;'>$yht_tavara_summa</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "<td class='spec' style='text-align: right;'>$yht_kulu_summa</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "<td class='spec' style='text-align: right;'>$yht_eturahti</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "<td class='spec'>&nbsp;</td>";
				echo "</tr>";

			}

		}
		echo "</table>";
	}

	if (file_exists("../inc/footer.inc")) {
		require ("../inc/footer.inc");
	}

?>