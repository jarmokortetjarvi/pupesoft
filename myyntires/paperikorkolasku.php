<?php

	function paperi_alku () {
		global $pdf, $asiakastiedot, $yhteyshenkilo, $yhtiorow, $kukarow, $kala, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli, $vmehto;

		$firstpage = $pdf->new_page("a4");
		$pdf->enable('template');
		$tid = $pdf->template->create();
		$pdf->template->size($tid, 600, 830);
		
		//Haetaan yhteyshenkilon tiedot
		$apuqu = "	select *
					from kuka 
					where yhtio='$kukarow[yhtio]' and tunnus='$yhteyshenkilo'";
		$yres = mysql_query($apuqu) or pupe_error($apuqu);
		$yrow = mysql_fetch_array($yres);

		//Otsikko
		//$pdf->draw_rectangle(830, 20,  800, 580, $firstpage, $rectparam);
		$pdf->draw_text(30, 815,  $yhtiorow["nimi"], $firstpage);
		$pdf->draw_text(280, 815, t("Korkoerittely", $kieli), $firstpage);
		$pdf->draw_text(430, 815, t("Sivu", $kieli)." ".$sivu, $firstpage, $norm);

		//Vasen sarake
		//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), $firstpage, $pieni);
		$pdf->draw_text(50, 717, $asiakastiedot["nimi"], $firstpage, $norm);
		$pdf->draw_text(50, 707, $asiakastiedot["nimitark"], $firstpage, $norm);
		$pdf->draw_text(50, 697, $asiakastiedot["osoite"], $firstpage, $norm);
		$pdf->draw_text(50, 687, $asiakastiedot["postino"]." ".$asiakastiedot["postitp"], $firstpage, $norm);
		$pdf->draw_text(50, 677, $asiakastiedot["maa"], $firstpage, $norm);

		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 792, t("P�iv�m��r�", $kieli), $firstpage, $pieni);
		$pdf->draw_text(310, 782, date('Y-m-d'), $firstpage, $norm);
		$pdf->draw_text(430, 792, t("Asiaa hoitaa", $kieli), $firstpage, $pieni);
		$pdf->draw_text(430, 782, $yrow["nimi"], $firstpage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 771, t("Er�p�iv�", $kieli), $firstpage, $pieni);

		
		// Etsit��n maksuehto
		$query = "	SELECT *
					FROM maksuehto
					WHERE tunnus='$vmehto' and yhtio='$kukarow[yhtio]'";
		$presult = mysql_query($query) or pupe_error($query);
		$xrow = mysql_fetch_array($presult);

		if ($xrow['abs_pvm'] == '0000-00-00') {
		
			$seurday   = sprintf('%02d', date("j", mktime(0, 0, 0, date("n"), date("j")+$xrow["rel_pvm"],  date("Y"))));
			$seurmonth = sprintf('%02d', date("n", mktime(0, 0, 0, date("n"), date("j")+$xrow["rel_pvm"],  date("Y"))));
			$seuryear  = sprintf('%02d', date("Y", mktime(0, 0, 0, date("n"), date("j")+$xrow["rel_pvm"],  date("Y"))));
			
			$erapvm = $seuryear."-".$seurmonth."-".$seurday;
		}
		else {
			$erapvm = "'$xrow[abs_pvm]'";
		}
										
		$pdf->draw_text(310, 761, $erapvm, $firstpage, $norm);

		$pdf->draw_text(430, 771, t("Puhelin", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(430, 761, $yrow["puhno"], 		$firstpage, $norm);

		$pdf->draw_rectangle(758, 300, 737, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 750, t("Ytunnus/Asiakasnumero", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 740, $asiakastiedot["ytunnus"], 			$firstpage, $norm);

		$pdf->draw_text(430, 750, t("S�hk�posti", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 740, $yrow["eposti"], 			$firstpage, $norm);

		$kala = 620;

		//Laskurivien otsikkotiedot
		//eka rivi
		$pdf->draw_text(30,  $kala, t("Laskunumero", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(100, $kala, t("P�iv�m��r�", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(160, $kala, t("Er�p�iv�", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(220, $kala, t("Maksettu", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(280, $kala, t("My�h�ss� pv.", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(340, $kala, t("Viiv�stykorko-%", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(420, $kala, t("Laskun summa", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(500, $kala, t("Korko", $kieli),				$firstpage, $pieni);

		$kala -= 15;

		return($firstpage);
	}

	function paperi_rivi ($firstpage, $summa) {
		global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli;

		if ($lask >= 37) {
			$sivu++;
			paperi_loppu($firstpage,'');
			$firstpage = paperi_alku();
			$kala = 605;
			$lask = 1;
		}

		$pdf->draw_text(30,  $kala, $row["laskunro"],		$firstpage, $norm);
		$pdf->draw_text(100, $kala, $row["tapvm"], 			$firstpage, $norm);
		$pdf->draw_text(160, $kala, $row["erpcm"], 			$firstpage, $norm);
		$pdf->draw_text(220, $kala, $row["mapvm"], 			$firstpage, $norm);
		$pdf->draw_text(280, $kala, $row["ika"],			$firstpage, $norm);
		$pdf->draw_text(340, $kala, $row["viikorkopros"],	$firstpage, $norm);
		$pdf->draw_text(420, $kala, $row["summa"], 			$firstpage, $norm);
		$pdf->draw_text(500, $kala, $row["korkosumma"], 	$firstpage, $norm);
		$kala = $kala - 13;

		$lask++;
		$summa+=$row["korkosumma"];
		return($summa);
	}


	function paperi_loppu ($firstpage, $summa) {

		global $pdf, $laskurow, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli;

		//yhteens�rivi
		$pdf->draw_rectangle(110, 20, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 207, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 394, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 540, 90, 580,	$firstpage, $rectparam);

		$pdf->draw_text(404, 92,  t("YHTEENS�", $kieli).":",	$firstpage, $norm);
		$pdf->draw_text(464, 92,  $summa,						$firstpage, $norm);
		$pdf->draw_text(550, 92,  $yhtiorow["valkoodi"],		$firstpage, $norm);

		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90, 20, 20, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(30, 72,  $yhtiorow["pankkinimi1"],	$firstpage, $norm);
		$pdf->draw_text(80, 72,  $yhtiorow["pankkitili1"],	$firstpage, $norm);
		$pdf->draw_text(217, 72, $yhtiorow["pankkinimi2"],	$firstpage, $norm);
		$pdf->draw_text(257, 72, $yhtiorow["pankkitili2"],	$firstpage, $norm);
		$pdf->draw_text(404, 72, $yhtiorow["pankkinimi3"],	$firstpage, $norm);
		$pdf->draw_text(444, 72, $yhtiorow["pankkitili3"],	$firstpage, $norm);


		//Alimmat kolme laatikkoa, yhti�tietoja
		$pdf->draw_rectangle(70, 20, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 207, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 394, 20, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30, 55, $yhtiorow["nimi"],		$firstpage, $pieni);
		$pdf->draw_text(30, 45, $yhtiorow["osoite"],	$firstpage, $pieni);
		$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$firstpage, $pieni);
		$pdf->draw_text(30, 25, $yhtiorow["maa"],		$firstpage, $pieni);

		$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(247, 55, $yhtiorow["puhelin"],			$firstpage, $pieni);
		$pdf->draw_text(217, 45, t("Fax", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(247, 45, $yhtiorow["fax"],				$firstpage, $pieni);
		$pdf->draw_text(217, 35, t("Email", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(247, 35, $yhtiorow["email"],			$firstpage, $pieni);

		$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(444, 55, $yhtiorow["ytunnus"],			$firstpage, $pieni);
		$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",	$firstpage, $pieni);
		$pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],		$firstpage, $pieni);
		$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(404, 25, t("Alv.rek", $kieli),			$firstpage, $pieni);

	}

	require('../inc/parametrit.inc');

	require('../pdflib/phppdflib.class.php');

	//echo "<font class='message'>Karhukirje tulostuu...</font>";
	flush();

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	$norm["height"] = 10;
	$norm["font"] = "Times-Roman";

	$pieni["height"] = 8;
	$pieni["font"] = "Times-Roman";

	// defaultteja
	$lask = 1;
	$sivu = 1;

	// aloitellaan laskun teko
	$xquery='';
	for ($i=0; $i<sizeof($lasku_tunnus); $i++) {
		if($i != 0) { 
			$xquery=$xquery . ",";
		}
				
		$xquery=$xquery . "$lasku_tunnus[$i]";
	}

	$query = "	SELECT l.tunnus, l.liitostunnus, t.summa*-1 summa, l.erpcm,
				l.laskunro, t.tapvm mapvm, l.tapvm tapvm, l.tunnus, l.viikorkopros, to_days(t.tapvm) - to_days(l.erpcm) as ika,
				round(l.viikorkopros * t.summa * -1 * (to_days(t.tapvm)-to_days(l.erpcm)) / 36500,2) as korkosumma
				FROM lasku l use index (PRIMARY)
				join tiliointi t use index (tositerivit_index) on (t.yhtio = l.yhtio and t.tilino = '$yhtiorow[myyntisaamiset]' and t.tapvm > l.erpcm and l.tunnus = t.ltunnus)
				LEFT JOIN maksuehto me on (me.yhtio=l.yhtio and me.tunnus=l.maksuehto)
				WHERE l.tunnus in ($xquery)
				and l.yhtio = '$kukarow[yhtio]'
				and l.tila = 'U'
				ORDER BY l.laskunro, t.tapvm";

	$result = mysql_query($query) or pupe_error($query);

	//otetaan asiakastiedot ekalta laskulta
	$asiakastiedot = mysql_fetch_array($result);
	
	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' AND tunnus = '$asiakastiedot[liitostunnus]'";
	$asiakasresult = mysql_query($query) or pupe_error($query);
	$asiakastiedot = mysql_fetch_array($asiakasresult);
	
	//Otetaan t�ss� asiakkaan kieli talteen
	$kieli = $asiakastiedot["kieli"];
	
	//Liitostunnusta tarvitaan tee_korkolasku.inc failissa
	$liitostunnus  = $asiakastiedot["tunnus"];
	
	//ja kelataan akuun
	mysql_data_seek($result,0);

	$firstpage = paperi_alku();

	//karhu_begin_work();
	$summa = 0;
	while ($row = mysql_fetch_array($result)) {
		$summa = paperi_rivi($firstpage,$summa);

		//p�ivitet��n korkosumma
		$query = "	UPDATE lasku
					SET viikorkoeur = '$row[korkosumma]'
					WHERE tunnus = '$row[tunnus]'
					and yhtio = '$kukarow[yhtio]'";
		$eurresult = mysql_query($query) or pupe_error($query);
	}

	$loppusumma = sprintf('%.2f', $summa);

	paperi_loppu($firstpage,$loppusumma);

	//keksit��n uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$pdffilenimi = "/tmp/karhukirje-".md5(uniqid(mt_rand(), true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
	fclose($fh);

	// itse print komento...
	$query = "	select komento
				from kirjoittimet
				where yhtio='$kukarow[yhtio]' and tunnus = '$kukarow[kirjoitin]'";
	$kires = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($kires) == 1) {
		$kirow=mysql_fetch_array($kires);
		$line = exec("$kirow[komento] $pdffilenimi");
	}
?>
