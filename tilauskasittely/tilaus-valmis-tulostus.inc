<?php
	//sis��n halutaan $laskurow jossa on tulostettavan tilauksen tiedot
	//jos tilauksia on useita $laskurow muuttujassa on jonkun tilauksen tiedot

	// katotaan ihan aluksi, ett� meill� on edellytykset tulostukselle...
	$query  = "SELECT * from varastopaikat where yhtio='$kukarow[yhtio]'";
	$prires = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($prires) != 0) {
		$prirow = mysql_fetch_array($prires);

		$query  = "SELECT * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$prirow[printteri1]'";
		$kirres = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($kirres)==0)
			die ("<font class='error'>VIRHE: Varaston $prirow[nimitys] tulostinta ei l�ydy. L�hetteit� ei voida tulostaa...</font>");
	}
	else {
		die ("<font class='error'>Yht��n varastoa ei ole m��ritelty. L�hetteit� ei voida tulostaa...</font>");
	}

	// tarkistetan tulostuksessa syntyvi� virheit�
	$virheellinen	= "";
	$returnvalue	= 0;
	$komento		= "";

	// emuloidaan transactioita mysql LOCK komennolla
	$query = "LOCK TABLES liitetiedostot READ, tilausrivi WRITE, tilausrivi AS t2 READ, tilausrivin_lisatiedot AS tlt2 READ, tilausrivi AS tilausrivi_osto READ, tilausrivi AS tilausrivi_myynti READ, sarjanumeroseuranta READ, tilausrivi AS t3 READ, lasku WRITE, sanakirja WRITE, avainsana READ, avainsana as a READ, avainsana as b READ, varaston_tulostimet READ, tuotepaikat READ, tuote READ, tuoteperhe READ, maksuehto READ, varastopaikat READ, kirjoittimet READ, asiakas READ, kuka WRITE, asiakaskommentti READ, tuotteen_toimittajat READ, tuotteen_avainsanat READ, pankkiyhteystiedot READ, toimitustapa READ, yhtion_toimipaikat READ, yhtion_parametrit READ, tuotteen_alv READ, maat READ, rahtisopimukset READ, rahtisopimukset AS rahtisopimukset2 READ, pakkaamo WRITE, avainsana as avainsana_kieli READ, lasku as vanha_lasku READ, varaston_tulostimet as vanha_varaston_tulostimet READ, yhtio READ";

	if ($yhtiorow['kerayserat'] == 'K' and isset($tullaan_kerayserasta) and trim($tullaan_kerayserasta) != '' and isset($keraysvyohyke) and trim($keraysvyohyke) != '') {
		$query .= ", keraysvyohyke READ, varaston_hyllypaikat AS vh READ, kerayserat READ";
	}

	$res   = mysql_query($query) or pupe_error($query);

	if ($toim == 'VASTAANOTA_REKLAMAATIO' and $yhtiorow['reklamaation_kasittely'] == 'U') {
		$queryc = "	SELECT sum(if(tila='C' and alatila='B', 1, 0)) ok, count(*) kaikki
					FROM lasku
					WHERE tunnus in ($tilausnumeroita)
					and yhtio	= '$kukarow[yhtio]'";
	}
	else {
		$queryc = "	SELECT sum(if(tila='N' and alatila='A', 1, 0)) ok, count(*) kaikki
					FROM lasku
					WHERE tunnus in ($tilausnumeroita)
					and yhtio	= '$kukarow[yhtio]'";
	}
	$chk_result = mysql_query($queryc) or pupe_error($queryc);
	$chk_row = mysql_fetch_array($chk_result);

	// Katsotaan, ett� t�m� ker�yslista ei ole jo tulostettu
	if ((int) $chk_row["ok"] == (int) $chk_row["kaikki"] and (int) $chk_row["ok"] != 0) {

		$ei_pakkaamoa = !isset($ei_pakkaamoa) ? '' : $ei_pakkaamoa;

		$laskurow['pakkaamo'] = pakkaamo($tilausnumeroita, "yes", $ei_pakkaamoa);

		//ker�yslistan tulostusta varten
		require_once ("tulosta_lahete_kerayslista.inc");

		//haetaan asiakkaan tiedot
		$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
					FROM asiakas
					WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
		$riresult = mysql_query($query) or pupe_error($query);
		$asrow = mysql_fetch_array($riresult);

		$query = "	SELECT ulkoinen_jarjestelma
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = '$laskurow[varasto]'";
		$result = pupe_query($query);
		$varastorow = mysql_fetch_assoc($result);

		$select_lisa 		= "";
		$where_lisa 		= "";
		$lisa1 				= "";
		$pjat_sortlisa 		= "";
		$kerayslistatyyppi 	= "";

		if ($varastorow["ulkoinen_jarjestelma"] == "G") {
			$kerayslistatyyppi = "EXCEL2";
		}
		elseif ($varastorow["ulkoinen_jarjestelma"] == "C") {
			$kerayslistatyyppi = "EXCEL1";
		}

		$tyyppi = "";

		// ker�yslistalle ei oletuksena tulosteta saldottomia tuotteita
		if ($yhtiorow["kerataanko_saldottomat"] == '') {
			$lisa1 = " and tuote.ei_saldoa = '' ";
		}

		if ($laskurow["tila"] == "V") {
			$sorttauskentta = generoi_sorttauskentta($yhtiorow["valmistus_kerayslistan_jarjestys"]);
			$order_sorttaus = $yhtiorow["valmistus_kerayslistan_jarjestys_suunta"];

			if ($yhtiorow["valmistus_kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

			// Summataan rivit yhteen (HUOM! unohdetaan kaikki perheet!)
			if ($yhtiorow["valmistus_kerayslistan_jarjestys"] == "S") {
				$select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
				$where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
			}
		}
		else {
			if ($yhtiorow['kerayserat'] == 'K' and isset($tullaan_kerayserasta) and trim($tullaan_kerayserasta) != '' and isset($keraysvyohyke) and trim($keraysvyohyke) != '') {
				$query = "	SELECT keraysjarjestys
							FROM keraysvyohyke
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$keraysvyohyke}'";
				$ker_res = mysql_query($query) or pupe_error($query);
				$ker_row = mysql_fetch_assoc($ker_res);

				if ($ker_row['keraysjarjestys'] == 'P') {
					$sorttauskentta = 'tuote.tuotemassa as sorttauskentta';
				}
				elseif ($ker_row['keraysjarjestys'] == 'V') {
					$sorttauskentta = "concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'),lpad(upper(tilausrivi.hyllynro), 5, '0'),lpad(upper(tilausrivi.hyllyvali), 5, '0'),lpad(upper(tilausrivi.hyllytaso), 5, '0'), tilausrivi.tuoteno) as sorttauskentta";
				}
				else {
					$sorttauskentta = 'vh.indeksi as sorttauskentta';
				}

				$tyyppi = 'KERAYSERA';
			}
			else {
				$sorttauskentta = generoi_sorttauskentta($yhtiorow["kerayslistan_jarjestys"]);
				$order_sorttaus = $yhtiorow["kerayslistan_jarjestys_suunta"];

				if ($yhtiorow["kerayslistan_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";

				// Summataan rivit yhteen (HUOM! unohdetaan kaikki perheet!)
				if ($yhtiorow["kerayslistan_jarjestys"] == "S") {
					$select_lisa = "sum(tilausrivi.kpl) kpl, sum(tilausrivi.tilkpl) tilkpl, sum(tilausrivi.varattu) varattu, sum(tilausrivi.jt) jt, '' perheid, '' perheid2, ";
					$where_lisa = "GROUP BY tilausrivi.tuoteno, tilausrivi.hyllyalue, tilausrivi.hyllyvali, tilausrivi.hyllyalue, tilausrivi.hyllynro";
				}
			}
		}

		//ker�yslistan rivit
		if ($yhtiorow['kerayserat'] == 'K' and isset($tullaan_kerayserasta) and trim($tullaan_kerayserasta) != '' and isset($keraysvyohyke) and trim($keraysvyohyke) != '') {
			$query = "	SELECT tilausrivi.*,
						tuote.sarjanumeroseuranta,
						kerayserat.kpl as tilkpl,
						kerayserat.kpl as varattu,
						kerayserat.tunnus as ker_tunnus,
						kerayserat.pakkausnro,
						{$sorttauskentta}
						FROM kerayserat
						JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi AND tilausrivi.tyyppi != 'D')
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno {$lisa1})
						JOIN varaston_hyllypaikat vh ON (vh.yhtio = tilausrivi.yhtio AND vh.hyllyalue = tilausrivi.hyllyalue AND vh.hyllynro = tilausrivi.hyllynro AND vh.hyllyvali = tilausrivi.hyllyvali AND vh.hyllytaso = tilausrivi.hyllytaso)
						WHERE kerayserat.otunnus IN ({$tilausnumeroita})
						AND kerayserat.yhtio   = '{$kukarow['yhtio']}'
						ORDER BY sorttauskentta";
		}
		else {
			$query = "	SELECT tilausrivi.*,
						$select_lisa
						$sorttauskentta,
						if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi,
						if (tuote.myyntihinta_maara=0, 1, tuote.myyntihinta_maara) myyntihinta_maara,
						tuote.sarjanumeroseuranta,
						tuote.eankoodi
						FROM tilausrivi
						JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
						WHERE tilausrivi.otunnus in ($tilausnumeroita)
						and tilausrivi.yhtio   = '$kukarow[yhtio]'
						and tilausrivi.tyyppi  != 'D'
						$lisa1
						$where_lisa
						ORDER BY $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
		}
		$riresult = mysql_query($query) or pupe_error($query);

		//generoidaan rivinumerot
		$rivinumerot = array();

		$kal = 1;

		while ($row = mysql_fetch_array($riresult)) {
			if ($yhtiorow['kerayserat'] == 'K' and isset($tullaan_kerayserasta) and trim($tullaan_kerayserasta) != '' and isset($keraysvyohyke) and trim($keraysvyohyke) != '') {
				$rivinumerot[$row["ker_tunnus"]] = $kal;
			}
			else {
				$rivinumerot[$row["tunnus"]] = $kal;
			}
			$kal++;
		}

		mysql_data_seek($riresult,0);

		if (!isset($kieli)) $kieli = "";
		if (!isset($toim)) $toim = "";
		if (!isset($tyyppi)) $tyyppi = "";

		$kerayseran_numero = !isset($kerayseran_numero) ? 0 : $kerayseran_numero;

		$params_kerayslista = array(
		'asrow'           	=> $asrow,
		'boldi'           	=> $boldi,
		'iso'             	=> $iso,
		'iso_boldi'         => $iso_boldi,
		'kala'            	=> 0,
		'kieli'           	=> $kieli,
		'komento'			=> '',
		'laskurow'        	=> $laskurow,
		'norm'            	=> $norm,
		'page'            	=> '',
		'paino'           	=> 0,
		'pdf'             	=> NULL,
		'perheid'         	=> 0,
		'perheid2'        	=> 0,
		'pieni'           	=> $pieni,
		'pieni_boldi'     	=> $pieni_boldi,
		'rectparam'       	=> $rectparam,
		'rivinkorkeus'    	=> $rivinkorkeus,
		'rivinumerot'    	=> $rivinumerot,
		'row'             	=> NULL,
		'sivu'            	=> 1,
		'tee'             	=> $tee,
		'thispage'			=> NULL,
		'tilausnumeroita' 	=> $tilausnumeroita,
		'toim'            	=> $toim,
		'tots'            	=> 0,
		'tyyppi'		  	=> $tyyppi,
		'kerayseran_numero' => $kerayseran_numero,
		'kerayslistatyyppi'	=> $kerayslistatyyppi);

		// Aloitellaan ker�yslistan teko
		$params_kerayslista = alku_kerayslista($params_kerayslista);

		while ($row = mysql_fetch_assoc($riresult)) {
			$params_kerayslista["row"] = $row;
			$params_kerayslista = rivi_kerayslista($params_kerayslista);
		}

		$params_kerayslista["tots"] = 1;
		$params_kerayslista = loppu_kerayslista($params_kerayslista);

		//tulostetaan faili ja valitaan sopivat printterit
		if ($laskurow["varasto"] == 0) {
			$query = "	SELECT *
						from varastopaikat
						where yhtio='$kukarow[yhtio]'
						order by alkuhyllyalue,alkuhyllynro
						limit 1";
		}
		else {
			$query = "	SELECT *
						from varastopaikat
						where yhtio='$kukarow[yhtio]'
						and tunnus='$laskurow[varasto]'";
		}
		$prires = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($prires) > 0) {
			$prirow = mysql_fetch_array($prires);

			$apuprintteri = $prirow['printteri1']; // l�heteprintteri

			//haetaan optimaalinen tulostin t�lle l�hetteelle
			$tilaus  = $tilausnumeroita;
			$varasto = $laskurow["varasto"];

			require("varaston_tulostusalue.inc");

			if (isset($kirjoitin) and $kirjoitin != '') {
				$apuprintteri = $kirjoitin;
			}

			//k�sinvalittu printteri
			if (isset($valittu_tulostin) and $valittu_tulostin != '') {
				$apuprintteri = $valittu_tulostin;
			}

			//haetaan l�hetteen tulostuskomento
			$query = "	SELECT komento
						FROM kirjoittimet
						WHERE yhtio = '$kukarow[yhtio]'
						AND tunnus  = '$apuprintteri'";
			$kirres = mysql_query($query) or pupe_error($query);
			$kirrow = mysql_fetch_assoc($kirres);

			$komento = $kirrow['komento'];
		}

		if ($komento != "") {
			//tulostetaan sivu
			$params_kerayslista["komento"] = $komento;
			print_pdf_kerayslista($params_kerayslista);

			$returnvalue = 0;
		}
		else {
			$returnvalue = 1;
		}

		if ($returnvalue != 0) {
			echo "<br><font class='error'>".t("L�hetteen tulostus ep�onnistui")."! ".t("Tilaus")." $laskurow[tunnus] ".t("siirrettiin tulostusjonoon").". ".t("K�y tulostamassa l�hete tulostusjonosta").": <a href='lahetteen_tulostusjono.php'>".t("Tulostusjono")."</a>.</font><br><br>";
			$virheellinen = "X"; //Merkataan ep�onnistuneeksi
		}

		// jos meill� oli l�hetteen tulostusongelmia
		if ($virheellinen != 'X') {

			//jos tilauksia on useita niin laitetaan niille yhteinen kerayslista-tunnus
			if ($laskuja > 1) {
				$tunnukset = explode(',', $tilausnumeroita);
				$kerayslistatunnus = trim($tunnukset[0]);
			}
			else {
				$kerayslistatunnus = 0;
			}

			$ei_pakkaamoa_lisaviesti = '';

			if (isset($ei_pakkaamoa_selected) and $ei_pakkaamoa_selected == 'checked' and $ei_pakkaamoa == '') {
				$ei_pakkaamoa_lisaviesti = ", sisviesti2 = concat(sisviesti2, ' $kukarow[kuka] ".t("halusi tilauksen pakkaamolokeroihin")." ".tv1dateconv(date("Y-m-d H:i:s"))."')";
			}

			if ($toim =='VASTAANOTA_REKLAMAATIO' and $yhtiorow['reklamaation_kasittely'] == 'U') {
				$query = "	UPDATE lasku SET
							alatila = 'C',
							lahetepvm = now(),
							kerayslista = '$kerayslistatunnus'
							$ei_pakkaamoa_lisaviesti
							WHERE yhtio = '$kukarow[yhtio]'
							and tila = 'C' and alatila = 'B'
							and tunnus in ($tilausnumeroita)";
			}
			else {
				$query = "	UPDATE lasku SET
							tila = 'L',
							lahetepvm = now(),
							kerayslista = '$kerayslistatunnus'
							$ei_pakkaamoa_lisaviesti
							WHERE yhtio = '$kukarow[yhtio]'
							and tunnus in ($tilausnumeroita)";
				}
			$result = mysql_query($query) or pupe_error($query);
		}
	}
	else {
		echo "<font class='error'>".t("VIRHE: L�hete/Ker�yslista on jo tulostettu!")."</font><br>";
	}

	// poistetaan lukot
	$query = "UNLOCK TABLES";
	$res   = mysql_query($query) or pupe_error($query);

?>