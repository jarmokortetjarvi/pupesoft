<?php

	// jos halutaan generoida ostotilaus t�m�n tilauksen riveist�
	// tarvitaan myyntitilauksen tunnus muuttujassa $otunnus

	if (!function_exists("tilauksesta_valmistustilaus")) {
		function tilauksesta_valmistustilaus($otunnus) {
			global $yhtiorow, $kukarow;

			$tilauksesta_valmistustilaus = "";

			$query = "	SELECT * 
						FROM lasku 
						WHERE yhtio	= '$kukarow[yhtio]' 
						and tunnus	= '$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$myytilrow = mysql_fetch_array($result);

			//otetaan ensin vain lis�varusteettomat tuotteet tai lis�varusteperheiden is�t
			$query = "	SELECT tilausrivin_lisatiedot.*, tilausrivi.*, jt+varattu kpl, tilausrivi.tunnus rivitunnus
						FROM tilausrivi
						LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.otunnus	='$otunnus'
						and tilausrivi.tyyppi != 'D'
						and var = 'R'
						and tilausrivi.perheid = 0
						and tilausrivi.perheid2 = 0
						ORDER BY tilausrivi.tunnus";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) > 0) {
				while ($rivi = mysql_fetch_array($result)) {
					
					$viesti = $myytilrow["tunnusnippu"]." ".$myytilrow["toim_nimi"];
					
					//	Tarkistetaan onko meill� jo otsikko
					$query = "	SELECT tunnus
								from lasku
								where yhtio 		= '$kukarow[yhtio]'
								and liitostunnus 	= '$tiltoi[tunnus]'
								and tila 			= 'V'
								and tilaustyyppi 	= 'w'
								and alatila 		= ''
								and toim_nimi		= '$myytilrow[toim_nimi]'
								and toim_nimitark	= '$myytilrow[toim_nimitark]'
								and toim_osoite		= '$myytilrow[toim_osoite]'
								and toim_postino	= '$myytilrow[toim_postino]'
								and toim_postitp	= '$myytilrow[toim_postitp]'
								and toim_maa		= '$myytilrow[toim_maa]'
								and viite			= '$viesti'
								and left(luontiaika,10) = left(now(),10)
								and laatija 		= '$kukarow[kuka]'";
					$jtsre = mysql_query($query) or pupe_error($query);

					// ei l�ydy, tehd��n uus otsikko
					if (mysql_num_rows($jtsre) == 0) {

						$query = "	INSERT INTO lasku SET
									nimi 				= '$myytilrow[toim_nimi]',
									nimitark 			= '$myytilrow[toim_nimi]',
									osoite 				= '$myytilrow[toim_nimi]',
									postino 			= '$myytilrow[toim_nimi]',
									postitp 			= '$myytilrow[toim_nimi]',
									maa 				= '$myytilrow[toim_nimi]',
									toim_nimi 			= '$toim_nimi',
									toim_nimitark 		= '$toim_nimitark',
									toim_osoite 		= '$toim_osoite',
									toim_postino 		= '$toim_postino',
									toim_postitp 		= '$toim_postitp',
									toim_maa 			= '$toim_maa',
									toimaika 			= '$myytilrow[toimaika]',
									kerayspvm 			= '$myytilrow[kerayspvm]',
									luontiaika			= now(),
									ketjutus			= '1',
									laatija 			= '$kukarow[kuka]',
									viesti 				= '$viesti',
									yhtio 				= '$kukarow[yhtio]',
									tilaustyyppi		= 'W',
									tila 				= 'V',								
									liitostunnus		= '9999999999',
									tunnusnippu			= '$myytilrow[tunnusnippu]'";
						$updre = mysql_query($query) or pupe_error($query);
						$tunnus = (string) mysql_insert_id();
						
						$tilauksesta_valmistustilaus = "<font class='message'>".t("Perustettiin valmistus %s", $kukarow["kieli"], $tunnus)."</font><br>";
					}
					else {
						// tilaus l�yty, otetaan tunnus
						$jtsro = mysql_fetch_array($jtsre);
						$tunnus = $jtsro["tunnus"];
					}
					
					if(!isset($laskurow)) {
						$query = "	SELECT * 
									FROM lasku 
									WHERE yhtio	= '$kukarow[yhtio]' 
									and tunnus	= '$tunnus'";
						$lresult = mysql_query($query) or pupe_error($query);
						$laskurow = mysql_fetch_array($lresult); 
					}
 					
					// $myy_sarjatunnus  --> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
					// $osto_sarjatunnus --> Jos halutaan automaattisesti linkata joku sarjanumero-olio tilausriviin
					
					$query = "	SELECT *
								FROM tuote
								WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$rivi[tuoteno]'";
					$tresult = mysql_query($query) or pupe_error($query);
					$trow = mysql_fetch_array($tresult);
					
					$kukarow["kesken"] 	= $tunnus;
					$kpl 				= $rivi["kpl"];
					$tuoteno			= $rivi["tuoteno"];
					$toimaika			= $myytilrow["toimaika"];
					$kerayspvm			= $myytilrow["kerayspvm"];
					$hinta				= 0;
					$var 				= "";
					$varasto			= "";
					$paikka				= "";
					
					
					// $perheid_lisaarivi on perustetun rivin tunnus
					require ("lisaarivi.inc");
					 
					
					//	P�ivitet��n tilausrivin lisatietoihin tilausrivitunnus
					$query = "	UPDATE tilausrivin_lisatiedot SET	
									tilausrivilinkki 	= '$perheid_lisaarivi', 
									muutospvm			= now(),
									muuttaja			= '$kukarow[kuka]'
								WHERE yhtio	= '$kukarow[yhtio]' and tilausrivitunnus = '$rivi[tunnus]'";
					$updres = mysql_query($query) or pupe_error($query);


					//Kopioidaan otsikon lisatiedot
					$query = "SELECT * FROM laskun_lisatiedot WHERE otunnus='$otunnus' and yhtio ='$kukarow[yhtio]'";
					$lisatiedotres = mysql_query($query) or pupe_error($query);
					$lisatiedotrow = mysql_fetch_array($lisatiedotres);

					$fields = mysql_field_name($lisatiedotres,0);
					$values = "'".$lisatiedotrow[0]."'";

					for($i=1; $i < mysql_num_fields($lisatiedotres)-1; $i++) { // Ei monisteta tunnusta

						$fields .= ", ".mysql_field_name($lisatiedotres,$i);

						switch (mysql_field_name($lisatiedotres,$i)) {
							case 'otunnus':
								$values .= ", '$tunnus'";
								break;
							default:
								$values .= ", '".$lisatiedotrow[$i]."'";
						}
					}

					$kysely  = "INSERT into laskun_lisatiedot ($fields) VALUES ($values)";
					$insres2 = mysql_query($kysely) or pupe_error($kysely);
					
					//	P�ivitet��n my�s tilausriville oikeammat tiedot!
					$query = "	SELECT *
								FROM tilausrivin_lisatiedot
								WHERE tilausrivitunnus='$rivi[tunnus]' and yhtio ='$kukarow[yhtio]'";
					$trlisatiedotres = mysql_query($query) or pupe_error($query);
					$trlisatiedotrow = mysql_fetch_array($trlisatiedotres);

					$values = mysql_field_name($trlisatiedotres,0)." = '".$trlisatiedotrow[0]."'";

					for($i=1; $i < mysql_num_fields($trlisatiedotres)-1; $i++) { // Ei monisteta tunnusta

						switch (mysql_field_name($trlisatiedotres,$i)) {
							case 'tilausrivitunnus':
								$values .= ", ".mysql_field_name($trlisatiedotres,$i)." = '$perheid_lisaarivi'";
								break;
							case 'tilausrivilinkki':
								$values .= ", ".mysql_field_name($trlisatiedotres,$i)." = ''";
								break;
							case 'pituus':
								$pituus = $trlisatiedotrow[$i];
								$uusiPituus = $pituus;
								$values .= ", ".mysql_field_name($trlisatiedotres,$i)." = '1000'";
								break;
							default:
								$values .= ", ".mysql_field_name($trlisatiedotres,$i)." = '".$trlisatiedotrow[$i]."'";
						}
					}
					
					$kysely  = "UPDATE tilausrivin_lisatiedot SET $values WHERE yhtio='$kukarow[yhtio]' and tilausrivitunnus = '$perheid_lisaarivi'";
					$insres2 = mysql_query($kysely) or pupe_error($kysely);
					
					if($uusiPituus > 0) {
						//	Fiksataan viel� rakenteen osapalikat jos meill� on multidimensionaalinen kappale valmistettavana
						$query = "	SELECT 	tilausrivi.tunnus, tuote.vaaditaan_kpl2,
											if(tilausrivin_lisatiedot.pituus>0, tilausrivi.hinta/(tilausrivin_lisatiedot.pituus/1000), tilausrivi.hinta) yksikkohinta,
											if(tilausrivin_lisatiedot.pituus>0, tilausrivi.tilkpl/(tilausrivin_lisatiedot.pituus/1000), tilausrivi.tilkpl) yksikkotilkpl,
											if(tilausrivin_lisatiedot.pituus>0, tilausrivi.varattu/(tilausrivin_lisatiedot.pituus/1000), tilausrivi.varattu) yksikkovarattu
									FROM tilausrivi use index (yhtio_otunnus)
									LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
									LEFT JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
									WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
									and tilausrivi.otunnus = '$tunnus'
									and (tilausrivi.tunnus = '$perheid_lisaarivi' or (tilausrivi.perheid!=0 and tilausrivi.perheid = '$perheid_lisaarivi' and (tilausrivin_lisatiedot.ei_nayteta = 'P' or tilausrivi.tyyppi IN ('W','V'))) or (tilausrivi.perheid2!=0 and tilausrivi.perheid2 = '$perheid_lisaarivi' and (tilausrivin_lisatiedot.ei_nayteta = 'P' or tilausrivi.tyyppi IN ('W','V'))))
									ORDER BY tunnus";
						$lapsires = mysql_query($query) or pupe_error($query);

						while($lapsi = mysql_fetch_array($lapsires)) {

							//	P�ivitet��n positio tai rivityyppi
							$query = "	UPDATE tilausrivin_lisatiedot
										SET $lisaalisa
										muutospvm				= now(),
										muuttaja				= '$kukarow[kuka]'
										WHERE yhtio			 = '$kukarow[yhtio]'
										and tilausrivitunnus = '$lapsi[tunnus]'";
							$result = mysql_query($query) or pupe_error($query);

							//	Fiksataan m��r�� tai hinta
							if(in_array($lapsi["vaaditaan_kpl2"], array("P","K","M")) and (isset($asiakkaan_positio) or isset($pituus))) {

								//	Lasketaan kertoimet, ekana tulee aina perheen faija
								if(!isset($uusiPituus)) {
									if($lapsi["vaaditaan_kpl2"] == "P") {
										$query  = "	SELECT pituus
													FROM asiakkaan_positio
													WHERE yhtio = '$kukarow[yhtio]'
													and tunnus = '$asiakkaan_positio'";
										$posres = mysql_query($query) or pupe_error($query);
										$posrow = mysql_fetch_array($posres);
										$uusiPituus = $posrow["pituus"];						
									}
									elseif($lapsi["vaaditaan_kpl2"] == "M") {
										$uusiPituus = $pituus;
									}

									//	Varmistetaan, ett� saadaan aina jotain lukuja
									if( (int) $uusiPituus == 0) {
										$uusiPituus == 10000;
									}
								}

								if($lapsi["vaaditaan_kpl2"] == "P") {
									$uhinta = round(($uusiPituus * $lapsi["yksikkohinta"])/1000, $yhtiorow['hintapyoristys']);

									$query = "	UPDATE tilausrivi
												SET hinta = '$uhinta'
												WHERE yhtio = '$kukarow[yhtio]' and tunnus = '{$lapsi["tunnus"]}'";
									$updre = mysql_query($query) or pupe_error($query);					
								}
								elseif($lapsi["vaaditaan_kpl2"] == "K") {
									$uvarattu = $uusiPituus*($lapsi["yksikkovarattu"]/1000);
									$utilkpl = $uusiPituus*($lapsi["yksikkotilkpl"]/1000);

									$query = "	UPDATE tilausrivi
												SET varattu = '$uvarattu',
													tilkpl	= '$utilkpl'
												WHERE yhtio = '$kukarow[yhtio]' and tunnus = '{$lapsi["tunnus"]}'";
									$updre = mysql_query($query) or pupe_error($query);
									//echo $query."<br>";
								}

								//	Tallennetaan t�m� pituus viel� lis�tietoihin
								$query = "	UPDATE tilausrivin_lisatiedot
											SET pituus 	= '$uusiPituus',
											muutospvm	= now(),
											muuttaja	= '$kukarow[kuka]'
											WHERE yhtio = '$kukarow[yhtio]' and tilausrivitunnus = '{$lapsi["tunnus"]}'";
								$updre = mysql_query($query) or pupe_error($query);
							}
						}
					}					
					
					//	Tehd��n rivist� normi JT
					$query = "update tilausrivi set var='J' where yhtio='$kukarow[yhtio]' and tunnus='$rivi[tunnus]'";
					$updre = mysql_query($query) or pupe_error($query);						
				}
			}
			return $tilauksesta_valmistustilaus;
		}
	}
?>