<?php

	// jos tilauksella oli suoratoimitusrivej�, niin tehd��n niist� ostotilaus
	// tarvitaan myyntitilauksen tunnus muuttujassa $otunnus

	if (!function_exists("jt_super")) {
		function jt_super($otunnus) {
			global $yhtiorow, $kukarow;

			//Laitetaan kaikki outputti muuttujaan
			$jt_superulos = "";

			$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$myytilrow = mysql_fetch_array($result);

			$query = "select * from tilausrivi where yhtio='$kukarow[yhtio]' and otunnus='$otunnus' and var='S' and jt<>0 and tyyppi='L'";
			$result = mysql_query($query) or pupe_error($query);

			$superostot = array();
			$supercommentti = t("Tilauksella on tuotteita my�s suoratoimituksessa").":\n";

			if (mysql_num_rows($result) > 0) {

				while ($rivi = mysql_fetch_array($result)) {

					$sarjacomment 	= "";
					$perhecomm 		= "";
					
					// tehd��n aluksi v�h�n oikeellisuustarkastuksia
					$query = "select * from toimi where yhtio='$kukarow[yhtio]' and tunnus='$rivi[tilaajanrivinro]'";
					$erres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($erres) != 1) {
						$jt_superulos .= "<font class='error'>Suoratoimitusrivin toimittajan tietoja ei l�ytynyt (toimittajan id: '$rivi[tilaajanrivinro]'). Muutettiin rivi tavalliseksi JT:ksi.</font><br>";
					}
					else {
						// toimittaja l�ytyi, tehd��n t�st� ostotilaus
						$tiltoi = mysql_fetch_array($erres);

						// katsotaan onko toimittajalla avoimia ostotilauksia
						$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and liitostunnus='$tiltoi[tunnus]' and tila='O' and alatila='' and toimaika=now() and laatija='$kukarow[kuka]'";
						$jtsre = mysql_query($query) or pupe_error($query);

						// ei l�ydy, tehd��n uus otsikko
						if (mysql_num_rows($jtsre) == 0) {

							$query = "	INSERT INTO lasku SET
										ytunnus				= '$tiltoi[ytunnus]',
										ovttunnus 			= '$tiltoi[ovttunnus]',
										nimi 				= '$tiltoi[nimi]',
										nimitark 			= '$tiltoi[nimitark]',
										osoite 				= '$tiltoi[osoite]',
										postino 			= '$tiltoi[postino]',
										postitp 			= '$tiltoi[postitp]',
										maa 				= '$tiltoi[maa]',
										toim_ovttunnus		= '$tiltoi[ovttunnus]',
										toim_nimi 			= '$myytilrow[toim_nimi]',
										toim_nimitark 		= '$myytilrow[toim_nimitark] ($myytilrow[ytunnus])',
										toim_osoite 		= '$myytilrow[toim_osoite]',
										toim_postino 		= '$myytilrow[toim_postino]',
										toim_postitp 		= '$myytilrow[toim_postitp]',
										toim_maa 			= '$myytilrow[toim_maa]',
										verkkotunnus		= '$myytilrow[liitostunnus]',
										toimaika 			= now(),
										kerayspvm 			= now(),
										luontiaika			= now(),
										ketjutus			= '1',
										maksuehto 			= '$tiltoi[maksuehto]',
										toimitustapa 		= '$myytilrow[toimitustapa]',
										toimitusehto 		= '$tiltoi[toimitusehto]',
										laatija 			= '$kukarow[kuka]',
										alv 				= '$tiltoi[alv]',
										comments 			= '$tiltoi[comments]',
										viesti 				= '$myytilrow[viesti]',
										kuljetus 			= '$tiltoi[kuljetus]',
										huolitsija 			= '$tiltoi[huolitsija]',
										maksuteksti 		= '$tiltoi[maksuteksti]',
										yhtio 				= '$kukarow[yhtio]',
										tilaustyyppi		= '$tiltoi[tilaustyyppi]',
										liitostunnus		= '$tiltoi[tunnus]',
										valkoodi 			= '$tiltoi[valkoodi]',
										viikorkopros 		= '$yhtiorow[viivastyskorko]',
										tila 				= 'O'";
							$updre = mysql_query($query) or pupe_error($query);
							$tunnus = (string) mysql_insert_id();
						}
						else {
							// tilaus l�yty, otetaan tunnus
							$jtsro = mysql_fetch_array($jtsre);
							$tunnus = $jtsro["tunnus"];
						}

						//tilausnumerot talteen, n�m� pit�� sitten lopuks l�hett�� ja merkata valmiiks
						if (!in_array($tunnus, $superostot)) $superostot[] = $tunnus;

						// katotaan mik� varasto on kysess� mist� halutaan tilata, niin laitetaan sen tunnus hyllyalue kentt��n (#TODO t�m� kusee jos varaston tunnus on yli 5 merkki�!)
						$varastorotunnus = kuuluukovarastoon($rivi["hyllyalue"], $rivi["hyllynro"], "", $tiltoi["tyyppi_tieto"]);

						// katotaan onko t�m� myyntirivi tunnus linkattu johonkin sarjanumeroon
						$sarjaquery = "select * from sarjanumeroseuranta where yhtio='$kukarow[yhtio]' and myyntirivitunnus='$rivi[tunnus]'";
						$sarjaresult = mysql_query($sarjaquery) or pupe_error($sarjaquery);

						// jos l�ydettiin sarjanumero, niin laitetaan se kommenttiin..
						if (mysql_num_rows($sarjaresult) == 1) {
							$sarjarow = mysql_fetch_array($sarjaresult);
							$sarjacomment = "$sarjarow[tunnus]";
						}
						
						if($rivi["perheid"] != 0) {
							$query = "select nimitys from tilausrivi where tunnus='$rivi[perheid]'";
							$vrnimire = mysql_query($query) or pupe_error($query);
							$vrnimiro = mysql_fetch_array($vrnimire);

							$perhecomm = t("Tuoteperhe:")." ".$vrnimiro["nimitys"];	
						}

						// lis�t��n ostotilausrivi
						$query = "	insert into tilausrivi
									(tuoteno, hinta, tilkpl, varattu, otunnus, yhtio, tyyppi, kommentti, toimaika, hyllyalue, hyllynro, hyllyvali, hyllytaso, tilaajanrivinro) values
									('$rivi[tuoteno]', '$rivi[hinta]', '$rivi[jt]', '$rivi[jt]', '$tunnus', '$kukarow[yhtio]', 'O', '".trim("$rivi[kommentti] $sarjacomment $perhecomm")."', '$rivi[toimaika]', '$varastorotunnus','0','0','0','$rivi[tunnus]')";
						$updre = mysql_query($query) or pupe_error($query);
						$rivitunnus = (string) mysql_insert_id();

						// jos l�ydettiin sarjanumero, niin linkataan nyt sitte t�m� ostorivi sarjanumeroon
						if (mysql_num_rows($sarjaresult) == 1) {
							$query = "	UPDATE sarjanumeroseuranta
										SET	ostorivitunnus = '$rivitunnus'
										WHERE tunnus='$sarjarow[tunnus]'";
							$sres = mysql_query($query) or pupe_error($query);
						}

						// haetaan viel� varaston nimi kommenttia varten
						$query = "select nimitys from varastopaikat where tunnus='$varastorotunnus'";
						$vrnimire = mysql_query($query) or pupe_error($query);
						$vrnimiro = mysql_fetch_array($vrnimire);

						$supercommentti .= "$tiltoi[nimi]: $vrnimiro[nimitys] - $rivi[tuoteno] $rivi[jt] kpl\n";

					}

					
					// tehd��n rivist� tavallinen JT, perheidkin nollataan
					$query = "update tilausrivi set var='J', perheid=0, kommentti=concat_ws(' ', kommentti, '$perhecomm') where yhtio='$kukarow[yhtio]' and tunnus='$rivi[tunnus]'";
					$updre = mysql_query($query) or pupe_error($query);

				}

				// l�hetet��n luodut ostotilaukset (ainoastaa edi)
				$komento['Ostotilaus'] = "edi";

				foreach ($superostot as $otunnus) {

					$palautus = 0;
					$silent = "SILENT";

					require('tulosta_ostotilaus.inc');

					$jt_superulos .= $tulosta_ostotilaus_ulos;
					$jt_superulos .= $pupesoft_ediout_ulos;

					if ($palautus == 0) {
						$query = "UPDATE lasku SET alatila='A' WHERE yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
						$result = mysql_query($query) or pupe_error($query);
					}
					else {
						$jt_superulos .= "<font class='error'>Ostotilauksen l�hetys ep�onnistui! K�y tekem�ss� se k�sin!</font><br>";
					}
				}

				// laitetaan viesti ker�yslistalle
				$query = "update lasku set sisviesti2=concat(sisviesti2,' $supercommentti') where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			return $jt_superulos;
		}
	}
?>
