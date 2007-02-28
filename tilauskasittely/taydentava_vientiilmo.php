<?php
	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("Tulosta t�ydent�v� vienti-ilmoitus")."</font><hr><br>";

	if ($tee == 'TULOSTA') {

		$pp = date('d');
		$kk = date('m');
		$vv = date('Y');
		$ajopvm = $pp.".".$kk.".".$vv;

		if (isset($kka) or isset($ppa) or isset($vva)) {
			if (!checkdate($kka, $ppa, $vva)) {
				echo "<font class='error'>".t("P�iv�m��r� virheellinen")."!</font><br>";
				exit;
			}
		}
		if (isset($kkl) or isset($ppl) or isset($vvl)) {
			if (!checkdate($kkl, $ppl, $vvl)) {
				echo "<font class='error'>".t("P�iv�m��r� virheellinen")."!</font><br>";
				exit;
			}
		}

		$alku  = "$ppa.$kka.$vva";
		$loppu = "$ppl.$kkl.$vvl";

		//tarkastuslistan funktiot
		require("taydentava_vientiilmo_tarkastuslista.inc");
		//paperilistan funktiot
		require("taydentava_vientiilmo_paperilista.inc");
		//atktullauksen funktiot
		require("taydentava_vientiilmo_atktietue.inc");

		//laskujen tulostuksen funktiot
		//require('../pdflib/phppdflib.class.php');
		//require("tulosta_lasku.inc");

		$query = "	SELECT *
					FROM lasku
					WHERE vienti	= 'K'
					and tila		= 'U'
					and alatila		= 'X'
					and tapvm	   >= '$vva-$kka-$ppa'
					and tapvm	   <= '$vvl-$kkl-$ppl'
					and tullausnumero != ''
					and yhtio		= '$kukarow[yhtio]'
					and lasku.kauppatapahtuman_luonne != '999'
					ORDER BY laskunro";
		$laskuresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($laskuresult) == 0)  {
			echo t("VIRHE: Aineisto on tyhj�, t�ydent�v�� vienti-ilmoitusta ei voida l�hett��")."!";
			exit;
		}


		//Avataan failit johon kirjotetaan

		list($usec, $sec) = explode(' ', microtime());
		mt_srand((float) $sec + ((float) $usec * 100000));

		//mik� pdffaili kyseess�
		//$pdfnro = 1;
		//$laskufaili = array();

		$tarkfaili = "/tmp/TVI_Tarkastuslista-".md5(uniqid(mt_rand(), true)).".txt";
		$fhtark = fopen($tarkfaili, "w+");

		$paperifaili = "/tmp/TVI_Paperilista-".md5(uniqid(mt_rand(), true)).".txt";
		$fhpaperi = fopen($paperifaili, "w+");

		$atkfaili = "/tmp/TVI_Atktietue-".md5(uniqid(mt_rand(), true)).".txt";
		$fhatk = fopen($atkfaili, "w+");


		///* NIY *///
		//$laskufaili[$pdfnro] = "/tmp/".$pdfnro.".TVI_Vientilaskut-".md5(uniqid(mt_rand(), true)).".pdf";
		//$fhpdf = fopen($laskufaili[$pdfnro], "w+");



		//aloitellaan ekan sivun tekemist�
		$tarksivu 	= 0;
		$tarkrivi 	= 1;
		$atkrivi 	= 1;

		$paperisivu = 1;
		$paperirivi = 1;

		//m��ritel��n muuttujat
		$tark 	= '';
		$paperi = '';
		$atk 	= '';

		//piirret��n ekat otsikot
		paperi_otsikko();

		$paperirivi += 10;

		//koko aineiston laskutusarvo
		$laskutusarvo = 0;
		//koko aineiston tietuem��r�
		$tietuemaara = 0;
		//summa per pvm
		$pvmyht = 0;

		//speciaalilaskuri laskujen kopioille
		$speclask = 0;

		//Katsomme tapahtuiko virheit�
		$virhe = 0;

		while($laskurow = mysql_fetch_array($laskuresult)) {

			///* Laskun tietoja tarkistetaan*///
			if ($laskurow["maa_maara"] == '') {
				echo t("Laskunumero").": $laskurow[laskunro]. ".t("M��r�maa puuttuu")."!<br>";
				$virhe++;
			}
			else {
				$query = "	SELECT distinct koodi
							FROM maat
							WHERE koodi='$laskurow[maa_maara]'";
				$maaresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($maaresult) == 0) {
					echo t("Laskunumero").": $laskurow[laskunro]. ".t("M��r�maa on virheellinen")."!<br>";
					$virhe++;
				}
			}

			if ($laskurow["kauppatapahtuman_luonne"] <= 0) {
			   	echo t("Laskunumero").": $laskurow[laskunro]. ".t("Kauppatapahtuman luonne puuttuu")."!<br>";
				$virhe++;
			}

			if ($laskurow["kuljetusmuoto"] == '') {
			    echo t("Laskunumero").": $laskurow[laskunro]. ".t("Kuljetusmuoto puuttuu")."!<br>";
				$virhe++;
			}

			if ($laskurow["sisamaan_kuljetus"] == '') {
			    echo t("Laskunumero").": $laskurow[laskunro]. ".t("Sis�maan kuljetus puuttuu")."!<br>";
				$virhe++;
			}

			if ($laskurow["sisamaan_kuljetusmuoto"] == '') {
			   	echo t("Laskunumero").": $laskurow[laskunro]. ".t("Sis�maan kuljetusmuoto puuttuu")."!<br>";
				$virhe++;
			}

			if ($laskurow["sisamaan_kuljetus_kansallisuus"] == '') {
			    //echo "Laskunumero: $laskurow[laskunro]. Sis�maan kuljetuksen kansallisuus puuttuu!<br>";
				//$virhe++;
			}
			else {
				$query = "	SELECT distinct koodi
							FROM maat
							WHERE koodi='$laskurow[sisamaan_kuljetus_kansallisuus]'";
				$maaresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($maaresult) == 0) {
					echo t("Laskunumero").": $laskurow[laskunro]. ".t("Sis�maan kuljetuksen kansallisuus on virheellinen")."!<br>";
					$virhe++;
				}
			}

			if ($laskurow["kontti"] == '') {
			    echo t("Laskunumero").": $laskurow[laskunro]. ".t("Konttitieto puuttuu")."!<br>";
				$virhe++;
			}

			if ($laskurow["aktiivinen_kuljetus_kansallisuus"]  == '') {
			   	echo t("Laskunumero").": $laskurow[laskunro]. ".t("Aktiivisen kuljetuksen kansallisuus puuttuu")."!<br>";
				$virhe++;
			}
			else {
				$query = "	SELECT distinct koodi
							FROM maat
							WHERE koodi='$laskurow[aktiivinen_kuljetus_kansallisuus]'";
				$maaresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($maaresult) == 0) {
					echo t("Laskunumero").": $laskurow[laskunro]. ".t("Aktiivisen kuljetuksen kansallisuus on virheellinen")."!<br>";
					$virhe++;
				}
			}

			if ($laskurow["poistumistoimipaikka_koodi"] == '') {
			   	echo t("Laskunumero").": $laskurow[laskunro]. ".t("Poistumistoimipaikkakoodi puuttuu")."!<br>";
				$virhe++;
			}

			if ($laskurow["bruttopaino"] == '') {
			  	echo t("Laskunumero").": $laskurow[laskunro]. ".t("Bruttopaino puuttuu")."!<br>";
				$virhe++;
			}
			///* Laskun tietojen tarkistus loppuu*///


			///* katsotaan, ett� laskulla on rivej�, hyvitysrivej� ei huolita mukaan vienti-ilmoitukseen, silloin er� hyl�t��n tullissa *///
			$cquery = "	SELECT
						tuote.tullinimike1,
						tuote.tullinimike2,
						tuote.tullikohtelu,
						(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
						if(sum(tilausrivi.rivihinta)>0,sum(tilausrivi.rivihinta),0.01) rivihinta
						FROM tilausrivi use index (uusiotunnus_index)
						JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = ''
						LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]'
						WHERE tilausrivi.uusiotunnus 	= '$laskurow[tunnus]'
						and tilausrivi.yhtio			= '$kukarow[yhtio]'
						and tilausrivi.kpl 				> 0
						GROUP BY tuote.tullinimike1, tuote.tullinimike2, tuote.tullikohtelu, alkuperamaa";
			$cresult = mysql_query($cquery) or pupe_error($cquery);

			if (mysql_num_rows($cresult) == 0) {
				///* Tarkistetaan, ett� tilausrivej� l�ytyy*///
				echo "Laskunumero: $laskurow[laskunro], ".t("Vitunmoinen virhe! Tilausrivej� ei l�ytynyt laskulle tai laskun summa oli nolla")."<br>";
			}
			else {

				///* Ylim��r�isten erien vaikutus *///
				$extrat = abs($laskurow["lisattava_era"])-abs($laskurow["vahennettava_era"]);

				///* Rivien summasta ja ylim��r�isist� erist� tulee laskun vientiarvo *///

				$vientiarvo = 0;
				$laskunarvo = 0;

				while($crow = mysql_fetch_array($cresult)) {
					$vientiarvo += $crow["rivihinta"];
					$laskunarvo += $crow["rivihinta"];
				}

				$vientiarvo += $extrat;
				$vientiarvo  = sprintf('%.2f', $vientiarvo);
				$laskunarvo  = sprintf('%.2f', $laskunarvo);


				/* Ei viel� tehd� laskunkopioita t�ss�...
				//laitetaan koostesivu laskupinkan v�liin
				if ($tapvm != $laskurow["tapvm"] && $speclask > 0) {
						$firstpage = $pdf->new_page("a4");
						$pdf->enable('template');
						$tid = $pdf->template->create();
						$pdf->template->size($tid, 600, 830);

						$query = "	SELECT min(laskunro), max(laskunro), count(*)
									FROM lasku
									WHERE vienti='K' and tila='U' and tullausnumero!='' and tapvm ='$tapvm' and yhtio='$kukarow[yhtio]'";
						$csresult = mysql_query($query) or pupe_error($query);
						$csrow = mysql_fetch_array($csresult);

						$pdf->draw_text(50,  810, $yhtiorow["nimi"], $firstpage);

						$pdf->draw_text(50,  790, t("P�iv�m��r�").":", $firstpage);
						$pdf->draw_text(150, 790, $tapvm, $firstpage);
						$pdf->draw_text(50,  770, t("Laskunumerot").":", $firstpage);
						$pdf->draw_text(150, 770, $csrow[0]."-".$csrow[1], $firstpage);
						$pdf->draw_text(50,  750, t("Kappaleet").":", $firstpage);
						$pdf->draw_text(150, 750, $csrow[2], $firstpage);
				}
				$speclask++;
				*/


				// aloitellaan laskun teko
				// defaultteja
				$tapvm = $laskurow["tapvm"];
				$kala = 540;
				$lask = 1;
				$sivu = 1;
				//$firstpage = alku();

				//Koko aineiston tullausarvo
				$laskutusarvo += $vientiarvo;

				if ($tarkrivi >= 40 || $laskurow["tapvm"] != $edtapvm) {
					if ($tarksivu >= 1) {
						tark_yht();
					}

					$pvmyht = 0;

					if ($tarksivu >= 1) {
						$tark .= chr(12);
					}

					$tarksivu++;
					$tarkrivi = 1;
					tark_otsikko();

					$tarkrivi += 5;
				}
				if ($paperirivi >= 40) {
					$paperi .= chr(12);
					$paperisivu++;
					$paperirivi = 1;
					paperi_otsikko();

					$paperirivi += 10;
				}

				///* Lasketaan laskun kokonaispaino *///
				//hetaan kaikki otunnukset jotka l�ytyv�t t�n uusiotunnuksen alta
				$query = "	SELECT distinct otunnus
							FROM tilausrivi
							WHERE tilausrivi.uusiotunnus = '$laskurow[tunnus]'
							and tilausrivi.yhtio='$kukarow[yhtio]'";
				$uresult = mysql_query($query) or pupe_error($query);

				$tunnukset = '';

				while($urow = mysql_fetch_array($uresult)) {
					$tunnukset  .= "'".$urow['otunnus']."',";
				}

				$tunnukset = substr($tunnukset,0,-1);

				//haetaan kollim��r� ja bruttopaino
				$query = "	SELECT *
							FROM rahtikirjat
							WHERE otsikkonro in ($tunnukset)
							and yhtio='$kukarow[yhtio]'";
				$rahtiresult = mysql_query($query) or pupe_error($query);

				$kilot  = 0;

				while($rahtirow = mysql_fetch_array($rahtiresult)) {
					$kilot  += $rahtirow["kilot"];
				}


				//Haetaan kaikki tilausrivit
				$query = "	SELECT
							tuote.tullinimike1,
							tuote.tullinimike2,
							tuote.tullikohtelu,
							(SELECT alkuperamaa FROM tuotteen_toimittajat WHERE tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.alkuperamaa!='' LIMIT 1) alkuperamaa,
							if(sum(tilausrivi.rivihinta)>0,sum(tilausrivi.rivihinta),0.01) rivihinta,
							sum(tilausrivi.kpl) kpl,
							round(sum((tilausrivi.rivihinta/$laskunarvo)*$kilot),0) nettop,
							tullinimike.su,
							tullinimike.su_vientiilmo,
							tilausrivi.nimitys,
							tilausrivi.tuoteno,
							tilausrivi.tunnus
							FROM tilausrivi use index (uusiotunnus_index)
							JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = ''
							LEFT JOIN tullinimike ON tuote.tullinimike1=tullinimike.cn and tullinimike.kieli = '$yhtiorow[kieli]'
							WHERE tilausrivi.uusiotunnus 	= '$laskurow[tunnus]'
							and tilausrivi.yhtio			= '$kukarow[yhtio]'
							and tilausrivi.kpl 				> 0
							GROUP BY tuote.tullinimike1, tuote.tullinimike2, tuote.tullikohtelu, alkuperamaa";
				$riviresult = mysql_query($query) or pupe_error($query);

				//piirret��n rivi tarkastuslistaan
				tark_rivi();

				$tarkrivi++;
				//ja lasketaan summa per p�iv�
				$pvmyht += $vientiarvo;

				//piirret��n otsikorivi paperilistaan
				paperi_otsikkorivi();

				$paperirivi += 4;

				//piirret��n atktullauksen er�tietue
				$atkrivi = 1;
				$vtietue = 2 + mysql_num_rows($riviresult);
				atk_eratietue();

				$atk .= "\n";
				$atkrivi++;

				//piirret��n atktullauksen arvotietue
				atk_arvotietue();
				$atk .= "\n";
				$atkrivi++;

				while($rivirow = mysql_fetch_array($riviresult)) {
					//v�h�n tarkistuksia
					if ($rivirow["tullinimike1"] == '') {
						echo t("1. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullinimike puuttuu")."!<br>";
						$virhe++;
					}
					else {
						$query = "	SELECT cn
									FROM tullinimike
									WHERE cn='$rivirow[tullinimike1]' and kieli = '$yhtiorow[kieli]'";
						$cnresult = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($cnresult) != 1) {
							echo t("1. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullinimike on virheellinen")."!<br>";
							$virhe++;
						}

					}

					if ($rivirow["tullikohtelu"] == '') {
						echo t("2. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullikohtelu puuttuu!")."<br>";
						$virhe++;
					}
					elseif (strlen($rivirow["tullikohtelu"]) != 4) {
						echo t("3. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullikohtelu on virheellinen!")."<br>";
						$virhe++;
					}
					elseif (!is_numeric($rivirow["tullikohtelu"])) {
						echo t("4. Rivin tunnus").":$rivirow[tunnus]. ".t("Tuoteno").": $rivirow[tuoteno]. ".t("Tullikohtelu on virheellinen, vain numeeriset arvot ovat sallittuja!")."<br>";
						$virhe++;
					}



					//laskun rivi
					//$row = $rivirow;
					//rivi($firstpage);

					//tullausarvo lis�erineen
					$tullarvo = round(($rivirow["rivihinta"] / $laskunarvo * $extrat) + $rivirow["rivihinta"],2);
					$tullarvo = sprintf('%.2f', $tullarvo);

					$tietuemaara++;

					if ($tarkrivi >= 40) {
						$tark .= chr(12);
						$tarksivu++;
						$tarkrivi = 1;
						tark_otsikko();

						$tarkrivi += 5;
					}
					if ($paperirivi >= 40) {
						$paperi .= chr(12);
						$paperisivu++;
						$paperirivi = 1;
						paperi_otsikko();

						$paperirivi += 10;
					}

					//piirret��n paperille nimikerivi
					paperi_nimikerivi();

					$paperirivi++;

					//piirret��n atktullaukseen nimikerivi
					atk_nimiketietue();
					$atk .= "\n";

					$atkrivi++;
				}

				//ja laskun vikalle sivulle
				//loppu($firstpage);
				//alvierittely ($firstpage, $kala);


				//kirjoitetaan  muuttujat failiin, s��stet��n muistia nollaamalla muuttujat
				fwrite($fhtark,$tark);
				$tark = '';

				fwrite($fhatk,$atk);
				$atk = '';

				fwrite($fhpaperi,$paperi);
				$paperi = '';

				///*kirjoitetaan pdf-objektit failiin ja luodaan uusi pdf-faili. Muuten muisti loppuu*///
				//fwrite($fhpdf, $pdf->generate());
				//fclose($fhpdf);

				//unset($pdf);

				//$pdfnro++;
				//$laskufaili[$pdfnro] = "/tmp/".$pdfnro.".TVI_Vientilaskut-".md5(uniqid(mt_rand(), true)).".pdf";
				//$fhpdf = fopen($laskufaili[$pdfnro], "w+");

				//PDF parametrit
				/*
				$pdf = new pdffile;

				$pdf->set_default('margin-top', 	0);
				$pdf->set_default('margin-bottom', 	0);
				$pdf->set_default('margin-left', 	0);
				$pdf->set_default('margin-right', 	0);
				//* PDF-kikkailut loppuu t�h�n*///
			}
		}

		/*
		//laitetaan se koostesivu laskupinkan loppuun
		$firstpage = $pdf->new_page("a4");
		$pdf->enable('template');
		$tid = $pdf->template->create();
		$pdf->template->size($tid, 600, 830);

		$query = "	SELECT min(laskunro), max(laskunro), count(*)
					FROM lasku
					WHERE vienti='K' and tila='U' and tullausnumero!='' and tapvm ='$tapvm' and yhtio='$kukarow[yhtio]'";
		$csresult = mysql_query($query) or pupe_error($query);
		$csrow = mysql_fetch_array($csresult);

		$pdf->draw_text(50,  810, $yhtiorow["nimi"], $firstpage);
		$pdf->draw_text(50,  790, t("P�iv�m��r�").":", $firstpage);
		$pdf->draw_text(150, 790, $tapvm, $firstpage);
		$pdf->draw_text(50,  770, t("Laskunumerot").":", $firstpage);
		$pdf->draw_text(150, 770, $csrow[0]."-".$csrow[1], $firstpage);
		$pdf->draw_text(50,  750, t("Kappaleet").":", $firstpage);
		$pdf->draw_text(150, 750, $csrow[2], $firstpage);

		*/

		//viel� vikalle sivulle piirrett�v�t
		$paperisivu++;

		tark_yht();
		paperi_loppu();

		//kirjoitetaan pdf-objektit ja muut kamat failiin
		//fwrite($fhpdf, $pdf->generate());

		fwrite($fhtark,$tark);
		$tark = '';

		fwrite($fhatk,$atk);
		$atk = '';

		fwrite($fhpaperi,$paperi);
		$paperi = '';


		//suljetaan failit
		fclose($fhatk);
		fclose($fhpaperi);
		//fclose($fhpdf);
		fclose($fhtark);



		//paperilista pit�� saada kauniiksi
		$line1 = exec("a2ps -o ".$paperifaili.".ps --no-header --columns=1 -r --chars-per-line=169 --margin=0 --borders=0 $paperifaili");

		//tarkastuslistalle sama juttu
		$line2 = exec("a2ps -o ".$tarkfaili.".ps --no-header --columns=1 -r --chars-per-line=121 --margin=0 --borders=0 $tarkfaili");


    	//lopuks k��nnet��n paskat viel� pdf:iks ja l�hetet��n s�hk�postiin, voi sitten tulostella kun silt� tuntuu
		$line3 = exec("ps2pdf -sPAPERSIZE=a4 ".$paperifaili.".ps ".$paperifaili.".pdf");

		//tarkastuslistalle sama juttu
		$line4 = exec("ps2pdf -sPAPERSIZE=a4 ".$tarkfaili.".ps ".$tarkfaili.".pdf");

		//mergataan pdf-�t yhdeksi failiksi joka sit l�hetet��n k�ytt�j�lle
		//$kaikkilaskut = "/tmp/TVI_Kaikki_Vientilaskut-".md5(uniqid(mt_rand(), true)).".pdf";



		///* Katsotaan voidaanko rappari l�hett�� tulliin *///

		if ($virhe > 0) {
			//virheit� on sattunut
			echo "<br><br>".t("Korjaa ensin kaikki virheet. Ja kokeile sitten uudestaan").".<br><br>";
			system("rm -f $tarkfaili");
			system("rm -f ".$tarkfaili.".ps");
			system("rm -f ".$tarkfaili.".pdf");
			system("rm -f ".$paperifaili.".ps");
			system("rm -f ".$paperifaili.".pdf");
			system("rm -f $paperifaili");
			system("rm -f $atkfaili");
			exit;
		}


		///*Kasataan k�ytt�j�lle l�hetett�v� meili *///
		//t�ssa on kaikki failit jotka tarvitaan
 		$bound = uniqid(time()."_") ;

		$header  = "From: <$yhtiorow[admin_email]>\r\n";
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

		$content = "--$bound\r\n";

		/*
		$content .= "Content-Type: application/pdf; name=\"Laskut.pdf\"\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: inline; filename=\"Laskut.pdf\"\r\n\r\n";
		$nimi 	 = $laskufaili;
		$handle  = fopen($nimi, "r");
		$sisalto = fread($handle, filesize($nimi));
		fclose($handle);
		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\r\n" ;

		$content .= "--$bound\r\n";
		*/

		$content .= "Content-Type: application/pdf; name=\"".t("Tarkastuslista").".pdf\"\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: inline; filename=\"".t("Tarkastuslista").".pdf\"\r\n\r\n";
		$nimi 	 = $tarkfaili.".pdf";
		$handle  = fopen($nimi, "r");
		$sisalto = fread($handle, filesize($nimi));
		fclose($handle);
		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\r\n" ;

		$content .= "--$bound\r\n";

		$content .= "Content-Type: application/pdf; name=\"".t("Taydentava-Ilmoitus").".pdf\"\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: inline; filename=\"".t("Taydentava-Ilmoitus").".pdf\"\r\n\r\n";
		$nimi 	 = $paperifaili.".pdf";
		$handle  = fopen($nimi, "r");
		$sisalto = fread($handle, filesize($nimi));
		fclose($handle);
		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\r\n" ;

		$content .= "--$bound--\r\n";

		mail($kukarow["eposti"],  t("T�ydent�v� vienti-ilmoitus")."", $content, $header);


		///* T�ss� teh��n t�ydent�v� ilmoitus s�hk�iseen muotoon *///
		//PGP-encryptaus atklabeli
		$label  = '';
		$label .= t("l�hett�j�").": $yhtiorow[nimi]\n";
		$label .= t("sis�lt�").": vientitullaus/sis�kaupantilasto\n";
		$label .= t("kieli").": ASCII\n";
		$label .= t("jakso").": $alku - $loppu\n";
		$label .= t("koko aineiston tietuem��r�").": $tietuemaara\n";
		$label .= t("koko aineiston vienti-, verotus- tai laskutusarvo").": $laskutusarvo\n";

		$message = '';

		$recipient = "pgp-key Customs Finland <ascii.vienti@tulli.fi>";

		$message = $label;
		require("../inc/gpg.inc");
		$label = $encrypted_message;

		//PGP-encryptaus atktietue
		$recipient = "pgp-key Customs Finland <ascii.vienti@tulli.fi>";

		$nimi	 = $atkfaili;
		$handle  = fopen($nimi, "r");
		$message = fread($handle, filesize($nimi));
		fclose($handle);

		require("../inc/gpg.inc");
		$atk = $encrypted_message;


		//Kasataan tulliin l�hetett�v� meili
 		$bound = uniqid(time()."_") ;

		$header  = "From: <juppe@arwidson.fi>\r\n";
		$header .= "MIME-Version: 1.0\r\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n" ;

		$content = "--$bound\r\n" ;

		$content .= "Content-Type: application/pgp-encrypted;\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: attachment; filename=\"otsikko.pgp\"\r\n\r\n";
		$content .= chunk_split(base64_encode($label));
		$content .= "\r\n" ;

		$content .= "--$bound\r\n" ;

		$content .= "Content-Type: application/pgp-encrypted;\r\n" ;
		$content .= "Content-Transfer-Encoding: base64\r\n" ;
		$content .= "Content-Disposition: attachment; filename=\"tietue.pgp\"\r\n\r\n";
		$content .= chunk_split(base64_encode($atk));
		$content .= "\r\n" ;

		$content .= "--$bound--\r\n" ;

		$to = 'ascii.vienti@tulli.fi';
		mail($to, "", $content, $header, "-f juppe@arwidson.fi");

		$to = 'juppe@arwidson.fi';
		mail($to, "", $content, $header);

		echo "<br><br>".t("S�hk�postit l�hetetty! Kaikki on valmista")."!";

		//Dellataan viel� viimeiset failit
		system("rm -f $tarkfaili");
		system("rm -f ".$tarkfaili.".ps");
		system("rm -f ".$tarkfaili.".pdf");
		system("rm -f ".$paperifaili.".ps");
		system("rm -f ".$paperifaili.".pdf");
		system("rm -f $paperifaili");
		system("rm -f $atkfaili");

	}
	if ($tee == '') {
		if (!isset($kka))
			$kka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		if (!isset($vva))
			$vva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		if (!isset($ppa))
			$ppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");

		//sy�tet��n ajanjakso
		echo "<table>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>
				<input type='hidden' name='toim' value='$toim'>";

		echo "<tr><td class='back'></td><th>".t("pp")."</th><th>".t("kk")."</th><th>".t("vvvv")."</th></tr>";

		echo "<tr><th>".t("Sy�t� alkup�iv�m��r�")." </th>
			<td><input type='text' name='ppa' value='$ppa' size='5'></td>
			<td><input type='text' name='kka' value='$kka' size='5'></td>
			<td><input type='text' name='vva' value='$vva' size='7'></td>
			</tr><tr><th>".t("Sy�t� loppup�iv�m��r�")." </th>
			<td><input type='text' name='ppl' value='$ppl' size='5'></td>
			<td><input type='text' name='kkl' value='$kkl' size='5'></td>
			<td><input type='text' name='vvl' value='$vvl' size='7'></td>";

		echo "<td><input type='submit' value='".t("Tulosta")."'></td></tr>";
		echo "</form>";
		echo "</table>";

	}

	require ('../inc/footer.inc');

?>
