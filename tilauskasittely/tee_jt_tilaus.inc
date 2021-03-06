<?php

	// $tunnukset jossa on k�sitelt�vien tilausrivien tunnukset (tuoteperhe)
	// $tunnusarray array jossa on k�sitelt�vien tilausrivien tunnukset arrayssa
	// $kpl[$tunnukset]   = monta toimitetaan
	// $loput[$tunnukset] = KAIKKI/POISTA/JATA/MITA/SUORA mit� tehd��n lopuille
	// $suoratoimpaikka[$tunnukset] = paikat josta myyd��n suoratoimituksia
	// $tilaus_on_jo = KYLLA niin lis�t��n rivit $kukarow[kesken] tilaukselle
	// $olpaikalta = pakotetaan myym��n oletuspaikalta
	// $varataan_saldoa = varataanko saldoa
	// $alv = alviprossa
	// $varastosta = mist� varastosta myyd��n

	if (!function_exists("tee_jt_tilaus")) {
		function tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimpaikka = '', $tilaus_on_jo = '', $varastosta = '', $olpaikalta = '', $varataan_saldoa = '', $alv = '') {
			global $yhtiorow, $kukarow, $toim, $automaattinen_poiminta;

			$debug = 0;

			if ($yhtiorow["varaako_jt_saldoa"] != "") {
				$lisavarattu = " + tilausrivi.varattu";
			}
			else {
				$lisavarattu = "";
			}

			if ($loput[$tunnukset] == 'MITA') {
				if ($toim == "ENNAKKO") {
					$query = "	UPDATE tilausrivi SET
								tyyppi = 'D'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus 	in ($tunnukset)
								and kpl		= 0
								and tyyppi	= 'E'";
				}
				else {
					$query = "	UPDATE tilausrivi SET
								tyyppi = 'D'
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus 	in ($tunnukset)
								and kpl		= 0
								and var		= 'J'
								and tyyppi in ('L','G')";
				}
				$tresult = pupe_query($query);

				$query = "UPDATE sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and myyntirivitunnus in ($tunnukset)";
				$sarjares = pupe_query($query);

			}
			else {
				if ($toim == "ENNAKKO") {
					$query = "	SELECT tilausrivi.*, tuote.ei_saldoa, tilausrivin_lisatiedot.suoraan_laskutukseen, lasku.vienti_kurssi, tilausrivin_lisatiedot.tilausrivilinkki
								FROM tilausrivi use index (PRIMARY)
								JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
								JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tunnus  = '$tunnusarray[0]'
								and tilausrivi.varattu > 0
								and tilausrivi.tyyppi  = 'E'";
				}
				else {
					$query = "	SELECT tilausrivi.*, tilausrivi.jt $lisavarattu jt, tuote.ei_saldoa, tilausrivin_lisatiedot.suoraan_laskutukseen, lasku.osatoimitus, lasku.vienti_kurssi, tilausrivin_lisatiedot.tilausrivilinkki
								FROM tilausrivi use index (PRIMARY)
								JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
								JOIN tuote ON tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
								LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
								WHERE tilausrivi.yhtio 			= '$kukarow[yhtio]'
								and tilausrivi.tunnus  			= '$tunnusarray[0]'
								and tilausrivi.jt $lisavarattu	> 0
								and tilausrivi.kpl     			= 0
								and tilausrivi.var     			= 'J'
								and tilausrivi.tyyppi 			in ('L','G')";
				}
				$rivitres = pupe_query($query);

				if (mysql_num_rows($rivitres) == 0) {
					echo "Tilausrivi� '$tunnusarray[0]' ei l�ytynyt<br><br>$query";
					exit;
				}

				///* T�m� on alkuper�isen rivinipun eka ja vanhin rivi, k�ytet��n sit� otsikon tekoa varten *///
				$isarivirow = mysql_fetch_assoc($rivitres);

				///* Etsit��n alkuper�isen-rivin laskun kaikki tiedot *///
				if ($tilaus_on_jo == "KYLLA") {
					//Tullaan myntitilauksen jtrivijutusta
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio	= '$kukarow[yhtio]' and
								tunnus		= '$kukarow[kesken]' and
								tila        in ('N', 'L', 'E', 'G', 'C')";
					$stresult = pupe_query($query);

					if (mysql_num_rows($stresult) == 0) {
						echo "Otsikkoa '$kukarow[kesken]' ei l�ytynyt";
						exit;
					}

					$query = "	SELECT *
								FROM laskun_lisatiedot
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$kukarow[kesken]'";
					$lisatiedot_result = pupe_query($query);
					$lisatiedot_row = mysql_fetch_assoc($lisatiedot_result);
				}
				elseif ($isarivirow["osatoimitus"] != "") {
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus	= '$isarivirow[otunnus]'
								and tila	in ('N','L')
								and alatila != 'X'";
					$stresult = pupe_query($query);

					if (mysql_num_rows($stresult) == 0) {
						echo "Otsikkoa '$isarivirow[otunnus]' ei l�ytynyt";
						exit;
					}

					$query = "	SELECT *
								FROM laskun_lisatiedot
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$isarivirow[otunnus]'";
					$lisatiedot_result = pupe_query($query);
					$lisatiedot_row = mysql_fetch_assoc($lisatiedot_result);
				}
				else {
					// tila pit�� olla l�hete, tilaus, ennakko tai siirtolista
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]'
								and tunnus = '$isarivirow[otunnus]'
								and tila in ('N', 'L', 'E', 'G', 'C')";
					$stresult = pupe_query($query);

					if (mysql_num_rows($stresult) == 0) {
						echo "Otsikkoa '$isarivirow[otunnus]' ei l�ytynyt";
						exit;
					}
					$otsikkorivi = mysql_fetch_assoc($stresult);

					$query = "	SELECT *
								FROM laskun_lisatiedot
								WHERE yhtio = '$kukarow[yhtio]'
								AND otunnus = '$isarivirow[otunnus]'";
					$lisatiedot_result = pupe_query($query);
					$lisatiedot_row = mysql_fetch_assoc($lisatiedot_result);

					if ($debug==1) echo t("Perusotsikko l�ytyi")." $otsikkorivi[nimi] $isarivirow[otunnus]<br>";

					$erpcmlisa1 = "";
					$erpcmlisa2 = "";

					if ($toim == "ENNAKKO") {
						// Tutkitaan perusotsikon er�p�iv�m��r��
						$erpquery = "	SELECT *
										from maksuehto
										where yhtio = '$kukarow[yhtio]'
										and tunnus = '$otsikkorivi[maksuehto]'";
						$erpres = pupe_query($erpquery);
						$meapurow = mysql_fetch_assoc($erpres);

						list($erpcmvv, $erpcmkk, $erpcmpp) = explode('-', $otsikkorivi["erpcm"]);

						$nyt = (int) date("Ymd");
						$erp = (int) $erpcmvv.$erpcmkk.$erpcmpp;

						//Jos alkuper�isell� tilauksella on er�p�iv�m��r� sy�tetty k�sin niin huomioidaan se nyt rivien poiminnassa
						if ($meapurow["erapvmkasin"] != "" and checkdate($erpcmkk, $erpcmpp, $erpcmvv) and $erp > $nyt) {
							$erpcmlisa1 = " and lasku.erpcm = '$otsikkorivi[erpcm]' ";
							$erpcmlisa2 = " erpcm = '$otsikkorivi[erpcm]', ";
						}

						$tila		= 'N';
						$alatila	= 'E';
					}
					elseif ($otsikkorivi['tila']== 'G') {
						$tila 		= 'G';
						$alatila 	= 'P';
					}
					else {
						$tila		= 'N';
						$alatila	= 'J';
					}

					// Vain saman alkuper�isen tilauksen rivej� samalle otsikolle
					if ($otsikkorivi['jtkielto'] == "Y") {
						// v��rink�ytet��n hyvaksyja_nyt kentt��...
						$yhdlisa = " and lasku.hyvaksyja_nyt = '$otsikkorivi[tunnus]' ";
					}
					else {
						$yhdlisa = "";
					}

					// Tarvitsemmeko uuden otsikon?
					$query = "	SELECT lasku.*
								FROM lasku
								JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
								WHERE lasku.yhtio		= '$kukarow[yhtio]'
								and lasku.laatija		= '$kukarow[kuka]'
								and lasku.alatila		= '$alatila'
								and lasku.tila 			= '$tila'
								and lasku.myyja			= '$otsikkorivi[myyja]'
								and lasku.ytunnus		= '$otsikkorivi[ytunnus]'
								and lasku.nimi 			= '$otsikkorivi[nimi]'
								and lasku.nimitark 		= '$otsikkorivi[nimitark]'
								and lasku.osoite 		= '$otsikkorivi[osoite]'
								and lasku.postino		= '$otsikkorivi[postino]'
								and lasku.postitp 		= '$otsikkorivi[postitp]'
								and lasku.toim_nimi		= '$otsikkorivi[toim_nimi]'
								and lasku.toim_nimitark	= '$otsikkorivi[toim_nimitark]'
								and lasku.toim_osoite 	= '$otsikkorivi[toim_osoite]'
								and lasku.toim_postino 	= '$otsikkorivi[toim_postino]'
								and lasku.toim_postitp 	= '$otsikkorivi[toim_postitp]'
								and lasku.toimitustapa 	= '$otsikkorivi[toimitustapa]'
								and lasku.maksuehto 	= '$otsikkorivi[maksuehto]'
								and lasku.vienti	 	= '$otsikkorivi[vienti]'
								and lasku.alv		 	= '$otsikkorivi[alv]'
								and lasku.ketjutus 		= '$otsikkorivi[ketjutus]'
								and lasku.kohdistettu	= '$otsikkorivi[kohdistettu]'
								and lasku.toimitusehto	= '$otsikkorivi[toimitusehto]'
								and lasku.valkoodi 		= '$otsikkorivi[valkoodi]'
								and lasku.vienti_kurssi	= '$otsikkorivi[vienti_kurssi]'
								and lasku.erikoisale	= '$otsikkorivi[erikoisale]'
								and lasku.eilahetetta	= '$isarivirow[suoraan_laskutukseen]'
								and lasku.tunnusnippu	= '$otsikkorivi[tunnusnippu]'
								and lasku.piiri			= '$otsikkorivi[piiri]'
								and laskun_lisatiedot.laskutus_nimi 	= '$lisatiedot_row[laskutus_nimi]'
								and laskun_lisatiedot.laskutus_nimitark = '$lisatiedot_row[laskutus_nimitark]'
								and laskun_lisatiedot.laskutus_osoite 	= '$lisatiedot_row[laskutus_osoite]'
								and laskun_lisatiedot.laskutus_postino 	= '$lisatiedot_row[laskutus_postino]'
								and laskun_lisatiedot.laskutus_postitp 	= '$lisatiedot_row[laskutus_postitp]'
								and laskun_lisatiedot.laskutus_maa 		= '$lisatiedot_row[laskutus_maa]'
								$erpcmlisa1
								$yhdlisa";
					$stresult = pupe_query($query);
				}

				if (mysql_num_rows($stresult) != 0) {
					$otsikkorivi = mysql_fetch_assoc($stresult);
					$id = $otsikkorivi['tunnus'];
					if ($debug == 1) echo t("Lis��n laskun olemassaolevaan")." $otsikkorivi[nimi] $id<br>";
				}
				else {
					// tehd��n vanhasta laskusta 1:1 kopio...
					$query = "INSERT into lasku set ";

					for ($i=0; $i<mysql_num_fields($stresult); $i++) {

						$fieldname = mysql_field_name($stresult, $i);

						// Tilaan laitetaan jotain speciaalia
						if ($fieldname == 'tila') {
							if ($toim == "ENNAKKO") {
								$query .= "tila='N',";
							}
							elseif ($otsikkorivi['tila']== 'G') {
								$query .= "tila='G',";
							}
							else {
								$query .= "tila='N',";
							}
						}
						// alatilaan laitetaan jotain speciaalia
						elseif ($fieldname == 'alatila') {
							if ($toim == "ENNAKKO") {
								$query .= "alatila='E',";
							}
							elseif ($otsikkorivi['tila']== 'G') {
								$query .= "alatila='P',";
							}
							else {
								$query .= "alatila='J',";
							}
						}
						// tilaustyyppi
						elseif ($fieldname == 'tilaustyyppi') {
							if ($toim == "ENNAKKO") {
								$query .= "tilaustyyppi='N',";
							}
							elseif ($otsikkorivi['tila']== 'G') {
								$query .= "tilaustyyppi='".$otsikkorivi[$fieldname]."',";
							}
							else {
								$query .= "tilaustyyppi='".$otsikkorivi[$fieldname]."',";
							}
						}
						// laatijaksi klikkaaja
						elseif ($fieldname == 'laatija') {
							$query .= "laatija='$kukarow[kuka]',";
						}
						// ker�ysaika ja toimitusaikaan now
						elseif ($fieldname == 'kerayspvm' or
								$fieldname == 'toimaika') {
							$query .= $fieldname."=now(),";
						}
						// Katotaan oisko k�sinsy�tetty er�p�iv�m��r�
						elseif ($fieldname == 'erpcm') {
							if ($erpcmlisa2 != '') {
								$query .= $erpcmlisa2;
							}
							else {
								$query .= "erpcm='',";
							}
						}
						// n�m� kent�t tyhjennet��n aina
						elseif ($fieldname == 'kapvm' or
								$fieldname == 'tapvm' or
								$fieldname == 'erpcm' or
								$fieldname == 'olmapvm' or
								$fieldname == 'summa' or
								$fieldname == 'kasumma' or
								$fieldname == 'hinta' or
								$fieldname == 'kate' or
								$fieldname == 'arvo' or
								$fieldname == 'maksuaika' or
								$fieldname == 'lahetepvm' or
								$fieldname == 'h1time' or
								$fieldname == 'viite' or
								$fieldname == 'laskunro' or
								$fieldname == 'mapvm' or
								$fieldname == 'kerayslista' or
								$fieldname == 'viikorkoeur' or
								$fieldname == 'tullausnumero' or
								$fieldname == 'laskutuspvm' or
								$fieldname == 'laskuttaja' or
								$fieldname == 'laskutettu' or
								$fieldname == 'lahetepvm' or
								$fieldname == 'maksaja' or
								$fieldname == 'maksettu' or
								$fieldname == 'maa_maara' or
								$fieldname == 'kuljetusmuoto' or
								$fieldname == 'kauppatapahtuman_luonne' or
								$fieldname == 'sisamaan_kuljetus' or
								$fieldname == 'sisamaan_kuljetusmuoto' or
								$fieldname == 'poistumistoimipaikka' or
								$fieldname == 'vanhatunnus' or
								$fieldname == 'poistumistoimipaikka_koodi') {
							$query .= $fieldname."='',";
						}
						elseif ($fieldname == 'tilausvahvistus') {

							$tv_query = "	SELECT tilausvahvistus, tilausvahvistus_jttoimituksista
											FROM asiakas
											where yhtio = '$yhtiorow[yhtio]'
											and tunnus = '$otsikkorivi[liitostunnus]'";
							$tv_res = pupe_query($tv_query);
							$tv_row = mysql_fetch_assoc($tv_res);

							if ($tv_row["tilausvahvistus_jttoimituksista"] == "K" or ($yhtiorow["tilausvahvistus_jttoimituksista"] == "K" and $tv_row["tilausvahvistus_jttoimituksista"] != "E")) {
								if (trim($otsikkorivi["tilausvahvistus"]) != "") {
									$query .= $fieldname."='$otsikkorivi[tilausvahvistus]',";
								}
								else {
									$query .= $fieldname."='$tv_row[tilausvahvistus]',";
								}
							}
							else {
								$query .= $fieldname."='',";
							}
						}
						elseif ($fieldname == 'hyvaksyja_nyt' and $otsikkorivi['jtkielto'] == "Y") {
							$query .= $fieldname."='$otsikkorivi[tunnus]',";
						}
						// n�m� kent�t tyhjennet��n jos jtrivit kootaan yhdelle otsikolle (siis normikeissi)
						elseif ($fieldname == 'sisviesti1' or
								$fieldname == 'tilausyhteyshenkilo' or
								$fieldname == 'asiakkaan_tilausnumero' or
								$fieldname == 'kohde' or
								$fieldname == 'viesti') {

							if ($toim == "ENNAKKO" or $otsikkorivi['jtkielto'] == "Y") {
								$query .= $fieldname."='".$otsikkorivi[$fieldname]."',";
							}
							else {
								$query .= $fieldname."='',";
							}
						}
						// kommentti speciaalia
						elseif ($fieldname == 'comments') {
							if ($toim == "ENNAKKO" or $otsikkorivi['jtkielto'] == "Y") {
								if ($toim == "ENNAKKO") {
									$query .= $fieldname."=trim(concat('".t("ENNAKKOTILAUS")."\n', '".$otsikkorivi[$fieldname]."')),";
								}
								else {
									$query .= $fieldname."=trim(concat('".t("J�LKITOIMITUS")."\n', '".$otsikkorivi[$fieldname]."')),";
								}
							}
							else {
								if ($toim == "ENNAKKO") {
									$query .= $fieldname."='".t("ENNAKKOTILAUS")."',";
								}
								else {
									$query .= $fieldname."='".t("J�LKITOIMITUS")."',";
								}
							}
						}
						//HUOM: Laitetaan clearing kentt��n jotain speciaalia
						elseif ($fieldname == 'clearing') {
							if ($toim == "ENNAKKO") {
								$query .= "clearing='ENNAKKOTILAUS',";
							}
							elseif ($otsikkorivi['tila'] == 'G') {
								$query .= "clearing='".$otsikkorivi[$fieldname]."',";
							}
							else {
								$query .= "clearing='JT-TILAUS',";
							}
						}
						elseif ($fieldname == 'eilahetetta') {
							$query .= "eilahetetta='".$isarivirow["suoraan_laskutukseen"]."',";
						}
						elseif ($fieldname == 'kohdistettu') {
							// riipastaan tarkoituksella tyhj��, hoidetaan homma 'rahtivapaa' haarassa
						}
						elseif ($fieldname == 'rahtivapaa') {
							if ($yhtiorow["jt_rahti"] == "B") {
								// Ennakot/J�lkitoimitukset ovat aina rahtivapaita
								// Rahtivapaat tilaukset l�hetet��n aina l�hett�j�n rahtisopimuksella
								$query .= "rahtivapaa = 'o',";
								$query .= "kohdistettu = 'K',";
							}
							elseif ($yhtiorow["jt_rahti"] == "C") {
								// Ennakoihin/J�lkitoimituksiin lis�t��n aina rahtikulu
								$query .= "rahtivapaa = '',";
								$query .= "kohdistettu = '{$otsikkorivi["kohdistettu"]}',";
							}
							elseif ($yhtiorow["jt_rahti"] == "D") {
								// Ennakot/J�lkitoimitukset ovat rahtivapaita, jos tilauksen arvo on yli rahtivapausrajan (tilaus-valmis.inc hoitaa rahtikulun kuntoon)
								$query .= "rahtivapaa = '',";
								$query .= "kohdistettu = '{$otsikkorivi["kohdistettu"]}',";
							}
							else {
								// Ennakko/J�lkitoimitus on rahtivapaa, jos alkuper�inen tilaus oli rahtivapaa
								$query .= "rahtivapaa = '".$otsikkorivi[$fieldname]."',";
								$query .= "kohdistettu = '{$otsikkorivi["kohdistettu"]}',";
							}
						}
						elseif ($fieldname=='maksuehto') {
							//katsotaan onko asiakkaan maksuehto muuttunut j�lkivaatimukseksi
							$maksuehto_query = "SELECT maksuehto.jv, maksuehto.tunnus
												FROM asiakas
												join maksuehto ON asiakas.yhtio = maksuehto.yhtio and asiakas.maksuehto = maksuehto.tunnus
												where asiakas.yhtio = '$yhtiorow[yhtio]'
												and asiakas.tunnus = '$otsikkorivi[liitostunnus]'";
							$maksuehto_result = pupe_query($maksuehto_query);
							$maksuehtorow = mysql_fetch_assoc($maksuehto_result);

							if ($maksuehtorow['jv'] != '') {
								$query .= "maksuehto='$maksuehtorow[tunnus]',";
							}
							else {
								$query .= $fieldname."='".$otsikkorivi[$fieldname]."',";
							}

						}
						// ja kaikki muut paitsi tunnus sellaisenaan
						elseif ($fieldname!='tunnus') {
							$query .= $fieldname."='".$otsikkorivi[$fieldname]."',";
						}
					}

					$query = substr($query,0,-1);
					$stresult = pupe_query($query);
					$id = mysql_insert_id();

					$query = "INSERT INTO laskun_lisatiedot SET ";

					for ($i = 0; $i < mysql_num_fields($lisatiedot_result); $i++) {

						$fieldname = mysql_field_name($lisatiedot_result, $i);

						if ($fieldname == 'laatija') {
							$query .= $fieldname."='$kukarow[kuka]',";
						}
						elseif ($fieldname == 'luontiaika') {
							$query .= $fieldname."=now(),";
						}
						elseif ($fieldname == 'otunnus') {
							$query .= $fieldname."='$id',";
						}
						elseif ($fieldname != 'tunnus') {
							$query .= $fieldname."='".$lisatiedot_row[$fieldname]."',";
						}
					}

					$query = substr($query, 0, -1);
					$lisatiedot_result = pupe_query($query);

					if ($debug==1) echo t("Perustin laskun")." $otsikkorivi[nimi] $id<br>";
				}

				///* T�ss� meill� on otsikko hallussa se on $id muuttujassa*///

				// T�ss� haarassa k�ytt�j� on sy�tt�nyt jonkun kappalem��r�n
				if ($kpl[$tunnukset] > 0 or $loput[$tunnukset] == 'KAIKKI' or $loput[$tunnukset] == 'VAKISIN') {

					$jt_vakisin = FALSE;

					if ($loput[$tunnukset] == 'KAIKKI' or $loput[$tunnukset] == 'VAKISIN') {
						if ($toim == "ENNAKKO") {
							$kpl[$tunnukset] = $isarivirow["varattu"];
						}
						else {
							$kpl[$tunnukset] = $isarivirow["jt"];
						}

						if ($loput[$tunnukset] == 'VAKISIN') {
							$jt_vakisin = TRUE;
						}

						$loput[$tunnukset] = 'POISTA';
					}

					if ($toim == "ENNAKKO") {
						$isa_kerroin	= $kpl[$tunnukset] / $isarivirow["varattu"];
						$isa_kpl		= $isarivirow["varattu"];
					}
					else {
						$isa_kerroin 	= $kpl[$tunnukset] / $isarivirow["jt"];
						$isa_kpl		= $isarivirow["jt"];
					}

					if ($suoratoimpaikka[$tunnukset] != '') {
						$paikka = $suoratoimpaikka;
					}
					else {
						$paikka = "";
					}

					if (($kpl[$tunnukset] <= $isa_kpl and $kpl[$tunnukset] > 0) or $jt_vakisin === TRUE) {

						$luodut_rivit = array();
						$luodut_kpl = array();

						foreach ($tunnusarray as $tunnus_nyt) {
							if ($toim == "ENNAKKO") {
								$query = "	SELECT tilausrivi.*, tuote.ei_saldoa, tilausrivin_lisatiedot.vanha_otunnus, tilausrivin_lisatiedot.tilausrivilinkki
											FROM tilausrivi
											LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
											JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
											WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
											and tilausrivi.tunnus = '$tunnus_nyt'
											and tilausrivi.varattu > 0
											and tilausrivi.tyyppi = 'E'";
							}
							else {
								$query = "	SELECT tilausrivi.*, tilausrivi.jt $lisavarattu jt, tuote.ei_saldoa, tilausrivin_lisatiedot.vanha_otunnus, tilausrivin_lisatiedot.tilausrivilinkki
											FROM tilausrivi
											LEFT JOIN tilausrivin_lisatiedot ON tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus
											JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno)
											WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
											and tilausrivi.tunnus = '$tunnus_nyt'
											and tilausrivi.jt $lisavarattu > 0
											and tilausrivi.kpl = 0
											and tilausrivi.var = 'J'
											and tilausrivi.tyyppi in ('L','G')";
							}
							$rivitres = pupe_query($query);
							$lisataanrow = mysql_fetch_assoc($rivitres);

							if ($toim == "ENNAKKO") {
								$lisataanrow_kpl = round($lisataanrow["varattu"] * $isa_kerroin,2);
							}
							else {
								$lisataanrow_kpl = round($lisataanrow["jt"] * $isa_kerroin,2);
							}

							if ($debug == 1) echo t("Yritet��n myyd� tuotetta").": $lisataanrow[tuoteno] $lisataanrow_kpl kappaletta.<br>";

							$query = "	SELECT *
										FROM tuote
										WHERE yhtio  = '$kukarow[yhtio]'
										and  tuoteno = '$lisataanrow[tuoteno]'";
							$aresult = pupe_query($query);
							$trow = mysql_fetch_assoc($aresult);

							$query = "	SELECT *
										FROM lasku
										WHERE tunnus = '$id'";
							$aresult = pupe_query($query);
							$laskurow = mysql_fetch_assoc($aresult);

							$talteen_kpl		= $kpl; // lis��rivi rikkoo t�n, otetaan talteen
							$kukarow["kesken"]	= $id;
							$tuoteno 		 	= $lisataanrow["tuoteno"];
							$netto 			 	= $lisataanrow["netto"];

							for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
								${'ale'.$alepostfix} = $lisataanrow["ale{$alepostfix}"];
							}

							$kpl				= $lisataanrow_kpl;
							$varasto 		 	= $varastosta;
							$korvaavakielto	 	= "Y";
							$perhekielto     	= "Y";
							$osatoimkielto		= "Y";

							if ($jt_vakisin === TRUE) {
								$var = "H";
							}
							else {
								$var = "";
							}

							$perheid = $lisataanrow["perheid"];
							$perheid2 = $lisataanrow["perheid2"];

							// Rivin kommentti
							$kommentti = "";

							if (trim($lisataanrow['kommentti']) != "") {
								// Alkuper�isen rivin kommentti
								$kommentti .= trim($lisataanrow['kommentti']);
							}

							if ($lisataanrow["vanha_otunnus"] > 0) {
								$alkupotunnus = $lisataanrow["vanha_otunnus"];
							}
							else {
								$alkupotunnus = $lisataanrow["otunnus"];
							}

							$query = "	SELECT tunnus, asiakkaan_tilausnumero, viesti, tilausyhteyshenkilo, sisviesti1, kohde
										FROM lasku
										WHERE tunnus = '$alkupotunnus'";
							$aresult = pupe_query($query);

							if (mysql_num_rows($aresult) > 0) {
								$orig_laskurow = mysql_fetch_assoc($aresult);

								// Joitain tietoja alkuper�iselt� tilaukselta, vain jos osatoimitus ei ole estetty ja jtrivit yhdistet��n samalle otsikolle ja kyseess� ei ole ennakkotilaus
								if ($otsikkorivi["osatoimitus"] == "" and $toim != "ENNAKKO" and $otsikkorivi['jtkielto'] != "Y") {

									if (strpos($kommentti, t("Alkuper�inen tilaus").": ".trim($orig_laskurow['tunnus'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Alkuper�inen tilaus").": ".trim($orig_laskurow['tunnus']);
									}

									if (trim($orig_laskurow['asiakkaan_tilausnumero']) != "" and strpos($kommentti, t("Asiakkaan tilaus").": ".trim($orig_laskurow['asiakkaan_tilausnumero'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Asiakkaan tilaus").": ".trim($orig_laskurow['asiakkaan_tilausnumero']);
									}

									if (trim($orig_laskurow['viesti']) != "" and strpos($kommentti, t("Tilausviite").": ".trim($orig_laskurow['viesti'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Tilausviite").": ".trim($orig_laskurow['viesti']);
									}

									if (trim($orig_laskurow['tilausyhteyshenkilo']) != "" and strpos($kommentti, t("Tilaaja").": ".trim($orig_laskurow['tilausyhteyshenkilo'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Tilaaja").": ".trim($orig_laskurow['tilausyhteyshenkilo']);
									}

									if (trim($orig_laskurow['sisviesti1']) != "" and strpos($kommentti, t("Kommentti").": ".trim($orig_laskurow['sisviesti1'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Kommentti").": ".trim($orig_laskurow['sisviesti1']);
									}

									if (trim($orig_laskurow['kohde']) != "" and strpos($kommentti, t("Kohde").": ".trim($orig_laskurow['kohde'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Kohde").": ".trim($orig_laskurow['kohde']);
									}

								}
								elseif ($otsikkorivi["osatoimitus"] == "" and $otsikkorivi['jtkielto'] == "Y") {
									if (strpos($kommentti, t("Alkuper�inen tilaus").": ".trim($orig_laskurow['tunnus'])) === FALSE) {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Alkuper�inen tilaus").": ".trim($orig_laskurow['tunnus']);
									}
								}
								elseif ($toim == "ENNAKKO") {
									if (strpos($kommentti, t("Ennakkotilaus").": ".trim($orig_laskurow['tunnus'])) === FALSE and $toim == "ENNAKKO") {
										if (trim($kommentti) != "") $kommentti .= " / ";
										// Tilausrivin systeemikommentti
										$kommentti .= t("Ennakkotilaus").": ".trim($orig_laskurow['tunnus']);
									}
								}
							}

							if ($automaattinen_poiminta != "" and $toim != "ENNAKKO" and $tilaus_on_jo == "KYLLA" and strpos($kommentti, t("Toimitettavissa oleva JT-rivi")."!") === FALSE) {
								if (trim($kommentti) != "") $kommentti .= " / ";
								// Tilausrivin systeemikommentti
								$kommentti .= t("Toimitettavissa oleva JT-rivi")."!";
							}

							$jtspec = "JTSPEC";

							// Tutkitaan onko t�m� myyty ulkomaan alvilla
							list(,,,$tsek_alehinta_alv,) = alehinta($laskurow, $trow, $kpl, '', '', '');

							if ($tsek_alehinta_alv > 0) {
								$trow["alv"] = $tsek_alehinta_alv;
							}

							// jos is�rivi oli k��nteinen alvi, niin laitetaan t�m�kin rivi k��nteiseksi
							if ($isarivirow["alv"] >= 600) {
								$alv = $trow["alv"] + 600;
							}

							if ($yhtiorow["alv_kasittely"] == "" and $trow["alv"] != $lisataanrow["alv"] and $lisataanrow["alv"] < 500) {
								$hinta = sprintf('%.2f',round($lisataanrow["hinta"] / (1+$lisataanrow['alv']/100) * (1+$trow["alv"]/100), $yhtiorow['hintapyoristys']));
							}
							else {
								$hinta = $lisataanrow["hinta"];
							}

							// jos k�yt�ss� on myyntihinnan poikkeava m��r�, kerrotaan hinta takaisin kuntoon.
							if ($trow["myyntihinta_maara"] != 0) {
								$hinta = $hinta * $trow["myyntihinta_maara"];
							}

							if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
								//K�ytet��n alkuper�isen JT-rivin otsikon valuuttakurssia
								$hinta = laskuval($hinta, $isarivirow["vienti_kurssi"]);
							}

							require("lisaarivi.inc");

							$kpl = $talteen_kpl; // lis��rivi rikko t�n, otetaan haltuun

							if ($debug==1) echo t("Lis�ttiin tuote").": $tuoteno kpl: $lisataanrow_kpl<br>";

							// Suoratoimitukset saliitaan jos ne ovat toivottuja
							if ($var == "S" and $suoratoimpaikka[$tunnukset] != '') {
								$lisatyt_rivit1 = $lisatyt_rivit2;
								unset($lisatyt_rivit2);
							}

							// Pidet��n muistissa kaikki lis�tyt rivit
							if (count($lisatyt_rivit1) > 0) {
								// Myyntihaara
								foreach ($lisatyt_rivit1 as $lisatty_tun) {
									$luodut_rivit[$lisatty_tun] = $lisatty_tun;

									//Pidet��n muistissa lis�tyt kappaleet
									if (isset($luodut_kpl[$tunnus_nyt])) $luodut_kpl[$tunnus_nyt] += $lisataanrow_kpl;
									else $luodut_kpl[$tunnus_nyt] = $lisataanrow_kpl;
								}
							}

							if (count($lisatyt_rivit2) > 0) {
								// Puute - JT - Ostohaara
								foreach ($lisatyt_rivit2 as $lisatty_tun) {
									$luodut_rivit[] = $lisatty_tun;
								}
							}

							//Jos saldo ei riitt�nyt
							if (count($lisatyt_rivit2) > 0) {
								foreach ($luodut_rivit as $poistetaan) {
									$query = "	DELETE FROM tilausrivi
												WHERE yhtio = '$kukarow[yhtio]'
												and tunnus = $poistetaan
												and kpl = 0";
									$tresult = pupe_query($query);

									$query = "UPDATE sarjanumeroseuranta set myyntirivitunnus=0 WHERE yhtio='$kukarow[yhtio]' and myyntirivitunnus=$poistetaan";
									$sarjares = pupe_query($query);

									if ($debug==1) echo t("Saldo ei riitt�nyt. Tehd��n rollback")."<br>";
								}

								unset($luodut_rivit);
								unset($luodut_kpl);
								break;
							}

							if (count($lisatyt_rivit1) > 0) {
								// Korjataan sarjanumero-oliot, eli siirret��n ne jtrivilt�/ennakkorivilt� tilausriville
								$query = "	SELECT myyntirivitunnus, count(*) kpl
											FROM sarjanumeroseuranta
											WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
											and sarjanumeroseuranta.tuoteno = '$lisataanrow[tuoteno]'
											and sarjanumeroseuranta.myyntirivitunnus  = '$lisataanrow[tunnus]'
											group by myyntirivitunnus";
								$sarjares = pupe_query($query);
								$sarjarow = mysql_fetch_assoc($sarjares);

								foreach ($lisatyt_rivit1 as $lisatty_tun) {
									$query = "	SELECT *
												FROM tilausrivi
												WHERE yhtio = '$kukarow[yhtio]'
												and otunnus = '$id'
												and tunnus  = '$lisatty_tun'";
									$lisres = pupe_query($query);
									$lisrow = mysql_fetch_assoc($lisres);

									if ($lisrow["varattu"] != 0) {
										$lisrow["varattu"] = (int) abs(round($lisrow["varattu"], 0));

										$query = "	UPDATE sarjanumeroseuranta
													SET myyntirivitunnus = '$lisrow[tunnus]'
													WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
													and sarjanumeroseuranta.tuoteno = '$lisataanrow[tuoteno]'
													and sarjanumeroseuranta.myyntirivitunnus  = '$lisataanrow[tunnus]'
													LIMIT $lisrow[varattu]";
										$cores = pupe_query($query);
									}

									//Kopioidaan tilausrivin lisatiedot
									$query = "	SELECT *
												FROM tilausrivin_lisatiedot
												WHERE tilausrivitunnus='$lisataanrow[tunnus]' and yhtio ='$kukarow[yhtio]'";
									$monistares2 = pupe_query($query);

									if (mysql_num_rows($monistares2) > 0) {
										$monistarow2 = mysql_fetch_assoc($monistares2);

										$kysely = "	UPDATE tilausrivin_lisatiedot SET ";

 										// Ei monisteta tunnusta
										for ($i=0; $i < mysql_num_fields($monistares2)-1; $i++) {

											$fieldname = mysql_field_name($monistares2, $i);

											switch ($fieldname) {
												case 'yhtio':
												case 'tilausrivitunnus':
												case 'tiliointirivitunnus':
												case 'tilausrivilinkki':
												case 'toimittajan_tunnus':
												case 'luontiaika':
												case 'laatija':
													$values .= "";
													break;
												case 'positio':
													if ($kukarow['extranet'] != '' and $tilaus_on_jo == "KYLLA") {
														$kysely .= "positio='JT',";
													}
													else {
														$kysely .= $fieldname."='".$monistarow2[$fieldname]."',";
													}
													break;
												default:
													$kysely .= $fieldname."='".$monistarow2[$fieldname]."',";
											}
										}

										$kysely  = substr($kysely, 0, -1);
										$kysely .= " WHERE yhtio='$kukarow[yhtio]' and tilausrivitunnus='$lisrow[tunnus]'";
										$insres2 = pupe_query($kysely);
									}

									// jos alkuper�inen rivi oli suoratoimitettu asiakkaalle, merkataan rivit ker�tyksi ja toimitetuksi
									if ($isarivirow["suoraan_laskutukseen"] != "") {

										//Tutkitaan l�ytyyk� ostorivi ja sen toimitettuaika
									   	$query = "	SELECT tilausrivin_lisatiedot.suoratoimitettuaika
									   				FROM tilausrivi
													LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
									   				WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
													and tilausrivi.tyyppi  = 'O'
									   				and tilausrivi.tunnus  = '$isarivirow[tilausrivilinkki]'
													and tilausrivin_lisatiedot.suoratoimitettuaika != '0000-00-00'";
										$suoratoimresult = pupe_query($query);

										if ($suoratoimrow = mysql_fetch_assoc($suoratoimresult)) {
											$toimitettuaika = " '$suoratoimrow[suoratoimitettuaika]' ";
										}
										else {
											$toimitettuaika = " now() ";
										}

										$toimquery = "	UPDATE tilausrivi
														SET keratty 	= '$kukarow[kuka]',
														kerattyaika 	= $toimitettuaika,
														toimitettu 		= '$kukarow[kuka]',
														toimitettuaika 	= $toimitettuaika,
														kommentti 		= trim(concat(kommentti, ' ".t("Suoratoimitus")."'))
														WHERE yhtio = '$kukarow[yhtio]'
														AND tunnus  = '$lisrow[tunnus]'";
										$toimupdres = pupe_query($toimquery);
									}
								}

								//Jos jotakin kopioitavan rivin sarjanumeroista ei saatu kopioitua niin irrotetaan se silt� rivilt�
								$query = "	UPDATE sarjanumeroseuranta
											SET myyntirivitunnus = 0
											WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
											and sarjanumeroseuranta.tuoteno = '$lisataanrow[tuoteno]'
											and sarjanumeroseuranta.myyntirivitunnus  = '$lisataanrow[tunnus]'";
								$cores = pupe_query($query);
							}
						}

						if (count($luodut_rivit) > 0) {
							// Katotaan mit� tehd��n lopuille
							// Poistetaan loput
							if ($loput[$tunnukset] == 'POISTA') {
								foreach ($tunnusarray as $tunnus_nyt) {
									if ($toim == "ENNAKKO") {
										$query = "	SELECT tilausrivi.varattu jt
													FROM tilausrivi
													WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
													and tilausrivi.tunnus  = '$tunnus_nyt'";
									}
									else {
										$query = "	SELECT tilausrivi.jt $lisavarattu jt
													FROM tilausrivi
													WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
													and tilausrivi.tunnus  = '$tunnus_nyt'";
									}
									$rivitres = pupe_query($query);

									$poista_update_lisa = "";

									if (mysql_num_rows($rivitres) == 1) {
										$row = mysql_fetch_assoc($rivitres);

										if ($row["jt"] > $luodut_kpl[$tunnus_nyt]) {

											$jaljella = $row["jt"] - $luodut_kpl[$tunnus_nyt];

											if ($toim == "ENNAKKO" or $yhtiorow["varaako_jt_saldoa"] != "") {
												$poista_update_lisa = ", varattu = '$jaljella', jt = '0' ";

											}
											else {
												$poista_update_lisa = ", jt = '$jaljella', varattu = '0' ";
											}
										}
										elseif ($row['jt'] == $luodut_kpl[$tunnus_nyt]) {
											$poista_update_lisa = ", varattu = '0', jt = '0' ";
										}
									}

									$query = "	UPDATE tilausrivi SET
												tyyppi = 'D'
												$poista_update_lisa
												WHERE yhtio = '$kukarow[yhtio]'
												and tunnus = '$tunnus_nyt'
												and kpl = 0";
									$tresult = pupe_query($query);

									if ($debug==1) echo t("Poistetaan loput rivilt�").": $tunnus_nyt<br>";
								}
							}
							// J�tet��n loput
							else {
								foreach ($tunnusarray as $tunnus_nyt) {
									if ($toim == "ENNAKKO") {
										$query = "	SELECT tilausrivi.varattu jt
													FROM tilausrivi
													WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
													and tilausrivi.tunnus  = '$tunnus_nyt'";
									}
									else {
										$query = "	SELECT tilausrivi.jt $lisavarattu jt
													FROM tilausrivi
													WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
													and tilausrivi.tunnus  = '$tunnus_nyt'";
									}
									$rivitres = pupe_query($query);

									if (mysql_num_rows($rivitres) == 1) {
										$row = mysql_fetch_assoc ($rivitres);

										if ($row["jt"] > $luodut_kpl[$tunnus_nyt]) {

											$jaljella = $row["jt"] - $luodut_kpl[$tunnus_nyt];

											if ($toim == "ENNAKKO" or $yhtiorow["varaako_jt_saldoa"] != "") {
												$query = "	UPDATE tilausrivi
															SET varattu = '$jaljella'
															WHERE yhtio = '$kukarow[yhtio]'
															and tunnus = '$tunnus_nyt'
															and kpl = 0";
											}
											else {
												$query = "	UPDATE tilausrivi
															SET jt = '$jaljella'
															WHERE yhtio = '$kukarow[yhtio]'
															and tunnus = '$tunnus_nyt'
															and kpl = 0";
											}
											$tresult = pupe_query($query);

											if ($debug==1) echo t("J�tet��n loput rivilt�").": $tunnus_nyt<br>";

										}
										else {
											$query = "	DELETE FROM tilausrivi
														WHERE yhtio = '$kukarow[yhtio]'
														and tunnus = '$tunnus_nyt'
														and kpl = 0";
											$tresult = pupe_query($query);

											if ($debug==1) echo t("J�tet��n loput, mutta j�tett�v�� ei ollut, joten dellataan rivi").": $tunnus_nyt<br>";
										}
									}
								}
							}

							//Korjataan perheid:t uusilla riveill�
							$query = "	SELECT perheid, min(tunnus) uusiperheid
										FROM tilausrivi
										WHERE yhtio = '$kukarow[yhtio]'
										and otunnus = '$id'
										and perheid != 0
										GROUP by perheid";
							$copresult = pupe_query($query);

							while ($coprivirow = mysql_fetch_assoc($copresult)) {
								$query = "	UPDATE tilausrivi
											SET perheid = '$coprivirow[uusiperheid]'
											WHERE yhtio = '$kukarow[yhtio]'
											and otunnus = '$id'
											and perheid = '$coprivirow[perheid]'";
								$cores = pupe_query($query);
							}

							//Korjataan perheid2:t uusilla riveill�
							$query = "	SELECT perheid2, min(tunnus) uusiperheid2
										FROM tilausrivi
										WHERE yhtio = '$kukarow[yhtio]'
										and otunnus = '$id'
										and perheid2 != 0
										GROUP by perheid2";
							$copresult = pupe_query($query);

							while ($coprivirow = mysql_fetch_assoc($copresult)) {
								$query = "	UPDATE tilausrivi
											SET perheid2 = '$coprivirow[uusiperheid2]'
											WHERE yhtio = '$kukarow[yhtio]'
											and otunnus = '$id'
											and perheid2 = '$coprivirow[perheid2]'";
								$cores = pupe_query($query);
							}
						}
					}
					else {
						echo "<br>".t("Sy�tit liian ison numeron! Ei voitu tehd� mit��n").".<br><br>";
					}
				}
			}
		}
	}
?>