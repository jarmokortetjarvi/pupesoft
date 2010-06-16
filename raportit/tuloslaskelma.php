<?php

	if (isset($_POST["teetiedosto"])) {
		if($_POST["teetiedosto"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "tuloslaskelma.php")  !== FALSE) {
		require ("../inc/parametrit.inc");
	}
	else {
		if($from != "PROJEKTIKALENTERI" or (int) $mul_proj[0] == 0) {
			die("<font class='error'>�l� edes yrit�!</font>");
		}
	}

	if (isset($teetiedosto)) {
		if ($teetiedosto == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	else {
		// Muokataan tilikartan rakennetta
		if (isset($tasomuutos)) {
			require("../tasomuutos.inc");
			require ('../inc/footer.inc');
			exit;
		}

		if ($from != "PROJEKTIKALENTERI") {
			echo "<font class='head'>".t("Tase/tuloslaskelma")."</font><hr>";
		}

		if ($tltee == "aja") {
			if ($plvv * 12 + $plvk > $alvv * 12 + $alvk) {
				echo "<font class='error'>".t("Alkukausi on p��ttymiskauden j�lkeen")."</font><br>";
				$tltee = '';
			}
		}

		// Jotta saadaan arrays lopetusmuuttujassa oikein
		if (isset($mul_kohde_seri)) $mul_kohde = unserialize(base64_decode($mul_kohde_seri));
		if (isset($mul_proj_seri))  $mul_proj  = unserialize(base64_decode($mul_proj_seri));
		if (isset($mul_kustp_seri)) $mul_kustp = unserialize(base64_decode($mul_kustp_seri));

		if ($tltee == "aja") {

			// Desimaalit
			$muoto = "%.". (int) $desi . "f";

			// Onko meill� lis�rajoitteita??
			$lisa  = "";
			$lisa2 = "";

			if (is_array($mul_kustp) and count($mul_kustp) > 0) {
				$sel_kustp = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_kustp))."')";
				$lisa 	.= " and kustp in $sel_kustp ";
				$lisa2	.= " and kustannuspaikka in $sel_kustp ";
			}
			if (is_array($mul_proj) and count($mul_proj) > 0) {
				$sel_proj = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_proj))."')";
				$lisa 	.= " and projekti in $sel_proj ";
				$lisa2 	.= " and projekti in $sel_proj ";
			}
			if (is_array($mul_kohde) and count($mul_kohde) > 0) {
				$sel_kohde = "('".str_replace(array('PUPEKAIKKIMUUT', ','), array('', '\',\''), implode(",", $mul_kohde))."')";
				$lisa 	.= " and kohde in $sel_kohde ";
				$lisa2 	.= " and kohde in $sel_kohde ";
			}
			if ($plvk == '' or $plvv == '') {
				$plvv = substr($yhtiorow['tilikausi_alku'], 0, 4);
				$plvk = substr($yhtiorow['tilikausi_alku'], 5, 2);
			}

			if ($tyyppi == "1") {
				// Vastaavaa Varat
				$otsikko 	= "Vastaavaa Varat";
				$kirjain 	= "U";
				$aputyyppi 	= 1;
				$tilikarttataso = "ulkoinen_taso";
				$luku_kerroin = 1;
			}
			elseif ($tyyppi == "2") {
				// Vastattavaa Velat
				$otsikko 	= "Vastattavaa Velat";
				$kirjain 	= "U";
				$aputyyppi 	= 2;
				$tilikarttataso = "ulkoinen_taso";
				$luku_kerroin = 1;
			}
			elseif ($tyyppi == "3") {
				// Ulkoinen tuloslaskelma
				$otsikko 	= "Ulkoinen tuloslaskelma";
				$kirjain 	= "U";
				$aputyyppi 	= 3;
				$tilikarttataso = "ulkoinen_taso";
				$luku_kerroin = -1;
			}
			else {
				// Sis�inen tuloslaskelma
				$otsikko 	= "Sis�inen tuloslaskelma";
				$kirjain 	= "S";
				$aputyyppi 	= 3;
				$tilikarttataso = "sisainen_taso";
				$luku_kerroin = -1;
			}

			// edellinen taso
			$taso     			= array();
			$tasonimi 			= array();
			$summattavattasot	= array();
			$summa    			= array();
			$kaudet   			= array();

			if ((int) $tkausi > 0) {
				$query = "	SELECT tilikausi_alku, tilikausi_loppu
							FROM tilikaudet
							WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
				$result = mysql_query($query) or pupe_error($query);
				$tkrow = mysql_fetch_array($result);

				$plvv = substr($tkrow['tilikausi_alku'], 0, 4);
				$plvk = substr($tkrow['tilikausi_alku'], 5, 2);
				$plvp = substr($tkrow['tilikausi_alku'], 8, 2);

				$alvv = substr($tkrow['tilikausi_loppu'], 0, 4);
				$alvk = substr($tkrow['tilikausi_loppu'], 5, 2);
				$alvp = substr($tkrow['tilikausi_loppu'], 8, 2);
			}

			// Tarkistetaan viel� p�iv�m��r�t
			if (!checkdate($plvk, $plvp, $plvv)) {
				echo "<font class='error'>".t("VIRHE: Alkup�iv�m��r� on virheellinen")."!</font><br>";
				$tltee = "";
			}

			if (!checkdate($alvk, $alvp, $alvv)) {
				echo "<font class='error'>".t("VIRHE: Loppup�iv�m��r� on virheellinen")."!</font><br>";
				$tltee = "";
			}
		}


		if ($tltee == "aja") {

			// Tehd��nk� linkit p�iv�kirjaan
			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'raportit.php'
						and alanimi = 'paakirja'";
			$oikresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($oikresult) > 0) {
				$paakirjalink = TRUE;
			}
			else {
				$paakirjalink = FALSE;
			}

			$lopelinkki = "&lopetus=$PHP_SELF////tltee=$tltee//toim=$toim//tyyppi=$tyyppi//plvv=$plvv//plvk=$plvk//plvp=$plvp//alvv=$alvv//alvk=$alvk//alvp=$alvp//tkausi=$tkausi//mul_kustp_seri=".base64_encode(serialize($mul_kustp))."//mul_kohde_seri=".base64_encode(serialize($mul_kohde))."//mul_proj_seri=".base64_encode(serialize($mul_proj))."//rtaso=$rtaso//tarkkuus=$tarkkuus//desi=$desi//kaikkikaudet=$kaikkikaudet//eiyhteensa=$eiyhteensa//vertailued=$vertailued//vertailubu=$vertailubu";

			$startmonth	= date("Ymd",   mktime(0, 0, 0, $plvk, 1, $plvv));
			$endmonth 	= date("Ymd",   mktime(0, 0, 0, $alvk, 1, $alvv));

			$annettualk = $plvv."-".$plvk."-".$plvp;
			$totalloppu = $alvv."-".$alvk."-".$alvp;

			$budjettalk = date("Ym", mktime(0, 0, 0, $plvk, 1, $plvv));
			$budjettlop = date("Ym", mktime(0, 0, 0, $alvk+1, 0, $alvv));

			if ($vertailued != "") {
				$totalalku  = ($plvv-1)."-".$plvk."-".$plvp;
				$totalloppued = ($alvv-1)."-".$alvk."-".$alvp;
			}
			else {
				$totalalku = $plvv."-".$plvk."-".$plvp;
			}

			$alkuquery1 = "";
			$alkuquery2 = "";
			$alkuquery3 = "";

			for ($i = $startmonth;  $i <= $endmonth;) {

				if ($i == $startmonth) $alku = $plvv."-".$plvk."-".$plvp;
				else $alku = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2), substr($i,0,4)));

				if ($i == $endmonth) $loppu = $alvv."-".$alvk."-".$alvp;
				else $loppu = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2)+1, 0, substr($i,0,4)));

				$bukausi = date("Ym",    mktime(0, 0, 0, substr($i,4,2), substr($i,6,2), substr($i,0,4)));
				$headny  = date("Y/m",   mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));

				if ($alkuquery1 != "") $alkuquery1 .= " ,";
				if ($alkuquery2 != "") $alkuquery2 .= " ,";
				if ($alkuquery3 != "") $alkuquery3 .= " ,";

				$alkuquery1 .= "sum(if(tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny'\n";
				$alkuquery2 .= "sum(if(tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny'\n";
				$alkuquery3 .= "sum(if(tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny'\n";

				$kaudet[] = $headny;

				if ($vertailued != "") {

					if ($i == $startmonth) $alku_ed = ($plvv-1)."-".$plvk."-".$plvp;
					else $alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2), substr($i,0,4)-1));

					if ($i == $endmonth) $loppu_ed = ($alvv-1)."-".$alvk."-".$alvp;
					else $loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2)+1, 0, substr($i,0,4)-1));

					$headed   = date("Y/m",   mktime(0, 0, 0, substr($i,4,2), substr($i,6,2), substr($i,0,4)-1));

					$alkuquery1 .= " ,sum(if(tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed'\n";
					$alkuquery2 .= " ,sum(if(tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed'\n";
					$alkuquery3 .= " ,sum(if(tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed'\n";

					$kaudet[] = $headed;
				}

				// sis�isess� tuloslaskelmassa voidaan joinata budjetti
				if ($vertailubu != "" and $kirjain == "S") {
					$alkuquery1 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and BINARY budjetti.taso = BINARY tili.$tilikarttataso and budjetti.kausi = '$bukausi' $lisa2) 'budj $headny'\n";
					$alkuquery2 .= " ,0 'budj $headny'\n";
					$alkuquery3 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and BINARY budjetti.taso = BINARY tili.taso and budjetti.kausi = '$bukausi' $lisa2) 'budj $headny'\n";

					$kaudet[] = "budj $headny";
				}

				$i = date("Ymd",mktime(0, 0, 0, substr($i,4,2)+1, 1, substr($i,0,4)));
			}

			$vka = date("Y/m", mktime(0, 0, 0, $plvk, 1, $plvv));
			$vkl = date("Y/m", mktime(0, 0, 0, $alvk+1, 0, $alvv));

			$vkaed = date("Y/m", mktime(0, 0, 0, $plvk, 1, $plvv-1));
			$vkled = date("Y/m", mktime(0, 0, 0, $alvk+1, 0, $alvv-1));

			// Yhteens�otsikkomukaan
			if ($eiyhteensa == "") {
				$alkuquery1 .= " ,sum(if(tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) '$vka - $vkl' \n";
				$alkuquery2 .= " ,sum(if(tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) 'Total' \n";
				$alkuquery3 .= " ,sum(if(tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) '$vka - $vkl' \n";
				$kaudet[] = $vka." - ".$vkl;

				if ($vertailued != "") {

					$alkuquery1 .= " ,sum(if(tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm <= '$totalloppued', tiliointi.summa, 0)) '$vkaed - $vkled' \n";
					$alkuquery2 .= " ,sum(if(tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm <= '$totalloppued', tiliointi.summa, 0)) 'Totaled' \n";
					$alkuquery3 .= " ,sum(if(tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm <= '$totalloppued', tiliointi.summa, 0)) '$vkaed - $vkled' \n";

					$kaudet[] = $vkaed." - ".$vkled;
				}

				if ($vertailubu != "" and $kirjain == "S") {
					$alkuquery1 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and BINARY budjetti.taso = BINARY tili.$tilikarttataso and budjetti.kausi >= '$budjettalk' and budjetti.kausi <= '$budjettlop' $lisa2) 'budj $vka - $vkl' \n";
					$alkuquery2 .= " ,0 'budj $vka - $vkl' \n";
					$alkuquery3 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and BINARY budjetti.taso = BINARY tili.taso and budjetti.kausi >= '$budjettalk' and budjetti.kausi <= '$budjettlop' $lisa2) 'budj $vka - $vkl' \n";
					$kaudet[] = "budj ".$vka." - ".$vkl;
				}
			}

			$query = "	SELECT *
						FROM taso
						WHERE yhtio = '$kukarow[yhtio]' AND
						tyyppi = '$kirjain' AND
						LEFT(taso, 1) = BINARY '$aputyyppi'
						and taso != ''
						ORDER BY taso";
			$tasores = mysql_query($query) or pupe_error($query);

			while ($tasorow = mysql_fetch_array($tasores)) {

				// mill� tasolla ollaan (1,2,3,4,5,6)
				$tasoluku = strlen($tasorow["taso"]);

				// tasonimi talteen (rightp�dd�t��n �:ll�, niin saadaan oikeaan j�rjestykseen)
				$apusort = str_pad($tasorow["taso"], 20, "�");
				$tasonimi[$apusort] = $tasorow["nimi"];

				if ($toim == "TASOMUUTOS") {
					$summattavattasot[$apusort] = $tasorow["summattava_taso"];
				}

				// pilkotaan taso osiin
				$taso = array();
				for ($i=0; $i < $tasoluku; $i++) {
					$taso[$i] = substr($tasorow["taso"], 0, $i+1);
				}

				$query = "	SELECT $alkuquery1
						 	FROM tili
							LEFT JOIN tiliointi USE INDEX (yhtio_tilino_tapvm) ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino and tiliointi.korjattu = '' and tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm <= '$totalloppu' $lisa)
							WHERE tili.yhtio 		 = '$kukarow[yhtio]'
							and tili.$tilikarttataso = BINARY '$tasorow[taso]'
							group by tili.$tilikarttataso";
				$tilires = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tilires) == 0) {
					// Ei tili�intej�, mutta budjetti voi olla
					$query = "	SELECT $alkuquery3
							 	FROM budjetti
								JOIN budjetti tili ON (tili.yhtio = budjetti.yhtio and tili.tunnus=budjetti.tunnus)
								LEFT JOIN tiliointi USE INDEX (PRIMARY) ON (tiliointi.tunnus = 0)
								WHERE budjetti.yhtio  = '$kukarow[yhtio]'
								and budjetti.taso 	  = BINARY '$tasorow[taso]'
								group by budjetti.taso";
					$tilires = mysql_query($query) or pupe_error($query);
				}

				while ($tilirow = mysql_fetch_array ($tilires)) {
					// summataan kausien saldot
					foreach ($kaudet as $kausi) {
						// summataan kaikkia pienempi� summaustasoja
						if (substr($kausi,0,4) == "budj") {
							$i = $tasoluku - 1;

							$summa[$kausi][$taso[$i]] += $tilirow[$kausi];
						}
						else {
							for ($i = $tasoluku - 1; $i >= 0; $i--) {
								$summa[$kausi][$taso[$i]] += $tilirow[$kausi];
							}
						}
					}
				}
			}

			// Haluaako k�ytt�j� n�h� kaikki kaudet
			if ($kaikkikaudet == "") {
				$alkukausi = count($kaudet)-2;

				if ($eiyhteensa == "") {
					if ($vertailued != "") $alkukausi-=2;
					if ($vertailubu != "") $alkukausi-=2;
				}
				else {
					if ($vertailued != "" and $vertailubu != "") $alkukausi-=1;
					if ($vertailued == "" and $vertailubu == "") $alkukausi+=1;
				}

			}
			else {
				$alkukausi = 0;
			}

			//	Laajempi pdf k�sittel
			require_once('pdflib/pupepdf.class.php');

			$pdf = new pdf;
			$pdf->set_default('margin', 0);
			$pdf->set_default('margin-left', 5);
			$rectparam["width"] = 0.3;

			$p["height"] 	= 10;
			$p["font"]	 	= "Times-Roman";
	        $b["height"]	= 8;
			$b["font"] 		= "Times-Bold";

			if (count($kaudet) > 10 and $kaikkikaudet != "") {
				$p["height"]--;
				$b["height"]--;
				$saraklev 			= 49;
				$yhteensasaraklev 	= 63;
				$vaslev 			= 150;
				$rivikork 			= 13;
			}
			else {
				$saraklev 			= 60;
				$yhteensasaraklev 	= 70;
				$vaslev 			= 160;
				$rivikork 			= 15;
			}

			if(!function_exists("alku")) {
				function alku () {
					global $yhtiorow, $kukarow, $firstpage, $pdf, $bottom, $kaudet, $saraklev, $rivikork, $p, $b, $otsikko, $alkukausi, $yhteensasaraklev, $vaslev;

					if(count($kaudet) > 5 and $kaikkikaudet != "") {
						$firstpage = $pdf->new_page("842x595");
						$bottom = "535";
					}
					else {
						$firstpage = $pdf->new_page("a4");
						$bottom = "782";
					}

					unset($data);

					if ((int) $yhtiorow["lasku_logo"] > 0) {
						$liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
						$data = $liite["data"];
						$isizelogo[0] = $liite["image_width"];
						$isizelogo[1] = $liite["image_height"];
						unset($liite);
					}
					elseif (file_exists($yhtiorow["lasku_logo"])) {
						$filename = $yhtiorow["lasku_logo"];

						$fh = fopen($filename, "r");
						$data = fread($fh, filesize($filename));
						fclose($fh);

						$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
					}

					if ($data) {
						$image = $pdf->jfif_embed($data);

						if(!$image) {
							echo t("Logokuvavirhe");
						}
						else {
							list($height, $width, $scale) = $pdf->scaleImage("$isizelogo[1]x$isizelogo[0]", "40x80");

	                        $placement = $pdf->image_place($image, ($bottom+$height-30), 10, $firstpage, array("scale" => $scale));
						}
					}
					else {
						$pdf->draw_text(10, ($bottom+30),  $yhtiorow["nimi"], $firstpage);
					}

					$pdf->draw_text(200,  ($bottom+30), $otsikko, $firstpage);

					$left = $vaslev;

					for ($i = $alkukausi; $i < count($kaudet); $i++) {
						$oikpos = $pdf->strlen($kaudet[$i], $b);
						if($i+1 == count($kaudet) and $eiyhteensa == "") {
							$lev = $yhteensasaraklev;
						}
						else {
							$lev = $saraklev;
						}

						$pdf->draw_text($left-$oikpos+$lev,  $bottom, $kaudet[$i], $firstpage, $b);

						$left += $saraklev;
					}

					$bottom -= $rivikork;
				}
			}

			alku();

			echo "<table>";

			// printataan headerit
			echo "<tr>";

			if ($toim == "TASOMUUTOS") {

				echo "	<form method='post'>
						<input type = 'hidden' name = 'tasomuutos' value = 'TRUE'>
						<input type = 'hidden' name = 'tee' value = 'tilitaso'>
						<input type = 'hidden' name = 'kirjain' value = '$kirjain'>
						<input type = 'hidden' name = 'taso' value = '$aputyyppi'>";

				$lopetus =  $palvelin2."raportit/tuloslaskelma.php////";

				foreach ($_REQUEST as $key => $value) {
					$lopetus .= $key."=".$value."//";
				}

				echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";

				echo "<td class='back' colspan='3'></td>";
			}
			else {
				echo "<td class='back' colspan='1'></td>";
			}

			for ($i = $alkukausi; $i < count($kaudet); $i++) {
				echo "<td class='tumma' align='right' valign='bottom'>$kaudet[$i]</td>";
			}
			echo "</tr>\n";

			// sortataan array indexin (tason) mukaan
			ksort($tasonimi);

			// loopataan tasot l�pi
			foreach ($tasonimi as $key_c => $value) {

				$key = str_replace("�", "", $key_c); // �-kirjaimet pois

				// tulostaan rivi vain jos se kuuluu rajaukseen
				if (strlen($key) <= $rtaso or $rtaso == "TILI") {

					if ($bottom < 20) {
						alku();
					}

					$class = "";

					// laitetaan ykk�s ja kakkostason rivit tummalla selkeyden vuoksi
					if (strlen($key) < 3 and $rtaso > 2) $class = "tumma";

					$rivi  = "<tr class='aktiivi'>";

					if ($toim == "TASOMUUTOS") {
						$rivi .= "<td class='back' nowrap><a href='?tasomuutos=TRUE&taso=$key&kirjain=$kirjain&tee=muuta&lopetus=$lopetus'>$key</a></td>";
						$rivi .= "<td class='back' nowrap><a href='?tasomuutos=TRUE&taso=$key&kirjain=$kirjain&edtaso=$edkey&tee=lisaa&lopetus=$lopetus'>".t("Lis�� taso tasoon")." $key</a></td>";
					}

					$tilirivi = "";

					if ($rtaso == "TILI") {

						$class = "tumma";

						$query = "SELECT * FROM tili WHERE yhtio = '$kukarow[yhtio]' and $tilikarttataso = BINARY '$key'";
						$tilires = mysql_query($query) or pupe_error($query);

						while ($tilirow = mysql_fetch_array($tilires)) {
							$query = "	SELECT tilino, $alkuquery2
										FROM tiliointi
										WHERE yhtio = '$kukarow[yhtio]'
										AND tilino = '$tilirow[tilino]'
										AND korjattu = ''
										AND tapvm >= '$totalalku'
										AND tapvm <= '$totalloppu'
										$lisa
										GROUP BY tilino";
							$summares = mysql_query($query) or pupe_error($query);
							$summarow = mysql_fetch_array($summares);
							$tilirivi2 = "";
							$tulos = 0;

							for ($tilii = $alkukausi + 1; $tilii < mysql_num_fields($summares); $tilii++) {
								$apu = sprintf($muoto, $summarow[$tilii] * $luku_kerroin / $tarkkuus);
								if ($apu == 0) $apu = "";

								$tilirivi2 .= "<td align='right' nowrap>".number_format($apu, $desi, ',', ' ')."</td>";
								if ($summarow[$tilii] != 0) $tulos++;
							}

							if ($tulos > 0 or $toim == "TASOMUUTOS") {

								$tilirivi .= "<tr>";

								if ($toim == "TASOMUUTOS") {
									$tilirivi .= "<td class='back' nowrap>$key</td>";
									$tilirivi .= "<td class='back' nowrap><input type='checkbox' name='tiliarray[]' value=\"'$tilirow[tilino]'\"></td>";
								}
								$tilirivi .= "<td nowrap>";

								if ($paakirjalink) {
									$tilirivi .= "<a href ='../raportit.php?toim=paakirja&tee=P&mista=tuloslaskelma&alvv=$alvv&alvk=$alvk&mul_kustp_seri=".base64_encode(serialize($mul_kustp))."&mul_kohde_seri=".base64_encode(serialize($mul_kohde))."&mul_proj_seri=".base64_encode(serialize($mul_proj))."&tili=$tilirow[tilino]$lopelinkki'>$tilirow[tilino] - $tilirow[nimi]</a>";
								}
								else {
									$tilirivi .= "$tilirow[tilino] - $tilirow[nimi]";
								}

								$tilirivi .= "</td>$tilirivi2</tr>";
							}
						}
					}

					$rivi .= "<th nowrap>$value</th>";

					$tulos = 0;

					for ($i = $alkukausi; $i < count($kaudet); $i++) {

						$query = "	SELECT summattava_taso
									FROM taso
									WHERE yhtio 		 = '$kukarow[yhtio]'
									and taso 			 = BINARY '$key'
									and summattava_taso != ''
									and tyyppi 			 = '$kirjain'";
						$summares = mysql_query($query) or pupe_error($query);

						// Budjettia ei summata
						if ($summarow = mysql_fetch_array ($summares) and substr($kaudet[$i],0,4) != "budj") {
							foreach(explode(",", $summarow["summattava_taso"]) as $staso) {
								$summa[$kaudet[$i]][$key] = $summa[$kaudet[$i]][$key] + $summa[$kaudet[$i]][$staso];
							}
						}

						// formatoidaan luku toivottuun muotoon
						$apu = sprintf($muoto, $summa[$kaudet[$i]][$key] * $luku_kerroin / $tarkkuus);

						if ($apu == 0) {
							$apu = ""; // nollat spaseiks
						}
						else {
							$tulos++; // summaillaan t�t� jos meill� oli rivill� arvo niin osataan tulostaa
						}

						$rivi .= "<td class='$class' align='right' nowrap>".number_format($apu, $desi,  ',', ' ')."</td>";
					}

					if ($toim == "TASOMUUTOS" and $summattavattasot[$key_c] != "") {
						$rivi .= "<td class='back' nowrap>".t("Summattava taso").": ".$summattavattasot[$key_c]."</td>";
					}

					$rivi .= "</tr>\n";

					// kakkostason j�lkeen aina yks tyhj� rivi.. paitsi jos otetaan vain kakkostason raportti
					if (strlen($key) == 2 and ($rtaso > 2 or $rtaso == "TILI")) {
						$rivi .= "<tr><td class='back'>&nbsp;</td></tr>";
					}

					// jos jollain kaudella oli summa != 0 niin tulostetaan rivi
					if ($tulos > 0 or $toim == "TASOMUUTOS") {

						echo $tilirivi, $rivi;

						$left = 10+(strlen($key)-1)*3;
						$pdf->draw_text($left,  $bottom, $value, $firstpage, $b);
						$left = $vaslev;

						for ($i = $alkukausi; $i < count($kaudet); $i++) {
							$oikpos = $pdf->strlen(number_format($summa[$kaudet[$i]][$key] * $luku_kerroin / $tarkkuus, $desi, ',', ' '), $p);

							if($i+1 == count($kaudet) and $eiyhteensa == "") {
								$lev = $yhteensasaraklev;
							}
							else {
								$lev = $saraklev;
							}

							$pdf->draw_text($left-$oikpos+$lev, $bottom, number_format($summa[$kaudet[$i]][$key] * $luku_kerroin / $tarkkuus, $desi, ',', ' '), $firstpage, $p);
							$left += $saraklev;
						}

						$bottom -= $rivikork;

						if (strlen($key) == 2 and ($rtaso > 2 or $rtaso == "TILI")) {
							$bottom -= $rivikork;
						}
					}
				}

				$edkey = $key;
			}

			echo "</table>";

			//	Projektikalenterilla ei sallita PDF tulostusta
			if($from != "PROJEKTIKALENTERI") {

				if ($toim == "TASOMUUTOS") {
					echo "<br><input type='submit' value='".t("Anna tileille taso")."'></form><br><br>";
				}

				//keksit��n uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$pdffilenimi = "Tuloslaskelma-".md5(uniqid(mt_rand(), true)).".pdf";

				//kirjoitetaan pdf faili levylle..
				$fh = fopen("/tmp/".$pdffilenimi, "w");
				if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF Error $pdffilenimi");
				fclose($fh);

				echo "<br><table>";
				echo "<tr><th>".t("Tallenna pdf").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='teetiedosto' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".urlencode($otsikko).".pdf'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$pdffilenimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}
		}

		//	UI vain jos sille on tarvetta
		if ($from != "PROJEKTIKALENTERI") {
			// tehd��n k�ytt�liittym�, n�ytet��n aina
			$sel = array();
			if ($tyyppi == "") $tyyppi = "4";
			$sel[$tyyppi] = "SELECTED";

			echo "<br>";
			echo "	<form action = 'tuloslaskelma.php' method='post'>
					<input type = 'hidden' name = 'tltee' value = 'aja'>
					<input type='hidden' name='toim' value='$toim'>
					<table>";

			echo "	<tr>
					<th valign='top'>".t("Tyyppi")."</th>
					<td>";

			echo "	<select name = 'tyyppi'>
					<option $sel[4] value='4'>".t("Sis�inen tuloslaskelma")."</option>
					<option $sel[3] value='3'>".t("Ulkoinen tuloslaskelma")."</option>
					<option $sel[1] value='1'>".t("Vastaavaa")." (".t("Varat").")</option>
					<option $sel[2] value='2'>".t("Vastattavaa")." (".t("Velat").")</option>
					</select>";

			echo "</td>
					</tr>";


			if (!isset($plvv)) {
				$query = "	SELECT *
							FROM tilikaudet
							WHERE yhtio = '$kukarow[yhtio]'
							and '".date("Y-m-d")."' >= tilikausi_alku
							and '".date("Y-m-d")."' <= tilikausi_loppu";
				$result = mysql_query($query) or pupe_error($query);
				$tilikausirow = mysql_fetch_array($result);

				$plvv = substr($tilikausirow['tilikausi_alku'], 0, 4);
				$plvk = substr($tilikausirow['tilikausi_alku'], 5, 2);
				$plvp = substr($tilikausirow['tilikausi_alku'], 8, 2);
			}

			echo "	<th valign='top'>".t("Alkukausi")."</th>
					<td><select name='plvv'>";

			$sel = array();
			$sel[$plvv] = "SELECTED";

			for ($i = date("Y"); $i >= date("Y")-4; $i--) {
				echo "<option value='$i' $sel[$i]>$i</option>";
			}

			echo "</select>";

			$sel = array();
			$sel[$plvk] = "SELECTED";

			echo "<select name='plvk'>
					<option $sel[01] value = '01'>01</option>
					<option $sel[02] value = '02'>02</option>
					<option $sel[03] value = '03'>03</option>
					<option $sel[04] value = '04'>04</option>
					<option $sel[05] value = '05'>05</option>
					<option $sel[06] value = '06'>06</option>
					<option $sel[07] value = '07'>07</option>
					<option $sel[08] value = '08'>08</option>
					<option $sel[09] value = '09'>09</option>
					<option $sel[10] value = '10'>10</option>
					<option $sel[11] value = '11'>11</option>
					<option $sel[12] value = '12'>12</option>
					</select>";

			$sel = array();
			$sel[$plvp] = "SELECTED";

			echo "<select name='plvp'>
					<option $sel[01] value = '01'>01</option>
					<option $sel[02] value = '02'>02</option>
					<option $sel[03] value = '03'>03</option>
					<option $sel[04] value = '04'>04</option>
					<option $sel[05] value = '05'>05</option>
					<option $sel[06] value = '06'>06</option>
					<option $sel[07] value = '07'>07</option>
					<option $sel[08] value = '08'>08</option>
					<option $sel[09] value = '09'>09</option>
					<option $sel[10] value = '10'>10</option>
					<option $sel[11] value = '11'>11</option>
					<option $sel[12] value = '12'>12</option>
					<option $sel[13] value = '13'>13</option>
					<option $sel[14] value = '14'>14</option>
					<option $sel[15] value = '15'>15</option>
					<option $sel[16] value = '16'>16</option>
					<option $sel[17] value = '17'>17</option>
					<option $sel[18] value = '18'>18</option>
					<option $sel[19] value = '19'>19</option>
					<option $sel[20] value = '20'>20</option>
					<option $sel[21] value = '21'>21</option>
					<option $sel[22] value = '22'>22</option>
					<option $sel[23] value = '23'>23</option>
					<option $sel[24] value = '24'>24</option>
					<option $sel[25] value = '25'>25</option>
					<option $sel[26] value = '26'>26</option>
					<option $sel[27] value = '27'>27</option>
					<option $sel[28] value = '28'>28</option>
					<option $sel[29] value = '29'>29</option>
					<option $sel[30] value = '30'>30</option>
					<option $sel[31] value = '31'>31</option>
					</select>
					</td></tr>";

			echo "<tr>
				<th valign='top'>".t("Loppukausi")."</th>
				<td><select name='alvv'>";

			$sel = array();
			if ($alvv == "") $alvv = date("Y");
			$sel[$alvv] = "SELECTED";

			for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
				echo "<option value='$i' $sel[$i]>$i</option>";
			}

			$sel = array();
			if ($alvk == "") $alvk = date("m");
			$sel[$alvk] = "SELECTED";

			echo "</select>";

			echo "<select name='alvk'>
					<option $sel[01] value = '01'>01</option>
					<option $sel[02] value = '02'>02</option>
					<option $sel[03] value = '03'>03</option>
					<option $sel[04] value = '04'>04</option>
					<option $sel[05] value = '05'>05</option>
					<option $sel[06] value = '06'>06</option>
					<option $sel[07] value = '07'>07</option>
					<option $sel[08] value = '08'>08</option>
					<option $sel[09] value = '09'>09</option>
					<option $sel[10] value = '10'>10</option>
					<option $sel[11] value = '11'>11</option>
					<option $sel[12] value = '12'>12</option>
					</select>";

			$sel = array();
			if ($alvp == "") $alvp = date("d", mktime(0, 0, 0, (date("m")+1), 0, date("Y")));
			$sel[$alvp] = "SELECTED";

			echo "<select name='alvp'>
					<option $sel[01] value = '01'>01</option>
					<option $sel[02] value = '02'>02</option>
					<option $sel[03] value = '03'>03</option>
					<option $sel[04] value = '04'>04</option>
					<option $sel[05] value = '05'>05</option>
					<option $sel[06] value = '06'>06</option>
					<option $sel[07] value = '07'>07</option>
					<option $sel[08] value = '08'>08</option>
					<option $sel[09] value = '09'>09</option>
					<option $sel[10] value = '10'>10</option>
					<option $sel[11] value = '11'>11</option>
					<option $sel[12] value = '12'>12</option>
					<option $sel[13] value = '13'>13</option>
					<option $sel[14] value = '14'>14</option>
					<option $sel[15] value = '15'>15</option>
					<option $sel[16] value = '16'>16</option>
					<option $sel[17] value = '17'>17</option>
					<option $sel[18] value = '18'>18</option>
					<option $sel[19] value = '19'>19</option>
					<option $sel[20] value = '20'>20</option>
					<option $sel[21] value = '21'>21</option>
					<option $sel[22] value = '22'>22</option>
					<option $sel[23] value = '23'>23</option>
					<option $sel[24] value = '24'>24</option>
					<option $sel[25] value = '25'>25</option>
					<option $sel[26] value = '26'>26</option>
					<option $sel[27] value = '27'>27</option>
					<option $sel[28] value = '28'>28</option>
					<option $sel[29] value = '29'>29</option>
					<option $sel[30] value = '30'>30</option>
					<option $sel[31] value = '31'>31</option>
					</select>
					</td></tr>";

			echo "<tr><th valign='top'>".t("tai koko tilikausi")."</th>";

			$query = "	SELECT *
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY tilikausi_alku DESC";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa")."";

			while ($vrow=mysql_fetch_array($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow["tunnus"]) {
					$sel = "selected";
				}
				echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"]);
			}
			echo "</select></td>";
			echo "</tr>";

			// haetaan kustannuspaikat
			$query = "	SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and kaytossa != 'E'
						and tyyppi = 'K'
						ORDER BY nimi";
			$k_result = mysql_query($query) or pupe_error($query);

			// haetaan kohteet
			$query = "	SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and kaytossa != 'E'
						and tyyppi = 'O'
						ORDER BY nimi";
			$o_result = mysql_query($query) or pupe_error($query);

			// haetaan projektit
			$query = "	SELECT tunnus, nimi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and kaytossa != 'E'
						and tyyppi = 'P'
						ORDER BY nimi";
			$p_result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($k_result) > 0 or
				mysql_num_rows($o_result) > 0 or
				mysql_num_rows($p_result) > 0) {

				echo "<tr><th valign='top'>";

				// piirret��n kustannuspaikat
				if (mysql_num_rows($k_result) > 0) {
					echo t("Kustannuspaikka")."<br>";
				}
				// piirret��n kohde
				if (mysql_num_rows($o_result) > 0) {
					echo t("Kohde")."<br>";
				}
				// piirret��n projekti
				if (mysql_num_rows($p_result) > 0) {
					echo t("Projekti")."<br>";
				}

				echo "</th><td valign='top'>";

				// piirret��n kustannuspaikat
				if (mysql_num_rows($k_result) > 0) {
					echo "<select name='mul_kustp[]' multiple='TRUE'>";

					$mul_check = '';
					if ($mul_kustp!="") {
						if (in_array("PUPEKAIKKIMUUT", $mul_kustp)) {
							$mul_check = 'SELECTED';
						}
					}

					echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei valintaa")."</option>";

					while ($vrow = mysql_fetch_array($k_result)) {
						$sel="";
						if ($trow[$i] == $vrow['tunnus'] or in_array($vrow["tunnus"],$mul_kustp)) {
							$sel = "selected";
						}
						echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
					}
					echo "</select>";
				}

				// piirret��n kohteet
				if (mysql_num_rows($o_result) > 0) {
					echo "<select name='mul_kohde[]' multiple='TRUE'>";

					$mul_check = '';
					if ($mul_kohde!="") {
						if (in_array("PUPEKAIKKIMUUT", $mul_kohde)) {
							$mul_check = 'SELECTED';
						}
					}
					echo "<option value='PUPEKAIKKIMUUT' $mul_check>Ei valintaa</option>";

					while ($vrow = mysql_fetch_array($o_result)) {
						$sel="";
						if ($trow[$i] == $vrow['tunnus'] or in_array($vrow["tunnus"],$mul_kohde)) {
							$sel = "selected";
						}
						echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
					}
					echo "</select>";
				}

				// piirret��n projektit
				if (mysql_num_rows($p_result) > 0) {
					echo "<select name='mul_proj[]' multiple='TRUE'>";

					$mul_check = '';
					if ($mul_proj!="") {
						if (in_array("PUPEKAIKKIMUUT", $mul_proj)) {
							$mul_check = 'SELECTED';
						}
					}
					echo "<option value='PUPEKAIKKIMUUT' $mul_check>".t("Ei valintaa")."</option>";

					while ($vrow=mysql_fetch_array($p_result)) {
						$sel="";
						if ($trow[$i] == $vrow['tunnus'] or in_array($vrow["tunnus"],$mul_proj)) {
							$sel = "selected";
						}
						echo "<option value = '$vrow[tunnus]' $sel>$vrow[nimi]</option>";
					}
					echo "</select>";
				}

				echo "</td></tr>";
			}

			$sel = array();
			$sel[$rtaso] = "SELECTED";

			echo "<tr><th valign='top'>".t("Raportointitaso")."</th>
					<td><select name='rtaso'>";

			$query = "SELECT max(length(taso)) taso from taso where yhtio = '$kukarow[yhtio]'";
			$vresult = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_array($vresult);

			echo "<option value='TILI'>".t("Tili taso")."</option>\n";

			for ($i=$vrow["taso"]-1; $i >= 0; $i--) {
				echo "<option ".$sel[$i+2]." value='".($i+2)."'>".t("Taso %s",'',$i+1)."</option>\n";
			}

			echo "</select></td></tr>";

			$sel = array();
			if ($tarkkuus == "") $tarkkuus = 1;
			$sel[$tarkkuus] = "SELECTED";

			echo "<tr><th valign='top'>".t("Lukujen taarkkuus")."</th>
					<td><select name='tarkkuus'>
						<option $sel[1]    value='1'>".t("�l� jaa lukuja")."</option>
						<option $sel[1000] value='1000'>".t("Jaa 1000:lla")."</option>
						<option $sel[10000] value='10000'>".t("Jaa 10 000:lla")."</option>
						<option $sel[100000] value='100000'>".t("Jaa 100 000:lla")."</option>
						<option $sel[1000000] value='1000000'>".t("Jaa 1 000 000:lla")."</option>
						</select>";

			$sel = array();
			if ($desi == "") $desi = "0";
			$sel[$desi] = "SELECTED";

			echo "<select name='desi'>
					<option $sel[0] value='0'>0 ".t("desimaalia")."</option>
					<option $sel[1] value='1'>1 ".t("desimaalia")."</option>
					<option $sel[2] value='2'>2 ".t("desimaalia")."</option>
					</select></td></tr>";

			$kauchek = $vchek = $bchek = $ychek = "";
			if ($kaikkikaudet != "") $kauchek = "SELECTED";
			if ($vertailued != "") $vchek = "CHECKED";
			if ($vertailubu != "") $bchek = "CHECKED";
			if ($eiyhteensa != "") $ychek = "CHECKED";

			echo "<tr><th valign='top'>".t("N�kym�")."</th>";

			echo "<td><select name='kaikkikaudet'>
					<option value=''>".t("N�yt� vain viimeisin kausi")."</option>
					<option value='o' $kauchek>".t("N�yt� kaikki kaudet")."</option>
					</select>
					<br>&nbsp;<input type='checkbox' name='eiyhteensa' $ychek> ".t("Ei yhteens�saraketta")."
					</td></tr>";

			echo "<tr><th valign='top'>".t("Vertailu")."</th>";
			echo "<td>";
			echo "&nbsp;<input type='checkbox' name='vertailued' $vchek> ".t("Edellinen vastaava");
			echo "<br>&nbsp;<input type='checkbox' name='vertailubu' $bchek> ".t("Budjetti");
			echo "</td></tr>";
			echo "</table><br>
			      <input type = 'submit' value = '".t("N�yt�")."'></form>";

			require("../inc/footer.inc");
		}
	}
?>