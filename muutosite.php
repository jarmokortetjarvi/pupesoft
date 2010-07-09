<?php

if ($_REQUEST["tee"] == 'NAYTATILAUS' or $_POST["tee"] == 'NAYTATILAUS' or $_GET["tee"] == 'NAYTATILAUS') $nayta_pdf = 1; //Generoidaan .pdf-file

require ("inc/parametrit.inc");

if ($tee_pdf == 'tulosta_karhu') {
	require ('myyntires/paperikarhu.php');
	exit;
}

if ($tee_pdf == 'tulosta_tratta') {
	require ('myyntires/paperitratta.php');
	exit;
}

if ($tee == 'tulosta_korkoerittely') {
	$apuqu = "	SELECT *
				from lasku
				where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$res = mysql_query($apuqu) or pupe_error($apuqu);
	if (mysql_num_rows($res) == 1) {
		$trow = mysql_fetch_array($res);
		require ('myyntires/tulosta_korkoerittely.inc');
	}
	exit;
}

enable_ajax();

if ($livesearch_tee == "TILIHAKU") {
	livesearch_tilihaku();
	exit;
}

// ekotetaan javascripti� jotta saadaan pdf:�t uuteen ikkunaan
js_openFormInNewWindow();

echo "<font class='head'>".t("Tili�intien muutos/selailu")."</font><hr>";

if (($tee == 'U' or $tee == 'P' or $tee == 'M' or $tee == 'J') and ($oikeurow['paivitys'] != 1)) {
	echo "<font class='error'>".t("Yritit p�ivitt�� vaikka sinulla ei ole siihen oikeuksia")."</font>";
	exit;
}

// Jaksotus
if ($tee == 'J') {
	require "inc/jaksota.inc";
}

// jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
if ($tee == 'U' and $summa != '' and abs($summa) > 0) {
	if (abs($summa) > 9999999999.99) {
		$tee = 'E';
		$tila = '';
		$virhe = t("VIRHE: liian iso summa")."!";
		$ok = 1;
	}
}

// Otsikon muutokseen
if ($tee == 'M') {
	require "inc/muutosite.inc";
}

// Seuraava "tosite"
if ($tee == 'G') {
	$query = "SELECT tapvm, tunnus
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]'
				and tunnus > '$tunnus'
				and tila in ('H','Y','M','P','Q','X','U')
				ORDER by tunnus
				LIMIT 1";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {
			$trow=mysql_fetch_array ($result);
			$tunnus=$trow['tunnus'];
			$tee = 'E';
	}
	else {
		echo "<font class='error'>".t("Ei seuraavaa tositetta")."</font><br>";
		$tee = 'E';
	}
}

// Tositeselailu
if ($tee == 'Y' or $tee == 'Z' or $tee == 'X' or $tee == 'W' or $tee == 'T' or $tee == 'S' or $tee == '�' or $tee == '�') {

	if  ($tee == 'Z' or $tee == 'X' or $tee == 'W' or $tee == 'T' or $tee == 'S' or $tee == '�' or $tee == '�') {

		// Etsit��n virheet vain kuluvalta tilikaudelta!
		if ($tee == 'Z') {
			$query = "	SELECT ltunnus, tapvm, round(sum(summa),2) summa, 'n/a', 'n/a', 'n/a', selite
						FROM tiliointi use index (yhtio_tapvm_tilino)
						WHERE yhtio = '$kukarow[yhtio]'
						and korjattu=''
						and tapvm >= '$yhtiorow[tilikausi_alku]'
						and tapvm <= '$yhtiorow[tilikausi_loppu]'
						GROUP BY ltunnus, tapvm
						HAVING summa <> 0";
		}

		if ($tee == 'X') {
			$query = "	SELECT ltunnus, tapvm, summa, 'n/a', 'n/a', 'n/a', selite
						FROM tiliointi use index (yhtio_tilino_tapvm), tili use index (tili_index)
						WHERE tiliointi.yhtio = '$kukarow[yhtio]'
						and tili.yhtio = '$kukarow[yhtio]'
						and tiliointi.tilino = tili.tilino
						and korjattu=''
						and tapvm >= '$yhtiorow[tilikausi_alku]'
						and tapvm <= '$yhtiorow[tilikausi_loppu]'
						and sisainen_taso like '3%'
						and kustp = 0
						and tiliointi.tilino!='$yhtiorow[myynti]'
						and tiliointi.tilino!='$yhtiorow[myynti_ei_eu]'
						and tiliointi.tilino!='$yhtiorow[myynti_eu]'
						and tiliointi.tilino!='$yhtiorow[varastonmuutos]'
						and tiliointi.tilino!='$yhtiorow[pyoristys]'";
		}

		if ($tee == 'W') {
			$query = "	SELECT ltunnus, count(*) maara, round(sum(summa),2) heitto, 'n/a', 'n/a', 'n/a', selite
						FROM tiliointi use index (yhtio_tilino_tapvm)
						WHERE yhtio='$kukarow[yhtio]'
						and korjattu=''
						and tapvm >= '$yhtiorow[tilikausi_alku]'
						and tapvm <= '$yhtiorow[tilikausi_loppu]'
						and tilino = '$yhtiorow[ostovelat]'
						GROUP BY ltunnus
						HAVING maara > 1 and heitto <> 0";
		}

		if ($tee == 'T') {
			$query = "	SELECT ltunnus, count(*) maara, tila, 'n/a', 'n/a', 'n/a', selite
						FROM tiliointi use index (yhtio_tilino_tapvm)
						LEFT JOIN lasku ON  lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus
						WHERE tiliointi.yhtio='$kukarow[yhtio]'
						and korjattu=''
						and tiliointi.tapvm >= '$yhtiorow[tilikausi_alku]'
						and tiliointi.tapvm <= '$yhtiorow[tilikausi_loppu]'
						and tilino = '$yhtiorow[ostovelat]'
						and tila < 'R'
						GROUP BY ltunnus
						HAVING maara > 1";
		}

		if ($tee == 'S') {
			$query = "	SELECT lasku.tunnus, lasku.laskunro, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm,
						if(sum(ifnull(t1.summa, 0))=0,0,1)+if(sum(ifnull(t2.summa, 0))=0,0,1)+if(sum(ifnull(t3.summa, 0))=0,0,1) korjattu,
						count(distinct t1.tilino)+count(distinct t2.tilino)+count(distinct t3.tilino) saamistilej�
						FROM lasku
						LEFT JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu = '' and t1.tilino='$yhtiorow[myyntisaamiset]'
						LEFT JOIN tiliointi t2 ON lasku.yhtio=t2.yhtio and lasku.tunnus=t2.ltunnus and t2.korjattu = '' and t2.tilino='$yhtiorow[factoringsaamiset]'
						LEFT JOIN tiliointi t3 ON lasku.yhtio=t3.yhtio and lasku.tunnus=t3.ltunnus and t3.korjattu = '' and t3.tilino='$yhtiorow[konsernimyyntisaamiset]'
						WHERE lasku.yhtio	= '$kukarow[yhtio]'
						and lasku.tila		= 'U'
						and lasku.alatila	= 'X'
						and lasku.tapvm >= '$yhtiorow[tilikausi_alku]'
						and lasku.tapvm <= '$yhtiorow[tilikausi_loppu]'
						GROUP BY 1,2,3,4,5,6
						HAVING saamistilej� > 1 and korjattu > 0";
		}

		if ($tee == '�') {
			$query = "	(SELECT distinct lasku.tunnus, lasku.laskunro, lasku.nimi, lasku.tapvm, tr1.tuoteno, s1.sarjanumero, if(tr1.alv>=500, 'MV', tr1.alv) alv1, if(tr2.alv>=500, 'MV', tr2.alv) alv2, l2.laskunro, l2.nimi
						FROM lasku
						JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu = '' and t1.tilino='$yhtiorow[osto_marginaali]'
						JOIN tilausrivi tr1 ON lasku.yhtio=tr1.yhtio and lasku.tunnus=tr1.uusiotunnus and tr1.alv>=500 and tr1.kpl<0
						JOIN sarjanumeroseuranta s1 ON tr1.yhtio=s1.yhtio and tr1.tunnus=s1.ostorivitunnus
						JOIN tilausrivi tr2 ON s1.yhtio=tr2.yhtio and s1.myyntirivitunnus=tr2.tunnus
						JOIN lasku l2 ON tr2.yhtio=l2.yhtio and tr2.uusiotunnus=l2.tunnus
						WHERE lasku.yhtio	= '$kukarow[yhtio]'
						and lasku.tila		= 'U'
						and lasku.alatila	= 'X'
						and lasku.tapvm >= '$yhtiorow[tilikausi_alku]'
						and lasku.tapvm <= '$yhtiorow[tilikausi_loppu]'
						HAVING (alv1 != 'MV' or alv2 != 'MV')
						ORDER by lasku.laskunro)

						UNION DISTINCT

						(SELECT distinct l2.tunnus, l2.laskunro, l2.nimi, l2.tapvm, tr1.tuoteno, s1.sarjanumero, if(tr2.alv>=500, 'MV', tr2.alv) alv2, if(tr1.alv>=500, 'MV', tr1.alv) alv1, lasku.laskunro, lasku.nimi
						FROM lasku
						JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu = '' and t1.tilino='$yhtiorow[myynti_marginaali]'
						JOIN tilausrivi tr1 ON lasku.yhtio=tr1.yhtio and lasku.tunnus=tr1.uusiotunnus and tr1.alv>=500 and tr1.kpl>0
						JOIN sarjanumeroseuranta s1 ON tr1.yhtio=s1.yhtio and tr1.tunnus=s1.myyntirivitunnus
						JOIN tilausrivi tr2 ON s1.yhtio=tr2.yhtio and s1.ostorivitunnus=tr2.tunnus
						JOIN lasku l2 ON tr2.yhtio=l2.yhtio and tr2.uusiotunnus=l2.tunnus
						WHERE lasku.yhtio	= '$kukarow[yhtio]'
						and lasku.tila		= 'U'
						and lasku.alatila	= 'X'
						and lasku.tapvm >= '$yhtiorow[tilikausi_alku]'
						and lasku.tapvm <= '$yhtiorow[tilikausi_loppu]'
						HAVING (alv1 != 'MV' or alv2 != 'MV')
						ORDER by l2.laskunro)";
		}

		if ($tee == '�') {
			$query = "	SELECT lasku.tunnus ltunnus, lasku.ytunnus, lasku.nimi, lasku.tapvm, lasku.summa, ifnull(sum(t1.summa),0) + ifnull(sum(t2.summa),0) + ifnull(sum(t3.summa),0) ero
						FROM lasku
						LEFT JOIN tiliointi t1 ON (lasku.yhtio = t1.yhtio and lasku.tunnus = t1.ltunnus and t1.korjattu = '' and t1.tilino = '$yhtiorow[myyntisaamiset]')
						LEFT JOIN tiliointi t2 ON (lasku.yhtio = t2.yhtio and lasku.tunnus = t2.ltunnus and t2.korjattu = '' and t2.tilino = '$yhtiorow[factoringsaamiset]')
						LEFT JOIN tiliointi t3 ON (lasku.yhtio = t3.yhtio and lasku.tunnus = t3.ltunnus and t3.korjattu = '' and t3.tilino = '$yhtiorow[konsernimyyntisaamiset]')
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila	  = 'U'
						and lasku.alatila = 'X'
						and lasku.mapvm  != '0000-00-00'
						and lasku.tapvm  >= '$yhtiorow[tilikausi_alku]'
						and lasku.tapvm  <= '$yhtiorow[tilikausi_loppu]'
						GROUP BY ltunnus
						HAVING round(sum(t1.summa),2) != 0 or round(sum(t2.summa),2) != 0 or round(sum(t3.summa),2) != 0
						ORDER by lasku.laskunro";
		}

	}
	else {

		$plisa = "";
		$lisa  = "";
		$vlisa = "";
		$summa = str_replace ( ",", ".", $summa);

		$tav += 0; // Tehd��n pvmst� numeroita
		$tak += 0;
		$tap += 0;

		if ($tav > 0 and $tav < 1000) {
			$tav += 2000;
		}

		if ($tav != 0 and $tak != 0 and $tap != 0) {
			$plisa = " and tapvm = '$tav-$tak-$tap' ";
		}
		elseif ($tav != 0 and $tak != 0) {
			$plisa = " and tapvm >= '$tav-$tak-01' and tapvm < '".date("Y-m-d",mktime(0, 0, 0, $tak+1, 1, $tav))."' ";
		}
		elseif ($tav != 0) {
			$plisa = " and tapvm >= '$tav-01-01' and tapvm < '".date("Y-m-d",mktime(0, 0, 0, 1, 1, $tav+1))."' ";
		}
		else {
			$plisa = " and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]' ";
		}

		if (strlen($selite) > 0) {
			$lisa = " and selite";
			if ($ohita == 'on') {
				$lisa .= " not ";
			}
			$lisa .= " like '%" . $selite . "%'";
		}

		if (strlen($tilino) > 0) {
			$lisa .= " and tiliointi.tilino = '" . $tilino . "'";
		}

		if (strlen($summa) > 0) {
			$summa = abs($summa); // tehd��n siit� positiivinen numero
			$lisa .= " and abs(tiliointi.summa) = $summa";
		}

		if (strlen($laatija) > 0) {
			$lisa .= " and tiliointi.laatija = '" . $laatija . "'";
		}

		if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
			if (strlen($tositenro) > 0) {
				list($tositenro1, $tositenro2) = explode("-",$tositenro);
				$tositenro1 = (int) $tositenro1;
				$tositenro2 = (int) $tositenro2;
				$tositenro = sprintf ('%02d', $tositenro1) . sprintf ('%06d', $tositenro2);
				$lisa .= " and tiliointi.tosite = '$tositenro' ";
			}
		}
		$summa = "";

		if ($viivatut != 'on') {
			$vlisa = "and tiliointi.korjattu=''";
		}
		else {
			$slisa = ", concat_ws('@', korjattu, korjausaika) korjaus";
		}

		$query = "	SELECT tiliointi.ltunnus, tiliointi.tapvm, tiliointi.summa, tili.tilino,
					tili.nimi, vero, selite $slisa
					FROM tiliointi use index (yhtio_tapvm_tilino), tili
					WHERE tiliointi.yhtio = '$kukarow[yhtio]' and
					tili.yhtio = tiliointi.yhtio and tili.tilino = tiliointi.tilino
					$plisa
					$vlisa
					$lisa
					ORDER BY tiliointi.ltunnus desc, tiliointi.tunnus";
	}

	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Haulla ei l�ytynyt yht��n tositetta")."</font>";
		$tyhja = 1;
	}
	else {
		echo "<table><tr>";
		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th>".t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";
		echo "<tr>";

		while ($trow = mysql_fetch_array ($result)) {
			// Ei anneta t�m�n h�m�t� meit�!
			if ($trow[7] == '@0000-00-00 00:00:00') $trow[7] = '';
			if ($trow[7] == '0000-00-00 00:00:00') $trow[7] = '';

			//Laitetaan linkki tuonne pvm:��n, n�in voimme avata tositteita tab:eihin
			if (strlen($edtunnus) > 0) {

				// Tosite vaihtui
				if ($trow[0] != $edtunnus) {
					echo "<tr><th height='10' colspan='".mysql_num_fields($result)."'></th></tr><tr>";
				}
				else {
					echo "</tr><tr>";
				}
			}

			$edtunnus = $trow[0];

			for ($i=1; $i < mysql_num_fields($result); $i++) {
				if ($i == 1) {
					if (mysql_field_name($result,$i) == 'tapvm') {
						$trow[$i] = tv1dateconv($trow[$i]);
					}
					echo "<td><a href = '$PHP_SELF?tee=E&tunnus=$edtunnus&viivatut=$viivatut'>$trow[$i]</td>";
				}
				elseif (is_numeric($trow[$i]) and (mysql_field_type($result,$i) == 'real' or mysql_field_type($result,$i) == 'int')) {
					echo "<td align='right'>$trow[$i]</td>";
				}
				elseif (mysql_field_name($result, $i) == "tapvm") {
					echo "<td>".tv1dateconv($trow[$i])."</td>";
				}
				else {
					echo "<td>$trow[$i]</td>";
				}
			}
		}
	}
	echo "</tr></table><br><br>";
	$tee = "";
}

// Olemassaolevaa tili�inti� muutetaan, joten yliviivataan rivi ja annetaan perustettavaksi
if ($tee == 'P') {

	$query = "	SELECT tilino, kustp, kohde, projekti, summa, vero, selite, tapvm, tosite, summa_valuutassa, valkoodi
				FROM tiliointi
				WHERE tunnus = '$ptunnus'
				AND yhtio = '$kukarow[yhtio]'
				AND tapvm >= '$yhtiorow[tilikausi_alku]'
				AND tapvm <= '$yhtiorow[tilikausi_loppu]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo t("Tili�inti� ei l�ydy tai se on lukitulla tilikaudella! Systeemivirhe!");
		exit;
	}

	$tiliointirow = mysql_fetch_array($result);

	$tili				= $tiliointirow['tilino'];
	$kustp				= $tiliointirow['kustp'];
	$kohde				= $tiliointirow['kohde'];
	$projekti			= $tiliointirow['projekti'];
	$summa				= $tiliointirow['summa'];
	$summa_valuutassa	= $tiliointirow['summa_valuutassa'];
	$valkoodi			= $tiliointirow["valkoodi"];
	$vero				= $tiliointirow['vero'];
	$selite				= $tiliointirow['selite'];
	$tiliointipvm		= $tiliointirow['tapvm'];
	$tositenro			= $tiliointirow['tosite'];
	$ok					= 1;
	$alv_tili			= $yhtiorow["alv"];

	// Katotaan voisiko meill� olla t�ss� joku toinen ALV tili
	// tutkitaan ollaanko jossain toimipaikassa alv-rekister�ity ja oteteaan niiden alv tilit
	$query = "	SELECT alv_tili FROM lasku WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$tunnus' AND alv_tili != ''";
	$alv_tili_res = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($alv_tili_res) == 1) {
		$alv_tili_row = mysql_fetch_array($alv_tili_res);
		$alv_tili = $alv_tili_row['alv_tili'];
	}

	// Etsit��n kaikki tili�intirivit, jotka kuuluvat t�h�n tili�intiin ja lasketaan niiden summa
	$query = "	SELECT sum(summa)
				FROM tiliointi
				WHERE aputunnus = '$ptunnus'
				AND yhtio = '$kukarow[yhtio]'
				AND tiliointi.korjattu = ''
				GROUP BY aputunnus";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 0) {
		$summarow = mysql_fetch_array($result);
		$summa += $summarow[0];
		$query = "	UPDATE tiliointi SET
					korjattu = '$kukarow[kuka]',
					korjausaika = now()
					WHERE aputunnus = '$ptunnus'
					and yhtio = '$kukarow[yhtio]'
					and tiliointi.korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);
	}

	$query = "	UPDATE tiliointi SET
				korjattu = '$kukarow[kuka]',
				korjausaika = now()
				WHERE tunnus = '$ptunnus'
				AND yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	$tee = 'E'; // N�ytet��n milt� tosite nyt n�ytt��
}

// Lis�t��n tili�intirivi
if ($tee == 'U') {
	$summa = str_replace ( ",", ".", $summa);
	$selausnimi = 'tili'; // Minka niminen mahdollinen popup on?

	require "inc/tarkistatiliointi.inc";

	$tiliulos = $ulos;
	$ulos = '';

	$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1) {
		echo t("Laskua ei en�� l�ydy! Systeemivirhe!");
		exit;
	}
	else {
		$laskurow = mysql_fetch_array($result);
	}

	// Katotaan voisiko meill� olla t�ss� joku toinen ALV tili
	// tutkitaan ollaanko jossain toimipaikassa alv-rekister�ity ja oteteaan niiden alv tilit
	if ($laskurow['alv_tili'] != '') {
		$alv_tili = $laskurow['alv_tili'];
	}
	else {
		$alv_tili = $yhtiorow["alv"];
	}

	// Tarvitaan kenties tositenro
	if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {

		if ($tositenro != 0) {
			$query = "	SELECT tosite FROM tiliointi
						WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and tosite='$tositenro'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
				$tositenro = 0;
			}
		}
		else {
			//T�ll� ei viel� ole tositenroa. Yritet��n jotain
			switch ($laskurow['tila']) {
				case "X" : // T�m� on muistiotosite, sill� voi olla vain yksi tositenro
					$query = "SELECT distinct tosite FROM tiliointi
								WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus'";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) != 1) {
						echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
						$tositenro=0;
					}
					else {
						$tositerow=mysql_fetch_array ($result);
						$tositenro = $tositerow['tosite'];
					}
					break;

				case 'U' : //T�m� on myyntilasku
					$query = "SELECT tosite FROM tiliointi
									WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus'";
					$result = mysql_query($query) or pupe_error($query);
					if (mysql_num_rows($result) != 0) {

						// T�lle saamme tositenron myyntisaamisista
						if ($laskurow['tapvm'] == $tiliointipvm) {
							$query = "	SELECT tosite FROM tiliointi
										WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
										tapvm='$tiliointipvm' and tilino = '$yhtiorow[myyntisaamiset]' and
										summa = $laskurow[summa]";
							$result = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($result) == 0) {
								echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
								$tositenro=0;
							}
							else {
								$tositerow=mysql_fetch_array ($result);
								$tositenro = $tositerow['tosite'];
							}
						}
						else {

							// T�lle saamme tositenron jostain samanlaisesta viennist�
							if ($laskurow['tapvm'] != $tiliointipvm) {
								$query = "	SELECT tosite FROM tiliointi
											WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
											tapvm='$tiliointipvm' and tilino != '$yhtiorow[myyntisaamiset]' and
											summa != $laskurow[summa]";
								$result = mysql_query($query) or pupe_error($query);
								if (mysql_num_rows($result) == 0) {
									echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
									$tositenro=0;
								}
								else {
									$tositerow=mysql_fetch_array ($result);
									$tositenro = $tositerow['tosite'];
								}
							}
						}
					}
					else {
						echo t("Tositenumeron tarkistus ei onnistu, koska tositteen kaikki tili�innit puuttuvat")."<br>";
						$tositenro=0;
					}
				default: //T�m�n pit�isi olla nyt ostolasku

					// T�lle saamme tositenron ostoveloista
					if ($laskurow['tapvm'] == $tiliointipvm) {
						$query = "	SELECT tosite FROM tiliointi
									WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
									tapvm='$tiliointipvm' and tilino = '$yhtiorow[ostovelat]' and
									summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2) * -1";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 0) {
							echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
							$tositenro = 0;
						}
						else {
							$tositerow = mysql_fetch_array ($result);
							$tositenro = $tositerow['tosite'];
						}
					}

					// T�lle saamme tositenron ostoveloista
					if ($laskurow['mapvm'] == $tiliointipvm) {
						$query = "	SELECT tosite FROM tiliointi
									WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and
									tapvm='$tiliointipvm' and tilino = '$yhtiorow[ostovelat]' and
									summa = round($laskurow[summa] * $laskurow[vienti_kurssi],2)";
						$result = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($result) == 0) {
							echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
							$tositenro=0;
						}
						else {
							$tositerow=mysql_fetch_array ($result);
							$tositenro = $tositerow['tosite'];
						}
					}
			}
		}
		echo "<font class='message'>".t("Tili�intirivi liitettiin tositteeseen")." $tositenro</font><br>";
	}

	$tee = 'E';

	if ($ok != 1) {

		require "inc/teetiliointi.inc";

		if ($jaksota == 'on') {
			$tee = 'U';
			require "inc/jaksota.inc"; // Jos jotain jaksotetaan on $tee J
		}
	}
}

// Tositeen n�ytt� muokkausta varten
if ($tee == 'E' or $tee == 'F') {

	// N�ytet��n laskun tai tositteen tiedot....
	$query = "	SELECT lasku.*,
				ifnull(la.nimi, lasku.laatija) laatija_nimi,
				ifnull(h1.nimi, lasku.hyvak1) hyvak1_nimi,
				ifnull(h2.nimi, lasku.hyvak2) hyvak2_nimi,
				ifnull(h3.nimi, lasku.hyvak3) hyvak3_nimi,
				ifnull(h4.nimi, lasku.hyvak4) hyvak4_nimi,
				ifnull(h5.nimi, lasku.hyvak5) hyvak5_nimi,
				ifnull(ma.nimi, lasku.maksaja) maksaja_nimi,
				yriti.nimi maksajanpankkitili,
				yriti.tilino maksajanpankkitilinro
				FROM lasku
				LEFT JOIN yriti ON (lasku.yhtio = yriti.yhtio and maksu_tili = yriti.tunnus)
				LEFT JOIN kuka la ON (lasku.yhtio = la.yhtio and lasku.laatija = la.kuka)
				LEFT JOIN kuka h1 ON (lasku.yhtio = h1.yhtio and lasku.hyvak1 = h1.kuka)
				LEFT JOIN kuka h2 ON (lasku.yhtio = h2.yhtio and lasku.hyvak2 = h2.kuka)
				LEFT JOIN kuka h3 ON (lasku.yhtio = h3.yhtio and lasku.hyvak3 = h3.kuka)
				LEFT JOIN kuka h4 ON (lasku.yhtio = h4.yhtio and lasku.hyvak4 = h4.kuka)
				LEFT JOIN kuka h5 ON (lasku.yhtio = h5.yhtio and lasku.hyvak5 = h5.kuka)
				LEFT JOIN kuka ma ON (lasku.yhtio = ma.yhtio and lasku.maksaja = ma.kuka)
				WHERE lasku.tunnus = '$tunnus'
				and lasku.yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1) {
		echo t("Laskua ei l�ydy!");
		exit;
	}

	$trow = mysql_fetch_array($result);

	// jos pit�� n�ytt�� keikan tietoja
	if ($tee2 == "1") {
		// katotaan keikkajuttuja
		if ($trow["tila"] != "U" and $trow["tila"] != "L") {
			$query = "	SELECT *
						from lasku
						where yhtio = '$kukarow[yhtio]'
						and tila = 'K'
						and vanhatunnus = '$tunnus'";
			$keikres = mysql_query($query) or pupe_error($query);
			$keekrow = mysql_fetch_array($keikres);

			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]'
						and tila = 'K'
						and vanhatunnus = ''
						and laskunro = '$keekrow[laskunro]'";
			$keikres = mysql_query($query) or pupe_error($query);
			$keikrow = mysql_fetch_array($keikres);

			$query = "	SELECT *
						from lasku
						where yhtio = '$kukarow[yhtio]'
						and tila = 'K'
						and laskunro = '$keekrow[laskunro]'
						and vanhatunnus != '0'
						and vanhatunnus != '$tunnus'";
			$muutkeikres = mysql_query($query) or pupe_error($query);
		}
	}

	$kurssi = $trow["maksu_kurssi"];
	if ((float) $kurssi == 0) $kurssi = $trow["vienti_kurssi"];

	// Myyntilaskut
	if ($trow['tila'] == 'U' or $trow['tila'] == 'L') {

		// T�m� on koko yl�otsikon table
		echo "<table>";
		// Aloitetaan vasen sarake
		echo "<tr><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

		echo "<table>";
		echo "<tr><th>".t("Ytunnus")."</th><td>$trow[ytunnus]</td></tr>";
		echo "<tr><th>".t("Nimi")."</th><td>$trow[nimi]</td></tr>";
		if ($trow["nimitark"] != "") echo "<tr><th>".t("Nimitark")."</th><td>$trow[nimitark]</td></tr>";
		echo "<tr><th>".t("Osoite")."</th><td>$trow[osoite]</td></tr>";
		if ($trow["osoitetark"] != "") echo "<tr><th>".t("Osoitetark")."</th><td>$trow[osoitetark]</td></tr>";
		echo "<tr><th>".t("Postino")."</th><td>$trow[postino], $trow[postitp], $trow[maa]</td></tr>";

		if (($trow["toim_nimi"] != $trow["nimi"] or $trow["toim_nimitark"] != $trow["nimitark"] or $trow["toim_osoite"] != $trow["osoite"]) and ($trow["toim_nimi"] != "" or $trow["toim_nimitark"] != "" or $trow["toim_osoite"] != "")) {
			echo "<tr><td><br></td></tr>";
			echo "<tr><th>".t("Toim_nimi")."</th><td>$trow[toim_nimi]</td></tr>";
			if ($trow["toim_nimitark"] != "") echo "<tr><th>".t("Toim_nimitark")."</th><td>$trow[toim_nimitark]</td></tr>";
			echo "<tr><th>".t("Toim_osoite")."</th><td>$trow[toim_osoite]</td></tr>";
			echo "<tr><th>".t("Toim_postino")."</th><td>$trow[toim_postino], $trow[toim_postitp], $trow[toim_maa]</td></tr>";
		}

		echo "</table>";

		// Lopetettaan vasen sarake, aloitetaan keskisarake
		echo "</td><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

		echo "<table>";
		echo "<tr><th>".t("Tapvm")."</th><td>".tv1dateconv($trow["tapvm"])."</td></tr>";
		echo "<tr><th>".t("Er�pvm")."</th><td>".tv1dateconv($trow["erpcm"])."</td></tr>";
		echo "<tr><th>".t("Mapvm")."</th><td>".tv1dateconv($trow["mapvm"])."</td></tr>";
		echo "<tr><th nowrap>".t("Summa")." $trow[valkoodi]</th><td align='right'>$trow[summa_valuutassa]</td></tr>";

		if ($trow["kasumma"] != 0) echo "<tr><th nowrap>".t("Kassa-ale")." $trow[valkoodi]</th><td align='right'>$trow[kasumma]</td></tr>";

		if ($trow["valkoodi"] != $yhtiorow["valkoodi"]) {
			echo "<tr><th nowrap>".t("Summa")." $yhtiorow[valkoodi]</th><td align='right'>".sprintf("%.02f", $trow["summa_valuutassa"] * $kurssi)."</td></tr>";
			if ($trow["kasumma"] != 0) echo "<tr><th nowrap>".t("Kassa-ale")." $yhtiorow[valkoodi]</th><td align='right'>".sprintf("%.02f", $trow["kasumma"] * $kurssi)."</td></tr>";
		}
		echo "</table>";

		// Lopetataan keskisarake, aloitetaan oikea sarake
		echo "</td><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

		echo "<table>";

		echo "<tr><th>".t("Laatija")."</th><td nowrap>".tv1dateconv($trow["luontiaika"], "PITK�")." &raquo; $trow[laatija_nimi]</td></tr>";

		// Onko laskua karhuttu?
		$karhu_query = "	SELECT liitostunnus
							FROM lasku
							WHERE yhtio = '$kukarow[yhtio]'
							AND tunnus = '$trow[tunnus]'";
		$karhu_result = mysql_query($karhu_query) or pupe_error($karhu_query);
		$karhu_liitos = mysql_fetch_array($karhu_result);

		// haetaan kaikki karhukerrat
		$karhu_query = "	SELECT ifnull(group_concat(ktunnus), '') ktunnus
							FROM karhu_lasku
							WHERE karhu_lasku.ltunnus = '$trow[tunnus]'
							ORDER BY ktunnus";
		$karhu_result = mysql_query($karhu_query) or pupe_error($karhu_query);
		$karhu_row = mysql_fetch_array($karhu_result);

		if ($karhu_row["ktunnus"] != "") {
			// haetaan kaikki karhukerrat jotka kuuluu t�h�n laskuun
			$karhu_query = "	SELECT pvm, tyyppi, karhukierros.tunnus ktunnus, group_concat(lasku.tunnus) laskutunnukset
								FROM karhukierros
								JOIN karhu_lasku ON (karhu_lasku.ktunnus = karhukierros.tunnus)
								JOIN lasku USE INDEX (primary) ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.yhtio = karhukierros.yhtio and lasku.tila = 'U' and lasku.liitostunnus = '$karhu_liitos[liitostunnus]')
								WHERE karhukierros.yhtio = '$kukarow[yhtio]'
								and karhukierros.tunnus in ($karhu_row[ktunnus])
								GROUP BY pvm, tyyppi, karhukierros.tunnus";
			$karhu_result = mysql_query($karhu_query) or pupe_error($karhu_query);

			if (mysql_num_rows($karhu_result) > 0) {
				echo "<tr><th>",t('Karhu / Tratta'),":</th><td>";
				
				$laskuri = 0;
				
				while ($karhu_row = mysql_fetch_array($karhu_result)) {
					
					$laskuri++;
					
					if ($karhu_row["tyyppi"] == 'T') {
						echo "<form id='tulostakopioform_$laskuri' name='tulostakopioform_$laskuri' method='post' action='".$palvelin2."muutosite.php'>
								<input type='hidden' name='karhutunnus' value='$karhu_row[ktunnus]'>
								<input type='hidden' name='lasku_tunnus[]' value='$karhu_row[laskutunnukset]'>
								<input type='hidden' name='tee' value='NAYTATILAUS'>
								<input type='hidden' name='tee_pdf' value='tulosta_tratta'>
								<input type='submit' value='".t("Tratta")." - ".tv1dateconv($karhu_row["pvm"])."' onClick=\"js_openFormInNewWindow('tulostakopioform_$laskuri', ''); return false;\">
								</form>";
					}
					else {
						echo "<form id='tulostakopioform_$laskuri' name='tulostakopioform_$laskuri' method='post' action='".$palvelin2."muutosite.php'>
								<input type='hidden' name='karhutunnus' value='$karhu_row[ktunnus]'>
								<input type='hidden' name='lasku_tunnus[]' value='$karhu_row[laskutunnukset]'>
								<input type='hidden' name='tee' value='NAYTATILAUS'>
								<input type='hidden' name='tee_pdf' value='tulosta_karhu'>
								<input type='submit' value='".t("Maksukehotus")." - ".tv1dateconv($karhu_row["pvm"])."' onClick=\"js_openFormInNewWindow('tulostakopioform_$laskuri', ''); return false;\">
								</form>";
					}
					echo "<br>";
				}
				echo "</td></tr>";
			}
		}

		echo "<tr><th>".t("Maksutieto")."</th><td>".wordwrap($trow["viite"]." ".$trow["viesti"]." ".$trow["sisviesti1"], 45, "<br>")."</td></tr>";

		// katsotaan onko t�st� laskusta tehty korkolasku
		$korko_query = "	SELECT olmapvm, liitostunnus
							FROM lasku
							WHERE yhtio='$kukarow[yhtio]'
							AND tunnus='$trow[tunnus]'
							AND olmapvm > '0000-00-00'";
		$korko_result = mysql_query($korko_query) or pupe_error($korko_query);

		if (mysql_num_rows($korko_result) > 0) {

			$korkolaskurow = mysql_fetch_array($korko_result);

			// etsit��n korkolasku
			$korko2_query = "	SELECT lasku2.tunnus
								FROM lasku
								JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.tyyppi = 'L' AND tilausrivi.tuoteno = 'Korko' AND tilausrivi.otunnus = lasku.tunnus)
								JOIN lasku AS lasku2 ON (lasku2.yhtio = lasku.yhtio AND lasku2.laskunro = lasku.laskunro AND lasku2.tila = 'U')
								WHERE lasku.yhtio = '$kukarow[yhtio]'
								AND lasku.olmapvm = '$korkolaskurow[olmapvm]'
								AND lasku.tapvm = '$korkolaskurow[olmapvm]'
								AND lasku.liitostunnus = '$korkolaskurow[liitostunnus]'
								AND lasku.tila = 'L'";
			$korko2_result = mysql_query($korko2_query) or pupe_error($korko2_query);

			if (mysql_num_rows($korko2_result) > 0) {

				echo "<tr><th>",t('Korkolaskut'),":</th><td>";

				while ($korkolaskurow2 = mysql_fetch_array($korko2_result)) {
					echo "<form id='tulostakopioform_$korkolaskurow2[tunnus]' name='tulostakopioform_$korkolaskurow2[tunnus]' method='post' action='".$palvelin2."tilauskasittely/tulostakopio.php'>
							<input type='hidden' name='otunnus' value='$korkolaskurow2[tunnus]'>
							<input type='hidden' name='toim' value='LASKU'>
							<input type='hidden' name='tee' value='NAYTATILAUS'>
							<input type='submit' value='".tv1dateconv($korkolaskurow['olmapvm'])."' onClick=\"js_openFormInNewWindow('tulostakopioform_$korkolaskurow2[tunnus]', ''); return false;\">
							</form>";
				}

				echo "</td></tr>";
			}
		}

		if ($trow["comments"] != '' or $trow['saldo_maksettu'] != 0) {
			echo "<tr><th>".t("Kommentti")."</th><td>$trow[comments]";

			if ($trow['saldo_maksettu'] != 0) {
				if ($trow["comments"] != '') echo "<br>";
				echo t("Laskusta avoinna");
				echo " ";
				echo $trow['summa'] - $trow['saldo_maksettu'];

				if ($trow['valkoodi'] != $yhtiorow['valkoodi']) {
					echo " (";
					echo $trow['saldo_valuutassa'] - $trow['saldo_maksettu_valuutassa'];
					echo $trow['valkoodi'];
					echo ")";
				}
			}
			echo "</td></tr>";
		}

		// tehd��n laskulinkki
		echo "<tr><th>".t("Laskun kuva")."</th><td>".ebid($tunnus)."</td></tr>";
		echo "</table>";

		// Lopetaaan oikea sarake
		echo "</td></tr>";

		$query = "	SELECT fakta
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$trow[liitostunnus]'";
		$faktares = mysql_query($query) or pupe_error($query);
		$faktarow = mysql_fetch_assoc($faktares);

		if (trim($faktarow["fakta"]) != "") {
			echo "<tr><th colspan='3'>".t("Fakta")."</th></tr>";
			echo "<tr><td colspan='3'>".wordwrap($faktarow["fakta"], 120, "<br>")."</td></tr>";
		}

		// Lopetaaan koko table
		echo "</table>";

	}
	// Tositteet
	elseif ($trow["tila"] == 'X') {
		echo "<table>";
		echo "<tr><th>".t("Laatija")."</th><td nowrap>$trow[laatija_nimi] @ ".tv1dateconv($trow["luontiaika"], "PITK�")."</td></tr>";
		echo "<tr><th>".t("Tapvm")."</th><td>".tv1dateconv($trow["tapvm"])."</td></tr>";
		echo "<tr><th>".t("Kommentti")."</th><td>$trow[comments]</td></tr>";
		echo "<tr><th>".t("Liitetiedostot")."</th><td>".ebid($tunnus) ."</td></tr>";
		echo "</table>";
	}
	// Jotain muuta kuin Myytilasku tai Tosite
	else {
		// T�m� on koko yl�otsikon table
		echo "<table>";
		// Aloitetaan vasen sarake
		echo "<tr><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

		echo "<table>";
		echo "<tr><th>".t("Ytunnus")."</th><td>$trow[ytunnus]</td></tr>";
		echo "<tr><th>".t("Nimi")."</th><td>$trow[nimi]</td></tr>";
		if ($trow["nimitark"] != "") echo "<tr><th>".t("Nimitark")."</th><td>$trow[nimitark]</td></tr>";
		echo "<tr><th>".t("Osoite")."</th><td>$trow[osoite]</td></tr>";
		if ($trow["osoitetark"] != "") echo "<tr><th>".t("Osoitetark")."</th><td>$trow[osoitetark]</td></tr>";
		echo "<tr><th>".t("Postino")."</th><td>$trow[postino], $trow[postitp], $trow[maa]</td></tr>";

		//Ulkomaan ostolaskuille
		if (strtoupper($trow["maa"]) != 'FI') {
			if ($trow["pankki_haltija"] != "") echo "<tr><th>".t("Pankkihaltija")."</th><td>$trow[pankki_haltija]</td></tr>";
			echo "<tr><th>".t("Tilinumero")."</th><td>$trow[ultilno]</td></tr>";
			if ($trow["pankki1"] != "") echo "<tr><th>".t("Pankkitieto")."</th><td>$trow[pankki1]</td></tr>";
			if ($trow["pankki2"] != "") echo "<tr><th>".t("Pankkitieto")."</th><td>$trow[pankki2]</td></tr>";
			if ($trow["pankki3"] != "") echo "<tr><th>".t("Pankkitieto")."</th><td>$trow[pankki3]</td></tr>";
			if ($trow["pankki4"] != "") echo "<tr><th>".t("Pankkitieto")."</th><td>$trow[pankki4]</td></tr>";
			if ($trow["swift"] != "") echo "<tr><th>".t("Swift")."</th><td>$trow[swift]</td></tr>";
		}
		else {
			echo "<tr><th>".t("Tilinumero")."</th><td>$trow[tilinumero]</td></tr>";
		}
		echo "<tr><th>".t("Maksutieto")."</th><td>".wordwrap($trow["viite"]." ".$trow["viesti"]." ".$trow["sisviesti1"], 40, "<br>")."</td></tr>";

		echo "</table>";

		// Lopetaan vasen sarake, aloitetaan keskisarake
		echo "</td><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

		if ($tee2 != 1) {

			echo "<table>";
			echo "<tr><th>".t("Tapvm")."</th><td align='right'>".tv1dateconv($trow["tapvm"])."</td></tr>";
			echo "<tr><th>".t("Er�pvm")."</th><td align='right'>".tv1dateconv($trow["erpcm"])."</td></tr>";
			echo "<tr><th>".t("Olmapvm")."</th><td align='right'>".tv1dateconv($trow["olmapvm"])."</td></tr>";
			echo "<tr><th>".t("Mapvm")."</th><td align='right'>".tv1dateconv($trow["mapvm"])."</td></tr>";
			echo "<tr><th nowrap>".t("Summa")." $trow[valkoodi]</th><td align='right'><strong>$trow[summa]</strong></td></tr>";
			if ($trow["kasumma"] != 0) echo "<tr><th nowrap>".t("Kassa-ale")." $trow[valkoodi]</th><td align='right'>$trow[kasumma]</td></tr>";
			if ($trow["valkoodi"] != $yhtiorow["valkoodi"]) {
				echo "<tr><th nowrap>".t("Summa")." $yhtiorow[valkoodi]</th><td align='right'><strong>".sprintf("%.02f", $trow["summa"] * $kurssi)."</strong></td></tr>";
				if ($trow["kasumma"] != 0) echo "<tr><th nowrap>".t("Kassa-ale")." $yhtiorow[valkoodi]</th><td align='right'>".sprintf("%.02f", $trow["kasumma"] * $kurssi)."</td></tr>";
			}

			if ($trow["vanhatunnus"] != 0) {
				$query = "	SELECT *
							from lasku
							where yhtio = '$kukarow[yhtio]'
							and tila in ('H','Y','M','P','Q')
							and vanhatunnus = '$trow[vanhatunnus]'";
				$jaetutres = mysql_query($query) or pupe_error($query);

				echo "<tr><td colspan='2'><br><font class='message'>".sprintf(t("Lasku on jaettu %s osaan!"), mysql_num_rows($jaetutres))."</font><br></td></tr>";

				echo "<tr><th>".t("Alkuper�inen summa")."</th><td align='right'><strong>$trow[arvo]</strong></td></tr>";
				$osa = 1;
				while ($jaetutrow = mysql_fetch_array ($jaetutres)) {
					echo "<tr><th>".t("Osa")." $osa</th><td align='right'><a href='muutosite.php?tee=E&tunnus=$jaetutrow[tunnus]'>$jaetutrow[summa]</a></td></tr>";
					$osa++;
				}
			}

			if ($trow['laskunro'] != 0) echo "<tr><th nowrap>",t("Laskunro"),"</th><td align='right'>$trow[laskunro]</td></tr>";

			echo "</table>";
		}
		else { //Laajennetut
			echo "<table>";
			echo "<tr><th>".t("Keikka")."</th><td>$keikrow[laskunro]</td></tr>";
			echo "<tr><th>".t("L�hetysmaa")."</th><td>$keikrow[maa_lahetys]</td></tr>";
			echo "<tr><th>".t("Kuljetusmuoto")."</th><td>$keikrow[kuljetusmuoto]</td></tr>";
			echo "<tr><th>".t("KT")."</th><td>$keikrow[kauppatapahtuman_luonne]</td></tr>";
			if ($keikrow["rahti"] != 0) echo "<tr><th>".t("Rahti")."</th><td>$keikrow[rahti]</td></tr>";
			if ($keikrow["rahti_etu"] != 0) echo "<tr><th>".t("Eturahti")."</th><td>$keikrow[rahti_etu]</td></tr>";
			if ($keikrow["rahti_huolinta"] != 0) echo "<tr><th>".t("Huolinta")."</th><td>$keikrow[rahti_huolinta]</td></tr>";
			if ($keikrow["erikoisale"] != 0) echo "<tr><th>".t("Alennus")."</th><td>$keikrow[erikoisale]</td></tr>";
			if ($keikrow["bruttopaino"] != 0) echo "<tr><th>".t("Paino")."</th><td>$keikrow[bruttopaino]</td></tr>";
			echo "<tr><th>".t("Toimaika")."</th><td>".tv1dateconv($keikrow["toimaika"])."</td></tr>";
			echo "<tr><th>".t("Kommentit")."</th><td>$keikrow[comments]</td></tr>";
			echo "<tr><th>".t("Keikan muut laskut")."</td><td>";
			while ($muutkeikrow = mysql_fetch_array($muutkeikres)) {
				echo "<a href='muutosite.php?tee=E&tunnus=$muutkeikrow[vanhatunnus]'>$muutkeikrow[nimi] ($muutkeikrow[summa])</a><br>";
			}
			echo "</td></tr>";
			echo "</table>";
		}

		// Lopetetaan keskisarake, aloitetaan oikea sarake
		echo "</td><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

		echo "<table>";

		$laskutyyppi = $trow["tila"];
		$alatila = $trow["alatila"];
		require ("inc/laskutyyppi.inc");

		echo "<tr><th>".t("Tila")."</th><td nowrap>$laskutyyppi $alatila</td></tr>";
		echo "<tr><th>".t("Laatija")."</th><td nowrap>".tv1dateconv($trow["luontiaika"], "PITK�")." &raquo; $trow[laatija_nimi]</td></tr>";
		echo "<tr><th>".t("Hyv�ksyj�1")."</th><td nowrap>".tv1dateconv($trow["h1time"], "PITK�")." &raquo; $trow[hyvak1_nimi]</td></tr>";
		if ($trow["hyvak2"] != "") echo "<tr><th>".t("Hyv�ksyj�2")."</th><td nowrap>".tv1dateconv($trow["h2time"], "PITK�")." &raquo; $trow[hyvak2_nimi]</td></tr>";
		if ($trow["hyvak3"] != "") echo "<tr><th>".t("Hyv�ksyj�3")."</th><td nowrap>".tv1dateconv($trow["h3time"], "PITK�")." &raquo; $trow[hyvak3_nimi]</td></tr>";
		if ($trow["hyvak4"] != "") echo "<tr><th>".t("Hyv�ksyj�4")."</th><td nowrap>".tv1dateconv($trow["h4time"], "PITK�")." &raquo; $trow[hyvak4_nimi]</td></tr>";
		if ($trow["hyvak5"] != "") echo "<tr><th>".t("Hyv�ksyj�5")."</th><td nowrap>".tv1dateconv($trow["h5time"], "PITK�")." &raquo; $trow[hyvak5_nimi]</td></tr>";
		echo "<tr><th>".t("Poimittu")."</th><td nowrap>".tv1dateconv($trow["maksuaika"], "PITK�", "")." &raquo; $trow[maksaja_nimi]</td></tr>";
		echo "<tr><th>".t("Maksuaineisto")."</th><td nowrap>";
		if ($trow["popvm"] != '0000-00-00 00:00:00') {
			$queryoik = "SELECT tunnus from oikeu where nimi like '%selaa_maksuaineisto.php' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
			$res = mysql_query($queryoik) or pupe_error($queryoik);

			if (mysql_num_rows($res) > 0) {
				list($apu, $dymmu) = explode(" ", $trow["popvm"]);
				list($apu_vv, $apu_kk, $apu_pp) = explode("-", $apu);
				echo "<a href='{$palvelin2}selaa_maksuaineisto.php?alkuvv=$apu_vv&alkukk=$apu_kk&alkupp=$apu_pp&loppuvv=$apu_vv&loppukk=$apu_kk&loppupp=$apu_pp&kuka_poimi=$trow[maksaja_nimi]'>";
				echo tv1dateconv($trow["popvm"], "PITK�", "");
				echo "</a>";
			}
			else {
				echo tv1dateconv($trow["popvm"], "PITK�", "");
			}
			echo " &raquo;  $trow[maksaja_nimi]";
		}
		echo "</td></tr>";
		if ($trow['maksajanpankkitili'] != '') echo "<tr><th>".t("Oma pankkitili")."</th><td>$trow[maksajanpankkitili] ($trow[maksajanpankkitilinro])</td></tr>";

		// tehd��n laskulinkit
		echo "<tr><th nowrap>".t("Liitteet")."</th><td>".ebid($tunnus)."</td></tr>";
		echo "</table>";

		// Lopetetaan oikea sarake
		echo "</td></tr>";

		$query = "	SELECT fakta
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]' and tunnus='$trow[liitostunnus]'";
		$faktares = mysql_query($query) or pupe_error($query);
		$faktarow = mysql_fetch_assoc($faktares);

		if (trim($faktarow["fakta"]) != "" or $trow["comments"] != "") {
			$vali = " ";
			if ($faktarow["fakta"] != "" and $trow["comments"] != "") {
				$vali = "<br>";
			}
			echo "<tr><th colspan='3'>".t("Fakta")." / ".t("Kommentit")."</th></tr>";
			echo "<tr><td colspan='3'>".wordwrap($faktarow["fakta"].$vali.$trow["comments"], 120, "<br>")."</td></tr>";
		}

		// Lopetetaan koko otsikko
		echo "</table>";
	}

	// N�ytet��n nappi vain jos siihen on oikeus
	if ($oikeurow['paivitys'] == 1) {
		echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
				<input type = 'hidden' name = 'tee' value='M'>
				<input type = 'hidden' name = 'tila' value=''>
				<input type = 'hidden' name = 'tunnus' value='$tunnus'>
				<input type = 'submit' value = '".t("Muuta tietoja")."'>
				</form>";
	}

	$queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='liitetiedostot' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
	$res = mysql_query($queryoik) or pupe_error($queryoik);

	if (mysql_num_rows($res) > 0) {
		echo "<form method='POST' action='".$palvelin2."yllapito.php?toim=liitetiedostot&from=muutosite&ohje=off&haku[7]=@lasku&haku[8]=@$tunnus&lukitse_avaimeen=$tunnus&lukitse_laji=lasku'>
				<input type = 'hidden' name = 'lopetus' value = '$lopetus/SPLIT/".$palvelin2."muutosite.php////tee=E//tunnus=$tunnus'>
				<input type = 'submit' value='" . t('Muokkaa liitteit�')."'>
				</form>";
	}

	// N�ytet��n nappi vain jos tieoja on
	if ($trow['vienti'] != '' and $trow['vienti'] != 'A' and $trow['vienti'] != 'D' and $trow['vienti'] != 'G') {
		if ($tee2 != 1) {
			echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
				<input type = 'hidden' name = 'tee' value='$tee'>
				<input type = 'hidden' name = 'tee2' value='1'>
				<input type = 'hidden' name = 'tunnus' value='$tunnus'>
				<input type = 'submit' value = '".t("Lis�tiedot")."'></form>";
		}
		else {
			echo "<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
				<input type = 'hidden' name = 'tee' value='$tee'>
				<input type = 'hidden' name = 'tunnus' value='$tunnus'>
				<input type = 'submit' value = '".t("Normaalitiedot")."'></form>";
		}
	}

	if ($trow['tila'] == 'U') {
		echo "<form id='tulostakopioform_$tunnus' name='tulostakopioform_$tunnus' method='post' action='".$palvelin2."tilauskasittely/tulostakopio.php' autocomplete='off'>
				<input type='hidden' name='otunnus' value='$tunnus'>
				<input type='hidden' name='toim' value='LASKU'>
				<input type='hidden' name='tee' value='NAYTATILAUS'>
				<input type='submit' value='".t("N�yt� laskun PDF")."' onClick=\"js_openFormInNewWindow('tulostakopioform_$tunnus', ''); return false;\"></form>";

		if ($trow['viesti'] == 'Korkolasku') {
			echo "<form action = '$PHP_SELF' method='post'>
			<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
			<input type='hidden' name='tunnus' value='$trow[tunnus]'>
			<input type='hidden' name='nayta_pdf' value='1'>
			<input type='hidden' name='tee' value='tulosta_korkoerittely'>
			<input type='submit' value='" . t('Tulosta korkoerittely')."'></form>";
		}
	}

	if ($trow["tila"] == "U" or $trow["tila"] == "L") {
		// Tehd��n nappula, jolla voidaan vaihtaa n�kym�ksi tilausrivit/tili�intirivit
		if ($tee == 'F') {
			$ftee = 'E';
			$fnappula = t('N�yt� tili�innit');
		}
		else {
			$ftee = 'F';
			$fnappula = t('N�yt� tilausrivit');
		}

		echo "<form action = '$PHP_SELF' method='post'>
			<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
			<input type = 'hidden' name = 'tee' value='$ftee'>
			<input type = 'hidden' name = 'tunnus' value='$tunnus'>
			<input type = 'submit' value = '$fnappula'>
			</form>
			<form action = '$PHP_SELF' method='post'>
			<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
			<input type = 'hidden' name = 'tee' value='G'>
			<input type = 'hidden' name = 'tunnus' value='$tunnus'>
			<input type = 'submit' value = '".t("Seuraava")."'>
			</form>";
	}

	if ($tee == 'F') {
		// Laskun tilausrivit
		echo "<br><br>";
		require "inc/tilausrivit.inc";
		$tee = '';
	}
	else {
		// Tositteen tili�intirivit...
		require "inc/tiliointirivit.inc";

		echo "<br><br>";
		$tee = "";
	}

}

if (strlen($tee) == 0) {

	if (strlen($formi) == 0) {
		$formi = 'valikko';
		$kentta = 'tap';
	}

	echo "<form name = 'valikko' action = '$PHP_SELF' method='post'><table>";
	echo "<tr>
		  <td>".t("Etsi tositetta")."</td>
		  <td>".t("tapahtumapvm")."</td>
		  <td>
		  	<input type='hidden' name='tee' value='Y'>
			<input type='text' name='tap' maxlength='2' size=2>
			<input type='text' name='tak' maxlength='2' size=2>
			<input type='text' name='tav' maxlength='4' size=4></td>
		  <td></td>
		  </tr>
		  <tr>
		  <td></td>
		  <td>summa</td>
		  <td><input type='text' name='summa' size=10></td>
		  <td></td>
		  </tr>
		  <tr>
		  <td></td>
		  <td>tili</td>
		  <td><input type='text' name='tilino' size=10></td>
		  <td></td>
		  </tr>
		  <tr>
		  <td></td>
		  <td>".t("osa selitteest�")."</td>
		  <td><input type='text' name='selite' maxlength='15' size=10></td>
		  <td><input type='checkbox' name='ohita' maxlength='15' size=10>".t("Ohita n�m�")."</td>
		  </tr>
		  <tr>
		  <td></td>
		  <td>laatija</td>
		  <td><input type='text' name='laatija' size=10></td>
		  <td></td>
		  </tr>";

	//$kpexport tulee salanasat.php:st�
	if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
		echo "
		  <tr>
		  <td></td>
		  <td>".t("tositenumero")."</td>
		  <td><input type='text' name='tositenro' size=10></td>
		  <td></td>
		  </tr>";
	}

	echo "<tr>
		  	<td></td>
		  	<td>".t("n�yt� muutetut rivit")."</td>
		  	<td><input type='checkbox' name='viivatut'></td>
		  	<td><input type = 'submit' value = '".t("Etsi")."'></form></td></tr>

		  	<tr>
		  	<td>".t("Etsi virhett�")."</td>
		  	<td>".t("n�yt� tositteet, jotka eiv�t t�sm��")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=Z' method='post'><input type = 'submit' value = '".t("N�yt�")."'></form></td>
		  	</tr>

			<tr>
		  	<td></td>
		  	<td>".t("n�yt� tositteet, joilta puuttuu kustannuspaikka")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=X' method='post'><input type = 'submit' value = '".t("N�yt�")."'></form></td>
		  	</tr>

			<tr>
		  	<td></td>
		  	<td>".t("n�yt� tositteet, joiden ostovelat ei t�sm��")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=W' method='post'><input type = 'submit' value = '".t("N�yt�")."'></form></td>
		  	</tr>

			<tr>
		  	<td></td>
		  	<td>".t("n�yt� tositteet, joiden tila tuntuu v��r�lt�")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=T' method='post'><input type = 'submit' value = '".t("N�yt�")."'></form></td>
		  	</tr>

			<tr>
		  	<td></td>
		  	<td>".t("n�yt� tositteet, joiden myyntisaamiset ovat v��rin")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=S' method='post'><input type = 'submit' value = '".t("N�yt�")."'></form></td>
			</tr>

			<tr>
		  	<td></td>
		  	<td>".t("n�yt� tositteet, joiden marginaaliverotili�innit ovat v��rin")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=�' method='post'><input type = 'submit' value = '".t("N�yt�")."'></form></td>
			</tr>

			<tr>
		  	<td></td>
		  	<td>".t("n�yt� maksetut laskut, joilla on myyntisaamisia")."</td>
		  	<td></td>
		  	<td><form action = '$PHP_SELF?tee=�' method='post'><input type = 'submit' value = '".t("N�yt�")."'></form></td>
			</tr>


			</table>";
}

if ($lopetus != '') {
	echo "<br><br>";
	lopetus($lopetus);
}

require "inc/footer.inc";

?>
