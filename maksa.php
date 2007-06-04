<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Laskujen maksatus")."</font><hr>";

    if ($tee == 'O') {
		// Sitten oletustili p��lle, jos sit� pyydettiin!
		$query = "UPDATE kuka set
				  oletustili = '$oltili'
				  WHERE kuka = '$kukarow[kuka]' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "V"; // n�ytet��n k�ytt�j�lle valikko
	}

	// Etsit��n aluksi yrityksen oletustili
	$query = "SELECT oletustili, yriti.tunnus
				FROM kuka, yriti
				WHERE kuka.yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and kuka.oletustili = yriti.tunnus and yriti.yhtio = kuka.yhtio";
	$result = mysql_query($query) or pupe_error($query);
	$oltilrow=mysql_fetch_array($result);

	if ((mysql_num_rows($result) == 0) || (strlen($oltilrow[0]) == 0)) {
		echo t("K�ytt�j�ll� ei ollut oletustili�")."!<br><br>";
		$tee = 'W';
	}

	if ($tee == 'X') {
		// Haa poistamme k�yttyj�n oletuksen!
		$query = "	UPDATE kuka set
					oletustili = ''
					WHERE kuka ='$kukarow[kuka]' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = 'W';
	}

	if ($tee == 'Z') {
		// Tarkistetaan  laskujen oletus maksupvm, eli poistetaan vanhentuneet kassa-alet
		$query = "UPDATE lasku
	                  SET olmapvm=if(kapvm<now(),if(kapvm='0000-00-00',erpcm,kapvm),erpcm)
	                  WHERE yhtio='$kukarow[yhtio]' and tila in ('H', 'M')";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "V";
	}

	if ($tee == 'H' or $tee == 'G') {
		// Lasku merkit��n maksettavaksi ja v�hennet��n limiitti� tai tehd��n vain tarkistukset p�itt�invientiin.
		$tili = $oltilrow[1];
		
		// maksetaan kassa-alennuksella
		if ($kaale == 'on') { 
			$maksettava = "(lasku.summa - lasku.kasumma)";
			$alatila = ", alatila = 'K'";
		}
		else {
			$maksettava = "lasku.summa";
			$alatila = '';
		}

		$query = "	SELECT valuu.kurssi, round($maksettava * valuu.kurssi,2) summa, maksuaika, olmapvm, tilinumero, maa, kapvm, erpcm,
							ultilno, swift, pankki1, pankki2, pankki3, pankki4, valkoodi
					FROM lasku, valuu
					WHERE lasku.tunnus = '$tunnus' and
					lasku.valkoodi = valuu.nimi and
					valuu.yhtio = '$kukarow[yhtio]' and
					lasku.yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<b>".t("Haulla ei l�ytynyt yht� laskua")."</b>$query";
			
			require ("inc/footer.inc");
			exit;
		}
		$trow=mysql_fetch_array ($result);

		// Tukitaan mahdollinen aikaikkuna
		if ($trow["maksuaika"] == "0000-00-00 00:00:00") { // Konversion kukkanen
			$trow["maksuaika"] = "";
		}
		
		// Se oli jo maksettu
		if (strlen($trow["maksuaika"]) > 0) { 
			echo "<font class='error'>".t("Laskun ehti jo joku muu maksaa! Ohitetaan")."! $trow[maksuaika]</font><br>";
			
			require ("inc/footer.inc");
			exit;
		}
		
		if ($poikkeus == 'on') 						$trow['olmapvm'] = date("Y-m-d");
		elseif (date("Y-m-d") <= $trow['kapvm']) 	$trow['olmapvm'] = $trow['kapvm'];
		elseif(date("Y-m-d") <= $trow['erpcm']) 	$trow['olmapvm'] = $trow['erpcm'];
		else  										$trow['olmapvm'] = date("Y-m-d");
		
		//Kotimainen hyvityslasku --> vastaava m��r� rahaa on oltava veloituspuolella
		if ($trow['summa'] < 0 and $eipankkiin == '')  {
			if (strtoupper($trow['maa']) == 'FI') {
				$query = "	SELECT sum(if(alatila='K', summa - kasumma, summa)) summa 
							FROM lasku
							WHERE yhtio='$kukarow[yhtio]' and tila='P' and olmapvm = '$trow[olmapvm]' and
							maksu_tili = '$tili' and maa = 'FI' and tilinumero='$trow[tilinumero]'";
			}
			else {
				$query = " SELECT sum(if(alatila='K', summa - kasumma, summa)) summa 
									FROM lasku
									WHERE yhtio='$kukarow[yhtio]' 
									and tila='P' 
									and olmapvm = '$trow[olmapvm]' 
									and maksu_tili = '$tili' 
									and maa <> 'FI'
									and valkoodi = '$trow[valkoodi]'
									and ultilno = '$trow[ultilno]'
									and swift = '$trow[swift]'
									and pankki1 = '$trow[pankki1]'
									and pankki2 = '$trow[pankki2]'
									and pankki3 = '$trow[pankki3]'
									and pankki4 = '$trow[pankki4]'
									and maksaja = '$kukarow[kuka]'";
			}
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) != 1) {
				echo "<b>".t("Hyvityshaulla ei l�ytynyt mit��n")."</b>$query";
				
				require ("inc/footer.inc");
				exit;
			}
			
			$veloitusrow=mysql_fetch_array ($result);

			if (strtoupper($trow['maa']) == 'FI') {
				if ($veloitusrow['summa'] + $trow['summa'] < 0) {
					echo "<font class='error'>".t("Hyvityslaskua vastaavaa m��r�� veloituksia ei ole valittuna.")."<br>".t("Valitse samalle asiakkaalle lis�� veloituksia, jos haluat valita t�m�n hyvityslaskun maksatukseen")." ($veloitusrow[summa])</font><br>";
						$tee = 'S';
				}

				if (abs($veloitusrow['summa'] + $trow['summa']) < 0.01) {
					if ($valinta=='') { // Ei ole valittu mit� tehd��n
					
						echo "<font class='message'>".t("Hyvityslasku ja veloituslasku(t) n�ytt�v�t menev�n p�itt�in")."<br>".t("Haluatko, ett� ne suoritetaan heti ja j�tet��n l�hett�m�tt� pankkiin")."?</font><br><br>";
						echo "<form action = 'maksa.php' method='post'>
						<input type='hidden' name = 'tee' value='G'>
						<input type='hidden' name = 'valuu' value='$valuu'>
						<input type='hidden' name = 'erapvm' value='$erapvm'>
						<input type='hidden' name = 'kaikki' value='$kaikki'>
						<input type='hidden' name = 'tapa' value='$tapa'>
						<input type='hidden' name = 'tunnus' value='$tunnus'>
						<input type='hidden' name = 'kaale' value='$kaale'>
						<input type='hidden' name = 'poikkeus' value='$poikkeus'>
						<input type='radio' name = 'valinta' value='K' checked> ".t("Kyll�")."
						<input type='radio' name = 'valinta' value='E'> ".t("Ei")."
						<input type='submit' name = 'valitse' value='".t("valitse")."'>";
						
						require ("inc/footer.inc");
						exit;
					}
					if ($valinta == 'E') {
						echo "<font class='error'>".t("Valitut veloitukset ja hyvitykset menev�t tasan p�itt�in (summa 0,-). Pankkiin ei kuitenkaan voi l�hett�� nolla-summaisia maksuja. Jos haluat l�hett�� n�m� p�itt�in menev�t veloitukset ja hyvitykset pankkiin, pit�� sinun valita lis�� veloituksia. Yhteissumman pit�� olla suurempi kuin 0.")."</font><br>";
						$tee = 'S';
					}
				}
			}
			else {
				if ($veloitusrow['summa'] + $trow['summa'] < 0.01) {
					echo "<font class='error'>".t("Hyvityslaskua vastaavaa m��r�� veloituksia ei ole valittuna.")."<br>".t("Valitse samalle asiakkaalle lis�� veloituksia, jos haluat valita t�m�n hyvityslaskun maksatukseen")." ($veloitusrow[summa])</font><br>";
					$tee = 'S';
				}
			}
		}
	}

	if ($tee == 'G') {
		// Suoritetaan p�itt�in (vain kotimaa)
		echo "<font class='message'>".t("Suoritan p�itt�in")." ";
		
		//Maksetaan hyvityslasku niin k�sittely helpottuu
		if ($poikkeus=='on') 						$maksupvm = date("Y-m-d");
		elseif (date("Y-m-d") <= $trow['kapvm']) 	$maksupvm = $trow['kapvm'];
		elseif(date("Y-m-d") <= $trow['erpcm']) 	$maksupvm = $trow['erpcm'];
		else  										$maksupvm = date("Y-m-d");
					
		$query = "	UPDATE lasku set
					maksaja = '$kukarow[kuka]',
					maksuaika = now(),
					maksu_kurssi = '$trow[0]',
					maksu_tili = '$tili',
					tila = 'P',
					olmapvm = '$maksupvm'
					$alatila
					WHERE tunnus='$tunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$kurssi = 1;
		$query = "	SELECT ytunnus, nimi, postitp,
					round(summa * " . $kurssi . ", 2) 'maksusumma',
					round(kasumma * " . $kurssi . ", 2) 'maksukasumma',
					round(summa * vienti_kurssi, 2) 'vietysumma',
					round(kasumma * vienti_kurssi, 2) 'vietykasumma',
					concat(summa, ' ', valkoodi) 'summa',
					ebid, tunnus, alatila, vienti_kurssi, tapvm
					FROM lasku
					WHERE yhtio 	= '$kukarow[yhtio]' 
					and tila		= 'P' 
					and olmapvm 	= '$maksupvm' 
					and maksu_tili 	= '$tili' 
					and maa 	= 'fi' 
					and tilinumero	= '$trow[tilinumero]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='message'>".t("K�sittelen")." ". mysql_num_rows($result) ." ".t("laskua")."<br></font>";
		
		if (mysql_num_rows($result) < 2) {
			echo "<font class='error'>".t("Laskuja katosi")."</font><br>";
			
			require ("inc/footer.inc");
			exit;
		}
		
		while ($laskurow=mysql_fetch_array($result)) {
			// Oletustili�innit
			$summa =  $trow['summa'];
			$selite = "Suoritettu p�itt�in $laskurow[nimi]";
			
			// Kassa-ale
			if ($laskurow['alatila'] != 'K') $laskurow['maksukasumma'] = 0;


			if ($laskurow['maksukasumma'] != 0) {
				// Kassa-alessa on huomioitava alv, joka voi olla useita vientej� (uhhhh tai iiikkkkk)
				$totkasumma = 0;
				
				$query = "	SELECT * 
							from tiliointi 
							WHERE ltunnus='$tunnus' 
							and yhtio = '$kukarow[yhtio]' 
							and tapvm = '$laskurow[tapvm]' 
							and tilino<>$yhtiorow[ostovelat] 
							and tilino<>$yhtiorow[alv] 
							and korjattu = ''";
				$yresult = mysql_query($query) or pupe_error($query);
			
				while ($tiliointirow=mysql_fetch_array ($yresult)) {
					// Kuinka paljon on t�m�n viennin osuus
					$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $laskurow['vietysumma'] * $laskurow['maksukasumma'],2);

					if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
						$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
						$summa -= $alv;
					}

					$totkasumma += $summa + $alv;

					$query = "	INSERT into tiliointi set
								yhtio ='$kukarow[yhtio]',
								ltunnus = '$laskurow[tunnus]',
								tilino = '$yhtiorow[kassaale]',
								tapvm = '$maksupvm',
								summa = $summa * -1,
								vero = '$tiliointirow[vero]',
								selite = '$selite',
								lukko = '',
								laatija = '$kukarow[kuka]',
								laadittu = now()";
					$xresult = mysql_query($query) or pupe_error($query);
					$isa = mysql_insert_id ($link); // N�in l�yd�mme t�h�n liittyv�t alvit....

					if ($tiliointirow['vero'] != 0) {
						$query = "	INSERT into tiliointi set
									yhtio ='$kukarow[yhtio]',
									ltunnus = '$laskurow[tunnus]',
									tilino = '$yhtiorow[alv]',
									tapvm = '$maksupvm',
									summa = $alv * -1,
									vero = '',
									selite = '$selite',
									lukko = '1',
									laatija = '$kukarow[kuka]',
									laadittu = now(),
									aputunnus = $isa";
						$xresult = mysql_query($query) or pupe_error($query);
					}
				}
				//Hoidetaan mahdolliset py�ristykset
				if ($totkasumma != $laskurow[maksukasumma]) {
					$query = "UPDATE tiliointi SET summa = summa - $totkasumma + $laskurow[maksukasumma]
							WHERE tunnus = '$isa' and yhtio='$kukarow[yhtio]'";
					$xresult = mysql_query($query) or pupe_error($query);
				}
			}

			/* Valuutta-ero (toistaiseksi vain EUROja)
			if ($trow[13] != $kurssi) {
				$summa = $laskurow[maksusumma] - $laskurow[vietysumma];
				if (($laskurow[alatili] == 'K')  && ($laskurow[maksukasumma] != 0)) {
					$summa = $summa - ($laskurow[maksukasumma] - $laskurow[vietykasumma]);
				}
				if (round($summa,2) != 0) {
					$query = "INSERT into tiliointi set
								yhtio ='$kukarow[yhtio]',
								ltunnus = '$laskurow[tunnus]',
								tilino = '$yhtiorow[1]',
								tapvm = '$mav-$mak-$map',
								summa = $summa,
								vero = 0,
								lukko = '',
								laatija = '$kukarow[kuka]',
								laadittu = now()";

					$xresult = mysql_query($query) or pupe_error($query);
				}
			}
			*/
			
			// Ostovelat
			$query = "	INSERT into tiliointi set
						yhtio ='$kukarow[yhtio]',
						ltunnus = '$laskurow[tunnus]',
						tilino = '$yhtiorow[ostovelat]',
						tapvm = '$maksupvm',
						summa = '$laskurow[vietysumma]',
						vero = 0,
						selite = '$selite',
						lukko = '',
						laatija = '$kukarow[kuka]',
						laadittu = now()";
			$xresult = mysql_query($query) or pupe_error($query);

			// Rahatili = selvittely
			$query = "	INSERT into tiliointi set
						yhtio ='$kukarow[yhtio]',
						ltunnus = '$laskurow[tunnus]',
						tilino = '$yhtiorow[selvittelytili]',
						tapvm = '$maksupvm',
						summa = -1 * ($laskurow[maksusumma] - $laskurow[maksukasumma]),
						vero = 0,
						selite = '$selite',
						lukko = '',
						laatija = '$kukarow[kuka]',
						laadittu = now()";
			$xresult = mysql_query($query) or pupe_error($query);

			$query = "	UPDATE lasku set
						tila = 'Y',
						mapvm = '$maksupvm',
						maksu_kurssi = $kurssi
						WHERE tunnus='$laskurow[tunnus]'";
			$xresult = mysql_query($query) or pupe_error($query);
			echo t("Lasku merkitty suoritetuksi!")."<br>";
		}
		$tee = 'S';
	}

	if ($tee == 'H') {
		if ($poikkeus=='on') $muutamaksupaiva = ", olmapvm = now()";
		else $muutamaksupaiva = ", olmapvm = if(now()<=kapvm,kapvm,if(now()<=erpcm,erpcm,now()))";

		if ($eipankkiin=='on') $tila = 'Q'; else $tila = 'P';

		$query = "	UPDATE lasku set
					maksaja = '$kukarow[kuka]',
					maksuaika = now(),
					maksu_kurssi = '$trow[0]',
					maksu_tili = '$tili',
					tila = '$tila'
					$muutamaksupaiva
					$alatila
					WHERE tunnus='$tunnus' and yhtio = '$kukarow[yhtio]' and tila='M'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_affected_rows() != 1) { // Jotain meni pieleen
			echo "System error Debug --> $query<br>";
			
			require ("inc/footer.inc");
			exit;
		}
		$query = "	UPDATE yriti set
					maksulimitti = maksulimitti - $trow[summa]
					WHERE tunnus = '$oltilrow[tunnus]' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = 'S';
	}

	if ($tee == 'DP') { // Perutaan maksuun meno
		$query = "	SELECT *, if(alatila='K', summa - kasumma, summa) usumma
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$lasku'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t('Lasku katosi tai se ehdittiin juuri siirt�� pankkiin')."</font><br>";
		}
		else {
			$trow=mysql_fetch_array ($result);

			//Hyvityslasku --> vastaava m��r� rahaa on oltava veloituspuolella
			if ($trow['usumma'] > 0) { 
				if (strtoupper($trow['maa']) == 'FI') {
					$query = "	SELECT sum(if(alatila='K', summa - kasumma, summa)) summa 
								FROM lasku
								WHERE yhtio='$kukarow[yhtio]' 
								and tila='P' 
								and olmapvm = '$trow[olmapvm]' 
								and maksu_tili = '$trow[maksu_tili]' 
								and maa = 'fi' 
								and tilinumero='$trow[tilinumero]'
								and maksaja = '$kukarow[kuka]' 
								and tunnus != '$lasku'";
				}
				else {
					$query = "	SELECT sum(if(alatila='K', summa - kasumma, summa)) summa 
								FROM lasku
								WHERE yhtio='$kukarow[yhtio]' 
								and tila='P' 
								and olmapvm = '$trow[olmapvm]' 
								and maksu_tili = '$trow[maksu_tili]' 
								and maa <> 'fi'
								and valkoodi = '$trow[valkoodi]'
								and ultilno = '$trow[ultilno]'
								and swift = '$trow[swift]'
								and pankki1 = '$trow[pankki1]'
								and pankki2 = '$trow[pankki2]'
								and pankki3 = '$trow[pankki3]'
								and pankki4 = '$trow[pankki4]'
								and maksaja = '$kukarow[kuka]'
								and tunnus != '$lasku'";
				}
			
				$result = mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($result) != 1) {
					echo "<b>".t("Hyvityshaulla ei l�ytynyt mit��n")."</b>$query";
					
					require ("inc/footer.inc");
					exit;
				}

				$veloitusrow=mysql_fetch_array ($result);

				if ($veloitusrow['summa'] < 0) {
					echo "<font class='error'>".t("Jos poistat t�m�n laskun maksatuksesta, on asiakkaalle valittu liikaa hyvityksi�.")." ($veloitusrow[summa])</font><br>";
					$tee = 'DM';
				}
			}
			if ($tee == 'DP') {
				$query = "	UPDATE lasku set
							maksaja = '',
							maksuaika = '0000-00-00',
							maksu_kurssi = '0',
							maksu_tili = '',
							tila = 'M',
							alatila = '',
							olmapvm=if(kapvm<now(),if(kapvm='0000-00-00',erpcm,kapvm),erpcm)
							WHERE tunnus='$lasku' and yhtio = '$kukarow[yhtio]' and tila='P'";
				$updresult = mysql_query($query) or pupe_error($query);
				
				if (mysql_affected_rows() != 1) { // Jotain meni pieleen
					echo "System error Debug --> $query<br>";
					
					require ("inc/footer.inc");
					exit;
				}
				
				$query = "	UPDATE yriti set
							maksulimitti = maksulimitti + $trow[usumma]
							WHERE tunnus = '$trow[maksu_tili]' 
							and yhtio = '$kukarow[yhtio]'";
				$updresult = mysql_query($query) or pupe_error($query);
				$tee = 'DM';
			}
		}
	}

	//Maksetaan nipussa
	if ($tee == "NK" or $tee == "NT" or $tee == "NV") {
		if ($oltilrow['tunnus'] == 0) {
			echo "Maksutili on kateissa! Systeemivirhe!";
			
			require ("inc/footer.inc");
			exit;
		}
		if ($tee != "NV") {
			$valinta = "<=" ; // Oletetaan kaikki er��ntyneet
			if ($tee == "NT") $valinta = "=";

			$query = "	SELECT valuu.kurssi, round(if(kapvm>=now(),summa-kasumma,summa) * valuu.kurssi,2) summa,
						lasku.nimi, lasku.tunnus
						FROM lasku, valuu
						WHERE lasku.valkoodi = valuu.nimi and
						valuu.yhtio = '$kukarow[yhtio]' and
						lasku.yhtio = valuu.yhtio and
						summa > 0 and tila = 'M' and olmapvm $valinta now()
						ORDER BY olmapvm, summa  desc";
		}
		else {		
			if ($valuu!='') $lisa = " valkoodi = '" . $valuu ."'";
			elseif ($nimihaku!='') $lisa = " lasku.nimi like '%" . $nimihaku ."%'";
			else {
				$lisa = " olmapvm = '" . $erapvm ."'";
				if ($kaikki == 'on') {
					$lisa = " olmapvm <= '" . $erapvm ."'";
				}
			}
			$query = "	SELECT valuu.kurssi, round(if(kapvm>=now(),summa-kasumma,summa) * valuu.kurssi,2) summa,
						lasku.nimi, lasku.tunnus
						FROM lasku, valuu
						WHERE lasku.valkoodi = valuu.nimi and
						valuu.yhtio = '$kukarow[yhtio]' and
						lasku.yhtio = valuu.yhtio and
						summa > 0 and tila = 'M' and $lisa
						ORDER BY olmapvm, summa  desc";
		}
		$result = mysql_query($query) or pupe_error($query);
		
		printf(t('Maksan %d laskua')."<br>",mysql_num_rows($result));
		
		while ($tiliointirow=mysql_fetch_array ($result)) {
			$query = "SELECT maksulimitti FROM yriti WHERE yhtio='$kukarow[yhtio]' and tunnus='$oltilrow[tunnus]'";
			$yrires = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($yrires) != 1) {
				echo "Maksutili katosi! Systeemivirhe!";
				
				require ("inc/footer.inc");
				exit;
			}
			
			$mayritirow=mysql_fetch_array ($yrires);
			
			if ($mayritirow['maksulimitti'] < $tiliointirow['summa']) {
				echo "<br><font class='error'>".t("Maksutilin limitti ylittyi! Laskujen maksu keskeytettiin")."</font>";
				break;
			}
			
			$query = "	UPDATE lasku set
						maksaja = '$kukarow[kuka]',
						maksuaika = now(),
						maksu_kurssi = '$tiliointirow[kurssi]',
						maksu_tili = '$oltilrow[tunnus]',
						tila = 'P',
						alatila = if(kapvm>=now(),'K',''),
						olmapvm = if(now()<=kapvm,kapvm,if(now()<=erpcm,erpcm,now()))
						WHERE tunnus='$tiliointirow[tunnus]' and yhtio = '$kukarow[yhtio]' and tila='M'";
			$updresult = mysql_query($query) or pupe_error($query);
			
			if (mysql_affected_rows() != 1) { // Jotain meni pieleen
				echo "System error Debug --> $query<br>";
				
				require ("inc/footer.inc");
				exit;
			}
			
			$query = "	UPDATE yriti set
						maksulimitti = maksulimitti - $tiliointirow[summa]
						WHERE tunnus = '$oltilrow[0]' and yhtio = '$kukarow[yhtio]'";
			$updresult = mysql_query($query) or pupe_error($query);
			echo "($tiliointirow[nimi], $tiliointirow[summa]) ";
		}
		echo "<br>";
		$tee = 'DM';
	}
	
	// Jaaha poistamme laskun!
	if ($tee == 'D' and $oikeurow['paivitys'] == '1') {

		$query = "	SELECT *
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' 
					and tunnus = '$tunnus'
					and h1time = '0000-00-00 00:00:00'
					and h2time = '0000-00-00 00:00:00'
					and h3time = '0000-00-00 00:00:00'
					and h4time = '0000-00-00 00:00:00'
					and h5time = '0000-00-00 00:00:00'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t('lasku kateissa') . "$tunnus</font>";
			
			require ("inc/footer.inc");
			exit;
		}

		$trow = mysql_fetch_array ($result);

		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Poisti laskun")."<br>" . $trow['comments'];

		// Ylikirjoitetaan tili�innit
		$query = "	UPDATE tiliointi SET
					korjattu = '$kukarow[kuka]',
					korjausaika = now()
					WHERE ltunnus = '$tunnus' and
					yhtio = '$kukarow[yhtio]' and
					tiliointi.korjattu = ''";
		$result = mysql_query($query) or pupe_error($query);

		// Merkataan lasku poistetuksi
		$query = "	UPDATE lasku SET
					alatila = 'H',
					tila = 'D',
					comments = '$komm'
					WHERE tunnus = '$tunnus' and
					yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<font class='error'>".sprintf(t('Poistit %s:n laskun tunnuksella %d.'), $trow['nimi'],$tunnus)."</font><br><br>";

		$tunnus = '';
		$tee 	= 'S';
	}

	if ($tee == 'W') {
		// Ei oletustili�, jotern annetaan k�ytt�j�n valita
		echo "".t("Tilien maksulimiitit")."<hr>";
		
		$query = "	SELECT tunnus, concat(nimi, ' (', tilino, ')') tili, maksulimitti
	                 FROM yriti 
					WHERE yhtio='$kukarow[yhtio]' 
					and maksulimitti > 0 and factoring = ''";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			echo "<font class='head'>".t("Sinulla ei ole yht��n tili�, jolla olisi limiitti�")."!<p>".t("K�y p�ivitt�m�ss� limiitit yrityksen pankkitileille")."!</font><hr>";
			
			require ("inc/footer.inc");
			exit;
		}
		echo "<form action='maksa.php' method='post'>
				<input type='hidden' name='tee' value='O'>";
		echo "<table><tr>";
		
		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
        }

		echo "<th>".t("maksutili")."</th></tr>";
		
		while ($yritirow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=1; $i<mysql_num_fields($result); $i++) {
				echo "<td>$yritirow[$i]</td>";
			}
			echo "<td><input type = 'radio' name = 'oltili' value = '$yritirow[tunnus]'></td></tr>";
		}
		echo "</table><br><input type='submit' value='".t("valitse")."'><br></form>";
		$tee = "";
	}
	else {
		// eli n�ytet��n tili jolta maksetaan ja sen saldo
		//echo t("maksutili")."<hr>";
		$query = "	SELECT tunnus, nimi, maksulimitti
				 	FROM yriti
				 	WHERE yhtio='$kukarow[yhtio]' 
					and tunnus = '$oltilrow[tunnus]' and factoring = ''";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) != 1) {
			echo t("Etsin tili�")." '$oltilrow[0]', ".t("mutta sit� ei l�ytynyt")."!";
			
			require ("inc/footer.inc");
			exit;
		}
		echo "<table><tr>";
		
		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		
		echo "<th></th><th></th>";
		echo "<th>".t("Kaikki er��ntyneet")."</th><th></th><th>".t("T�n��n er��ntyv�t")."</th><th></th></tr>";

		$yritirow=mysql_fetch_array ($result);
		for ($i=1; $i<mysql_num_fields($result); $i++) {
				echo "<td>$yritirow[$i]</td>";
		}
		echo "
		<form action = 'maksa.php' method='post'>
		<input type='hidden' name = 'tee' value='X'><td>
		<input type='Submit' value='".t("Vaihda maksutili�")."'>
		</td></form>";

		echo "
		<form action = 'maksa.php' method='post'>
		<input type='hidden' name = 'tee' value='Z'><td>
		<input type='Submit' value='".t("P�ivit� maksup�iv�t")."'>
		</td></form>";

		// Lis�t��n t�h�n viel� mahdollisuus maksaa kaikki er��ntyneet laskut tai t�n��n er��ntyv�t
		$query = "	SELECT sum(round(if(kapvm>=now(),summa-kasumma,summa)*kurssi,2)),
					sum(round(if(olmapvm=now(),if(kapvm>=now(),summa-kasumma,summa),0)*kurssi, 2)),
					count(*),
					sum(if(olmapvm=now(),1,0))
					FROM lasku, valuu
					WHERE lasku.yhtio='$yhtiorow[yhtio]' and summa > 0 and
					tila = 'M' and olmapvm <= now() and valuu.yhtio=lasku.yhtio and valuu.nimi=lasku.valkoodi";
		$result = mysql_query($query) or pupe_error($query);
		$sumrow=mysql_fetch_array ($result);

		echo "<td>$sumrow[0] ($sumrow[2])</td>";
		
		if ($sumrow[0] > 0) {
			if ($yritirow['maksulimitti'] < $sumrow[0]) {
				echo "<td><font class='error'>".t("Saldo ei riit�")."</font></td>";
			}
			else {
				echo "<form action = 'maksa.php' method='post'>
					<input type='hidden' name = 'tee' value='NK'>
					<input type='hidden' name = 'tili' value='$oltilrow[0]'><td>
					<input type='Submit' value='".t('Maksa')."'></td>
					</form>";
			}
		}
		else {
				echo "<td><font class='message'>".t("Ei maksuja")."</font></td>";
		}

		echo "<td>$sumrow[1] ($sumrow[3])</td>";
		
		if ($sumrow[1] > 0) {
			if ($yritirow['maksulimitti'] < $sumrow[1]) {
				echo "<td><font class='error'>".t("Saldo ei riit�")."</font></td>";
			}
			else {
				echo "<form action = 'maksa.php' method='post'>
					<input type='hidden' name = 'tee' value='NT'>
					<input type='hidden' name = 'tili' value='$oltilrow[0]'><td>
					<input type='Submit' value='".t('Maksa')."'></td>
					</form>";
			}
		}
		else {
				echo "<td><font class='message'>".t("Ei maksuja")."</font></td>";
		}
		echo "</tr></table>";

		if ($tee == '') {
			$tee = "V";
		}
	}

	if ($tee == 'DM') { //N�ytet��n kaikki omat maksatukseen valitut
		$query = "	SELECT maksaja, yriti.nimi, lasku.nimi,
			  		olmapvm, lasku.valkoodi,
					round(if(alatila='K',(summa - kasumma) * kurssi, summa * kurssi),2) 'kotivaluutassa',
					if(alatila='k','*','') kale, lasku.tunnus peru
					FROM lasku, valuu, yriti
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					valuu.yhtio = lasku.yhtio and
					valuu.yhtio = yriti.yhtio and
					lasku.maksu_tili = yriti.tunnus and
					tila = 'P' and
					lasku.valkoodi = valuu.nimi and maksaja = '$kukarow[kuka]'
					ORDER BY maksu_tili, olmapvm, kotivaluutassa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
		 	echo "<font class='error'>".t('Pankkiin l�hetett�vi� laskuja ei l�ydy')."</font><br>";
		}
		else {
			echo "<br>".t("Pankkiin l�htev�t maksut")."<hr>";
			$summa=0;
			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			while ($trow=mysql_fetch_array ($result)) {
		        $summa += $trow['kotivaluutassa'];
		        echo "<tr>";
		        for ($i=0; $i<mysql_num_fields($result); $i++) {
		        	if ($i==mysql_num_fields($result)-1) {
		        		echo "	<form action = 'maksa.php' method='post'>
								<input type='hidden' name = 'tee' value='DP'>
								<input type='hidden' name = 'lasku' value='$trow[peru]'>
								<td><input type='Submit' value='".t('�l� siirr�')."'>
								</td></form>";

		        	}
		        	else {
		        		if (mysql_field_name($result,$i) == 'olmapvm') {
		        			echo "<td><a href='muutosite.php?tee=E&tunnus=$trow[peru]'>$trow[$i]</a></td>";
		        		}
		        		else {
		        			echo "<td>$trow[$i]</td>";
		        		}
		        	}
		        }
		        echo "</tr>";
			}
			echo "<tr><td colspan='5'></td><td>$summa</td><td></td></tr>";
			echo "</table>";
		}
		$tee='V';
	}

	if ($tee == 'S') {

		if ($tapa == 'V') {
			$lisa = " valkoodi = '" . $valuu ."'";
		}
		if ($tapa == 'E') {
			$lisa = " olmapvm = '" . $erapvm ."'";
			
			if ($kaikki == 'on') {
				$lisa = " olmapvm <= '" . $erapvm ."'";
			}
		}
		if ($tapa == 'N') {
			$lisa = " lasku.nimi like '%" . $nimihaku ."%'";
		}
				
		$query = "	SELECT lasku.nimi,
			  		lasku.kapvm, lasku.erpcm, lasku.valkoodi,
					lasku.summa - lasku.kasumma 'kassa-alella',
					round((lasku.summa - lasku.kasumma) * valuu.kurssi,2) 'kotivaluutassa',
					lasku.summa, round(lasku.summa * valuu.kurssi,2) 'kotivaluutassa',
					lasku.ebid, lasku.tunnus, lasku.olmapvm, 
					if(lasku.maa='$yhtiorow[maa]',lasku.tilinumero, lasku.ultilno) tilinumero,
					h1time,
					h2time,
					h3time,
					h4time,
					h5time
					FROM lasku, valuu
					WHERE lasku.yhtio = '$kukarow[yhtio]' and
					valuu.yhtio = '$kukarow[yhtio]' and
					tila = 'M' and
					$lisa and
					lasku.valkoodi = valuu.nimi
					ORDER BY olmapvm, kotivaluutassa  desc";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
		 	echo "<font class='error'>".t("Haulla ei l�ytynyt yht��n laskua")."</font><br>";
		}
		else {
			// N�ytet��n valitut laskut
			echo "<br>".t("Maksuvalmiit laskut")."<hr>";
			echo "<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name = 'tili' value='$tili'>
				<input type='hidden' name = 'tee' value='NV'>
				<input type='hidden' name = 'kaikki' value='$kaikki'>
				<input type='hidden' name = 'valuu' value='$valuu'>
				<input type='hidden' name = 'erapvm' value='$erapvm'>
				<input type='hidden' name = 'nimihaku' value='$nimihaku'>
				<input type='Submit' value='".t('Maksa valitut veloituslaskut')."'>
				</form>";
			echo "<table><tr>";
			
			for ($i = 0; $i < mysql_num_fields($result)-8; $i++) {
				if ((mysql_field_name($result,$i) == "kapvm") or
						(mysql_field_name($result,$i) == "kassa-alella") or
						(mysql_field_name($result,$i) == "summa")) {
					echo "<th>" . t(mysql_field_name($result,$i))."<br>";
					$i++;
					echo t(mysql_field_name($result,$i))."</th>";
				}
				else
					echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th>".t("Maksatus")."</th></tr>";

			while ($trow=mysql_fetch_array ($result)) {
		        echo "<tr>";
		        for ($i=0; $i<mysql_num_fields($result)-8; $i++) { // ei n�ytet� tunnusta
					if (mysql_field_name($result,$i) == 'nimi') { // N�ytet��n nimi errorv�rill�, jos on hyvityksi� samalle asiakkaalle
						$query = "	SELECT count(*) 
									from lasku 
									WHERE yhtio = '$kukarow[yhtio]' 
									and tila = 'M' 
									and summa < 0 
									and if(lasku.maa='$yhtiorow[maa]',lasku.tilinumero, lasku.ultilno) = '$trow[tilinumero]'";
						$hyvitysresult = mysql_query($query) or pupe_error($query);
						$hyvitysrow=mysql_fetch_array ($hyvitysresult);
						
						if ($hyvitysrow[0] > 0)
							echo "<td><a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]'><font class='error'>$trow[$i]</font></a></td>";
						else
							echo "<td><a href = 'muutosite.php?tee=E&tunnus=$trow[tunnus]'>$trow[$i]</a></td>";
					}
					elseif (mysql_field_name($result,$i) == 'ebid') {
						if (strlen($trow[$i]) > 0) {
							$ebid = $trow[$i];
							require "inc/ebid.inc";
							echo "<td><a href='$url'>".t("N�yt� lasku")."</a></td>";
						}
						else {
							//	Onko kuva tietokannassa?
							echo "<td valign='top'>";
							$query = "select * from liitetiedostot where yhtio='{$kukarow[yhtio]}' and liitos='lasku' and liitostunnus='{$laskurow["tunnus"]}'";
							$liiteres=mysql_query($query) or pupe_error($query);
							if(mysql_num_rows($liiteres)>0) {
								while($liiterow=mysql_fetch_array($liiteres)) {
									echo "<a href='view.php?id={$liiterow["tunnus"]}'>{$liiterow["selite"]}</a><br>";
								}
							}
							else {
								echo t("Paperilasku");
							}
							echo "</td>";		
						}
					}
					else {
						//Laitetaan osa allekain (wow, mik� sotku)
						if ((mysql_field_name($result,$i) == "kapvm") or
							(mysql_field_name($result,$i) == "kassa-alella") or
							(mysql_field_name($result,$i) == "summa")) {
							echo "<td>";
							if ($trow[$i]!='') echo $trow[$i];
							$j=$i;
							$i++;
							if ((mysql_field_name($result,$j) == "kapvm") or
								($trow['valkoodi'] != $yhtiorow['valkoodi'])) {
								if ($trow[$i]!='') {
									if($trow[$j]!='') echo "<br>";
									echo "$trow[$i]";
								}
							}
							echo "</td>";
						}
						else
							echo "<td>$trow[$i]</td>";
					}
				}
				// Ok, mutta onko meill� varaa makssa kyseinen lasku???
				if ($trow[5] <= $yritirow[2]) {

					echo "<form action = 'maksa.php' method='post'>";
					echo "<td>";
					
					if (($trow[4] != $trow[6]) and ($trow['summa'] > 0)) {
						$ruksi='checked';
						
						if ($trow['kapvm'] < date("Y-m-d")) {
							$ruksi = ''; // Ooh, maksamme my�s�ss�
						}
						echo t("K�yt� kassa-ale")." <input type='Checkbox' name='kaale' $ruksi><br>";
					}
					
					if ($trow['olmapvm'] != date("Y-m-d")) {
						if ($trow['olmapvm'] < date("Y-m-d")) {
							echo "<font class='error'>".t("Er��ntynyt maksetaan heti")."</font><br>";
						}
						else {
							echo t("Maksetaan heti")."<input type='Checkbox' name='poikkeus'><br>";
						}
					}
					
					if ($trow['summa'] < 0) { //Hyvitykset voi hoitaa ilman pankkiinl�hetyst�
						echo t("�l� l�het� pankkiin")."<input type='Checkbox' name='eipankkiin'><br>";
					}

					echo "	<input type='hidden' name = 'tee' value='H'>
							<input type='hidden' name = 'tunnus' value='$trow[9]'>
							<input type='hidden' name = 'valuu' value='$valuu'>
							<input type='hidden' name = 'erapvm' value='$erapvm'>
							<input type='hidden' name = 'kaikki' value='$kaikki'>
							<input type='hidden' name = 'nimihaku' value='$nimihaku'>
							<input type='hidden' name = 'tapa' value='$tapa'>
							<input type='Submit' value='".t("Maksa")."'></form>";
						
					
					//Tutkitaan voidaanko lasku poistaa
					$query = "	select tunnus
								from lasku
								where yhtio='$kukarow[yhtio]' and tila='K' and vanhatunnus='$trow[tunnus]'";
					$delres2 = mysql_query($query) or pupe_error($query);
					
					if (mysql_num_rows($delres2) == 0 and $trow['h1time'] == '0000-00-00 00:00:00' and $trow['h2time'] == '0000-00-00 00:00:00' and $trow['h3time'] == '0000-00-00 00:00:00' and $trow['h4time'] == '0000-00-00 00:00:00' and $trow['h5time'] == '0000-00-00 00:00:00') {
						echo "	<form action = 'maksa.php' method='post' onSubmit = 'return verify()'>
								<input type='hidden' name='tee' value='D'>
								<input type='hidden' name = 'tunnus' value='$trow[9]'>
								<input type='hidden' name = 'valuu' value='$valuu'>
								<input type='hidden' name = 'tapa' value='$tapa'>
								<input type='hidden' name = 'erapvm' value='$erapvm'>
								<input type='hidden' name = 'kaikki' value='$kaikki'>
								<input type='hidden' name = 'nimihaku' value='$nimihaku'>
								<input type='hidden' name = 'tapa' value='$tapa'>
								<input type='Submit' value='".t("Poista lasku")."'>
								</form>";
					
						echo "	<SCRIPT LANGUAGE=JAVASCRIPT>
								function verify(){
									msg = '".t("Haluatko todella poistaa t�m�n laskun ja sen kaikki tili�innit? T�m� voi olla kirjanpitorikos!")."';
									return confirm(msg);
								}
							</SCRIPT>";
					}
					
					echo "</td>";
				}
				else {
					// ei ollutkaan varaa!!
					echo "<td>".t("Tilin limitti ei riit�")."!</td>";
				}
				echo "</tr>";
			}
			echo "</table>";
		}
		$tee = "V";
	}
	if ($tee == 'V') {

		echo "<br>".t("Etsi maksuvalmiita laskuja")."<hr>";
		// T�ll� ollaan, jos valitaan maksujen selailutapoja
		// Valuutoittain
		echo "<table><tr><form action = 'maksa.php' method='post'>
				<input type='hidden' name = 'tee' value='S'>
				<input type='hidden' name = 'tapa' value='V'>";
		echo "<td>".t("Valuutta")."</td>";
		echo "<td>";
		
		$query = "	SELECT valkoodi, count(*)
			  		FROM lasku
			  		WHERE yhtio = '$kukarow[yhtio]' and tila = 'M'
			  		GROUP BY valkoodi
			  		ORDER BY valkoodi";
		$result = mysql_query($query) or pupe_error($query);
		
		echo "<select name='valuu'>";
		
		while ($valuurow=mysql_fetch_array ($result)) {
			echo "<option value='$valuurow[0]'>$valuurow[0] ($valuurow[1])";
		}
		
		echo "</select></td>";
		echo "<td></td><td><input type='Submit' value='".t("valitse")."'></td></form>";
		echo "<form action = 'maksa.php' method='post'><td>
					<input type='hidden' name = 'tee' value='DM'>
					<input type='Submit' value='".t('N�yt� jo valitut laskut')."'>
					</td></form>";
		echo "</tr>";
		echo "<tr><form action = 'maksa.php' method='post'>
				<input type='hidden' name = 'tee' value='S'>
				<input type='hidden' name = 'tapa' value='E'><td>";
		
		$query = "	SELECT olmapvm, count(*)
					FROM lasku
					WHERE yhtio = '$kukarow[yhtio]' and tila = 'M'
					GROUP BY olmapvm
					ORDER BY olmapvm";
		$result = mysql_query($query) or pupe_error($query);
		
		echo "".t("Er�pvm")."</td>";
		echo "<td><select name='erapvm'>";
		
		while ($laskurow=mysql_fetch_array ($result)) {
			echo "<option value = '$laskurow[0]'>$laskurow[0] ($laskurow[1])";
		}
		
		echo "</select></td>";
		echo "<td>".t("N�yt� my�s vanhemmat")." <input type='Checkbox' name='kaikki'></td>";
		echo "<td><input type='Submit' value='".t("valitse")."'></td></form><td></td></tr>";
		echo "<tr><form action = 'maksa.php' method='post'>
				<input type='hidden' name = 'tee' value='S'>
				<input type='hidden' name = 'tapa' value='N'><td>";
		echo t("Etsi nimell�")."</td><td><input type='text' name='nimihaku' size=''8'></td><td></td>";
		echo "<td><input type='Submit' value='".t("valitse")."'></td></form><td></td></tr>";
		echo "</table>";
	}
	
	require ("inc/footer.inc");

?>