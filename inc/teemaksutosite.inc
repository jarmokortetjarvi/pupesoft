<?php

	// Kirjoitetaan laskulle maksutili�inti
	$selite = str_replace ("'", " ", $vientiselite); // Poistaa SQL-virheen mahdollisuuden
	$tpv 	= substr($pvm,0,2);
	$tpk 	= substr($pvm,2,2);
	$tpp 	= substr($pvm,4,2);
	if ($tpv < 1000) $tpv += 2000;

	$teemaksutosite = TRUE;

	// Haetaan ostovelkatili
	$query = "	SELECT tilino, kustp, kohde, projekti
				FROM tiliointi
				WHERE ltunnus 	= '$trow[tunnus]'
				and yhtio 		= '$yritirow[yhtio]'
				and tapvm 		= '$trow[tapvm]'
				and abs(summa + $trow[vietysumma]) <= 0.02
				and tilino in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
				and korjattu 	= ''";
	$result = pupe_query($query);

	if (mysql_num_rows($result) == 1) {
		$ostovelkarow = mysql_fetch_assoc($result);
	}
	else {
		echo "<font class='error'>".t("VIRHE: Ostovelkatilin m��rittely ep�onnistui")."!<br>".t("Summaa ei kirjattu")."!</font><br>";
		$teemaksutosite = FALSE;
	}

	// Haetaan kulutili�nti
	$query = "	SELECT *
				FROM tiliointi
				WHERE ltunnus 	= '$trow[tunnus]'
				and yhtio 		= '$yritirow[yhtio]'
				and tapvm 		= '$trow[tapvm]'
				and tilino not in ('$yhtiorow[ostovelat]', '$yhtiorow[alv]', '$yhtiorow[konserniostovelat]', '$yhtiorow[matkalla_olevat]', '$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[varastonmuutos_inventointi]', '$yhtiorow[varastonmuutos_epakurantti]')
				and korjattu 	= ''
				order by tunnus";
	$yresult = pupe_query($query);

	if (mysql_num_rows($yresult) == 0) {
		echo "<font class='error'>".t("VIRHE: Kulutilin m��rittely ep�onnistui")."!<br>".t("Summaa ei kirjattu")."!</font><br>";
		$teemaksutosite = FALSE;
	}

	if ($teemaksutosite) {

		// Kassa-ale
		if ($trow['alatila'] == 'K' and $trow['vietykasumma'] != 0) {

			// Kassa-alessa on huomioitava alv, joka voi olla useita vientej�
			echo "<font class='message'>".t("Kirjaan kassa-alennusta yhteens�")." $trow[vietykasumma]</font><br>";

			$totkasumma = 0;

			mysql_data_seek($yresult, 0);

			while ($tiliointirow = mysql_fetch_assoc($yresult)) {

				// Kuinka paljon on t�m�n viennin osuus
				$summa = round(($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $trow['vietysumma']) * $trow['vietykasumma'], 2);
				$alv = 0;

				echo "<font class='message'>".t("Kirjaan kassa-alennusta")." $summa</font><br>";

				if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
					$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
					$summa -= $alv;
				}

				$totkasumma += $summa + $alv;

				// Kassa-ale
				$query = "	INSERT into tiliointi set
							yhtio 		= '$yritirow[yhtio]',
							ltunnus 	= '$trow[tunnus]',
							tilino 		= '$yhtiorow[kassaale]',
							kustp 		= '$tiliointirow[kustp]',
							kohde 		= '$tiliointirow[kohde]',
							projekti 	= '$tiliointirow[projekti]',
							tapvm 		= '$tpv-$tpk-$tpp',
							summa 		= $summa * -1,
							vero 		= '$tiliointirow[vero]',
							lukko 		= '',
							laatija 	= 'tiliote',
							laadittu 	= now()";
				$xresult = pupe_query($query);
				$isa = mysql_insert_id(); // N�in l�yd�mme t�h�n liittyv�t alvit....

				if ($tiliointirow['vero'] != 0) {
					// Kassa-alen alv
					$query = "	INSERT into tiliointi set
								yhtio 		= '$yritirow[yhtio]',
								ltunnus 	= '$trow[tunnus]',
								tilino 		= '$yhtiorow[alv]',
								kustp 		= 0,
								kohde 		= 0,
								projekti 	= 0,
								tapvm 		= '$tpv-$tpk-$tpp',
								summa 		= $alv * -1,
								vero 		= 0,
								selite 		= '$selite',
								lukko 		= '1',
								laatija 	= 'tiliote',
								laadittu 	= now(),
								aputunnus 	= $isa";
					$xresult = pupe_query($query);
				}
			}

			//Hoidetaan mahdolliset py�ristykset
			$heitto = round($totkasumma - $trow["vietykasumma"], 2);

			if (abs($heitto) >= 0.01) {
				echo "<font class='message'>".t("Joudun py�rist�m��n kassa-alennusta")."</font><br>";

				$query = "	UPDATE tiliointi
							SET summa = summa + $heitto
							WHERE tunnus = '$isa'
							and yhtio 	 = '$yritirow[yhtio]'";
				$xresult = pupe_query($query);
			}
		}

		// Valuutta-ero
		if ($trow['vienti_kurssi'] != $kurssi) {

			$vesumma = ($maara*-1) - $trow['vietysumma'];

			if ($trow['alatila'] == 'K' and $trow['vietykasumma'] != 0) {
				$vesumma = round(($maara*-1) - ($trow['vietysumma'] - $trow['vietykasumma']),2);
			}

			if (round($vesumma, 2) != 0) {
				echo "<font class='message'>".t("Kirjaan valuuttaeroa yhteens�")." $vesumma</font><br>";

				$totvesumma = 0;

				mysql_data_seek($yresult, 0);

				while ($tiliointirow = mysql_fetch_assoc($yresult)) {
					// Kuinka paljon on t�m�n viennin osuus
					$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) / $trow['vietysumma'] * $vesumma, 2);

					echo "<font class='message'>".t("Kirjaan valuuttaeroa")." $summa</font><br>";

					if (round($summa, 2) != 0) {
						// Valuuttaero
						$query = "	INSERT into tiliointi set
									yhtio 		= '$yritirow[yhtio]',
									ltunnus 	= '$trow[tunnus]',
									tilino 		= '$yhtiorow[valuuttaero]',
									kustp 		= '$tiliointirow[kustp]',
									kohde 		= '$tiliointirow[kohde]',
									projekti 	= '$tiliointirow[projekti]',
									tapvm 		= '$tpv-$tpk-$tpp',
									summa 		= $summa,
									vero 		= 0,
									lukko 		= '',
									laatija 	= 'tiliote',
									laadittu 	= now()";
						$xresult = pupe_query($query);
						$isa = mysql_insert_id ($link);

						$totvesumma += $summa;
					}
				}

				// Hoidetaan mahdolliset py�ristykset
				if ($totvesumma != $vesumma) {
					echo "<font class='message'>".t("Joudun py�rist�m��n valuuttaeroa")."</font><br>";

					$query = "	UPDATE tiliointi
								SET summa = summa - $totvesumma + $vesumma
								WHERE tunnus = '$isa' and yhtio='$yritirow[yhtio]'";
					$xresult = pupe_query($query);
				}
			}
		}

		list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yritirow["oletus_rahatili"]);

		// Rahatili
		$query = "	INSERT into tiliointi set
					yhtio 		= '$yritirow[yhtio]',
					ltunnus 	= '$trow[tunnus]',
					tilino 		= '$yritirow[oletus_rahatili]',
					kustp    	= '{$kustp_ins}',
					kohde	 	= '{$kohde_ins}',
					projekti 	= '{$projekti_ins}',
					tapvm 		= '$tpv-$tpk-$tpp',
					summa 		= '$maara',
					vero 		= 0,
					lukko 		= '',
					laatija 	= 'tiliote',
					laadittu 	= now()";
		$result = pupe_query($query);

		// Ostovelat, tarkenteet kopsataan alkuper�iseslt� tili�innilt�
		$query = "	INSERT into tiliointi set
					yhtio 		= '$yritirow[yhtio]',
					ltunnus 	= '$trow[tunnus]',
					tilino		= '$ostovelkarow[tilino]',
					kustp		= '$ostovelkarow[kustp]',
					kohde		= '$ostovelkarow[kohde]',
					projekti	= '$ostovelkarow[projekti]',
					tapvm 		= '$tpv-$tpk-$tpp',
					summa 		= '$trow[vietysumma]',
					vero 		= 0,
					lukko 		= '',
					laatija 	= 'tiliote',
					laadittu 	= now()";
		$result = pupe_query($query);

	    $query = "	UPDATE tiliotedata
					SET kasitelty = now(),
					tiliointitunnus = ".mysql_insert_id($link)."
					WHERE tunnus='$tiliotedataid'";
		$dummyresult = pupe_query($query);

		$query = "	UPDATE tiliotedata
					SET kuitattu = 'tiliote',
					kuitattuaika = now()
					WHERE yhtio = '$yritirow[yhtio]'
					and perheid = '$tiliotedatape'";
		$dummyresult = pupe_query($query);

		// Merkataan k�sitellyiksi & lis�t��n linkki
		echo "<font class='message'>*** ".t("laskuun lis�tty maksutili�inti")." ***</font><br>";

		//Lasku maksetuksi
		$kurssi += 0;

		if ($kurssi == 0) {
			$kurssi = 1;
		}

		$query = "	UPDATE lasku
					set tila = 'Y',
					maksu_kurssi = $kurssi,
					mapvm = '$tpv-$tpk-$tpp'
					WHERE tunnus = '$trow[tunnus]'";
		$result = pupe_query($query);

		echo "<font class='message'>*** ".t("lasku suoritettu")." ***</font><br>";

	}
	else {
		// P�ivitet��n virhetapauksissa kasitelty nollaksi jotta t�m� k�sitell��n sit seuraavan kerran kun tiliotetta katsotaan!!!!
		$query = "	UPDATE tiliotedata
					SET kasitelty = '0000-00-00 00:00:00'
					WHERE tunnus = '$tiliotedataid'";
		$dummyresult = pupe_query($query);
	}

?>