<?php

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("inc/parametrit.inc");

	if (isset($tee)) {
		if ($tee == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);	
			exit;
		}
	}
	else {
		$toim = strtoupper($toim);

		if ($toim == "" or $toim == "SUPER") {
			$otsikko = t("myyntitilausta");
		}
		elseif ($toim == "ENNAKKO") {
			$otsikko = t("ennakkotilausta");
		}
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
			$otsikko = t("ty�m��r�yst�");
		}
		elseif ($toim == "REKLAMAATIO") {
			$otsikko = t("reklamaatiota");
		}
		elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
			$otsikko = t("sis�ist� ty�m��r�yst�");
		}
		elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
			$otsikko = t("valmistusta");
		}
		elseif ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER") {
			$otsikko = t("varastosiirtoa");
		}
		elseif ($toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER" or $toim == "MYYNTITILITOIMITA") {
			$otsikko = t("myyntitili�");
		}
		elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
			$otsikko = t("tarjousta");
		}
		elseif ($toim == "LASKUTUSKIELTO") {
			$otsikko = t("laskutuskieltoa");
		}
		elseif ($toim == "EXTRANET") {
			$otsikko = t("extranet-tilausta");
		}
		elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
			$otsikko = t("osto-tilausta");
		}
		elseif ($toim == "YLLAPITO") {
			$otsikko = t("yll�pitosopimusta");
		}
		elseif ($toim == "PROJEKTI") {
			$otsikko = t("tilauksia");
		}
		elseif ($toim == "VALMISTUSMYYNTI" or $toim == "VALMISTUSMYYNTISUPER") {
			$otsikko = t("tilauksia ja valmistuksia");
		}
		else {
			$otsikko = t("myyntitilausta");
			$toim = "";
		}

		echo "<font class='head'>".t("Muokkaa")." $otsikko<hr></font>";

		// Tehd��n popup k�ytt�j�n lep��m�ss� olevista tilauksista
		if ($toim == "SIIRTOLISTA" or $toim == "SIIRTOLISTASUPER" or $toim == "MYYNTITILI" or $toim == "MYYNTITILISUPER") {
			$query = "	SELECT *
						FROM lasku use index (tila_index)
						WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'G'";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
			$query = "	SELECT *
						FROM lasku use index (tila_index)
						WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'S'";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='A' and alatila='' and tilaustyyppi='A'";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "REKLAMAATIO") {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='C' and alatila='' and tilaustyyppi='R'";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "TARJOUS" or $toim == "TARJOUSSUPER") {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='T' and alatila in ('','A') and tilaustyyppi='T'";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "OSTO") {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and alatila = ''";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "OSTOSUPER") {
			$query = "	SELECT *
						FROM lasku
						WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and tila='O' and alatila = 'A'";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "ENNAKKO") {
			$query = "	SELECT lasku.*
						FROM lasku use index (tila_index), tilausrivi use index (yhtio_otunnus)
						WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						and lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]')  and lasku.tila='E' and lasku.alatila in ('','A','J') and lasku.tilaustyyppi = 'E' and tilausrivi.tyyppi = 'E'
						GROUP BY lasku.tunnus";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
			$query = "	SELECT *
						FROM lasku use index (tila_index)
						WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]')  and alatila='' and tila = 'V'";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "" or $toim == "SUPER") {
			$query = "	SELECT *
						FROM lasku use index (tila_index)
						WHERE yhtio = '$kukarow[yhtio]' and (laatija='$kukarow[kuka]' or tunnus='$kukarow[kesken]') and alatila='' and tila in ('N','E')";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "LASKUTUSKIELTO") {
			$query = "	SELECT lasku.*
						FROM lasku use index (tila_index)
						JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus and lasku.chn = '999'
						WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]') and tila in ('N','L') and alatila != 'X'";
			$eresult = mysql_query($query) or pupe_error($query);
		}
		elseif ($toim == "YLLAPITO") {
			$query = "	SELECT lasku.*
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and (lasku.laatija='$kukarow[kuka]' or lasku.tunnus='$kukarow[kesken]') and tila = '0' and alatila not in ('V','D')";
			$eresult = mysql_query($query) or pupe_error($query);
		}


		if ($toim != "MYYNTITILITOIMITA" and $toim != "EXTRANET" and $toim != "VALMISTUSMYYNTI" and $toim != "VALMISTUSMYYNTISUPER") {
			if (isset($eresult) and  mysql_num_rows($eresult) > 0) {
				// tehd��n aktivoi nappi.. kaikki mit� n�ytet��n saa aktvoida, joten tarkkana queryn kanssa.
				if ($toim == "" or $toim == "SUPER" or $toim == "ENNAKKO" or $toim == "LASKUTUSKIELTO") {
					$aputoim1 = "RIVISYOTTO";
					$aputoim2 = "PIKATILAUS";

					$lisa1 = t("Rivisy�tt��n");
					$lisa2 = t("Pikatilaukseen");
				}
				elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER") {
					$aputoim1 = "VALMISTAASIAKKAALLE";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				elseif ($toim == "MYYNTITILISUPER") {
					$aputoim1 = "MYYNTITILI";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				elseif ($toim == "SIIRTOLISTASUPER") {
					$aputoim1 = "SIIRTOLISTA";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				elseif ($toim == "TARJOUSSUPER") {
					$aputoim1 = "TARJOUS";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				elseif ($toim == "TYOMAARAYSSUPER") {
					$aputoim1 = "TYOMAARAYS";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
					$aputoim1 = "";
					$lisa1 = t("Muokkaa");

					$aputoim2 = "";
					$lisa2 = "";
				}
				else {
					$aputoim1 = $toim;
					$aputoim2 = "";

					$lisa1 = t("Muokkaa");
					$lisa2 = "";
				}

				if ($toim == "OSTO" or $toim == "OSTOSUPER") {
					echo "<form method='post' action='tilauskasittely/tilaus_osto.php'>";
				}
				else {
					echo "<form method='post' action='tilauskasittely/tilaus_myynti.php'>";
				}

				echo "	<input type='hidden' name='toim' value='$aputoim1'>
						<input type='hidden' name='tee' value='AKTIVOI'>";

				echo "<table>
						<tr>
						<th>".t("Kesken olevat").":</th>
						<td><select name='tilausnumero'>";

				while ($row = mysql_fetch_array($eresult)) {
					$select="";
					//valitaan keskenoleva oletukseksi..
					if ($row['tunnus'] == $kukarow["kesken"]) {
						$select="SELECTED";
					}
					echo "<option value='$row[tunnus]' $select>$row[tunnus]: $row[nimi] ($row[luontiaika])</option>";
				}

				echo "</select></td>";

				if ($toim == "" or $toim == "SUPER" or $toim == "ENNAKKO" or $toim == "LASKUTUSKIELTO") {
					echo "<td class='back'><input type='submit' name='$aputoim2' value='$lisa2'></td>";
				}

				echo "<td class='back'><input type='submit' name='$aputoim1' value='$lisa1'></td>";
				echo "</tr></table></form>";
			}
			else {
				echo t("Sinulla ei ole aktiivisia eik� kesken olevia tilauksia").".<br>";
			}
		}


		// N�ytet��n muuten vaan sopivia tilauksia
		echo "<br><form action='$PHP_SELF' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<font class='head'>".t("Etsi")." $otsikko<hr></font>
				".t("Sy�t� tilausnumero, nimen tai laatijan osa").":
				<input type='text' name='etsi'>
				<input type='Submit' value = '".t("Etsi")."'>
				</form><br><br>";

		// pvm 30 pv taaksep�in
		$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

		$haku='';
		if (is_string($etsi))  $haku="and (lasku.nimi like '%$etsi%' or lasku.laatija like '%$etsi%')";
		if (is_numeric($etsi)) $haku="and (lasku.tunnus like '$etsi%' or lasku.ytunnus like '$etsi%')";

		$seuranta = "";
		$seurantalisa = "";

		if ($yhtiorow["tilauksen_seuranta"] !="") {
			$seuranta = " seuranta, ";
			$seurantalisa = "LEFT JOIN laskun_lisatiedot ON lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus";
		}

		if ($kukarow['resoluutio'] == 'I' and $toim != "SIIRTOLISTA" and $toim != "SIIRTOLISTASUPER" and $toim != "MYYNTITILI" and $toim != "MYYNTITILISUPER" and $toim != "EXTRANET") {
			$toimaikalisa = ' lasku.toimaika, ';
		}


		if ($limit == "") {
			$rajaus = "LIMIT 50";
		}
		else {
			$rajaus	= "";
		}


		// Etsit��n muutettavaa tilausta
		if ($toim == 'SUPER') {

			// t�ss� viel� vanha query ilman summia jos summien hakeminen on hidasta
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, ytunnus, lasku.luontiaika, if (kuka1.kuka!=kuka2.kuka, concat_ws(' / ', kuka1.nimi, kuka2.nimi), kuka1.nimi) laatija, $toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// uusi query jossa tilauksen arvot
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, ytunnus, lasku.luontiaika, if (kuka1.kuka!=kuka2.kuka, concat_ws(' / ', kuka1.nimi, kuka2.nimi), kuka1.nimi) laatija,
						round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
						round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
			 			$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio = lasku.yhtio and kuka1.kuka = lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio = lasku.yhtio and kuka2.tunnus = lasku.myyja)
						JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi != 'D')
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X'
						$haku
						GROUP BY lasku.tunnus
						ORDER BY lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L', 'N') and lasku.alatila != 'X'";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == 'ENNAKKO') {
			$query = "	SELECT lasku.tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, lasku.laatija, viesti tilausviite,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index), tilausrivi use index (yhtio_otunnus)
						WHERE lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
						and lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='E' and tilausrivi.tyyppi = 'E'
						$haku
						GROUP BY lasku.tunnus
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi = 'E')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'E'";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == "SIIRTOLISTA") {
			$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.alatila in ('','A','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";
			$miinus = 2;
		}
		elseif ($toim == "SIIRTOLISTASUPER") {
			$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.alatila in ('','A','B','C','D','J','T')
						$haku
						order by lasku.luontiaika desc
						$rajaus";
			$miinus = 2;
		}
		elseif ($toim == "MYYNTITILI") {
			$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','J')";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == "MYYNTITILISUPER") {
			$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila in ('','A','B','C','J')";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == "MYYNTITILITOIMITA") {
			$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='G' and tilaustyyppi = 'M' and alatila = 'V'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			 // haetaan tilausten arvo
			 $sumquery = "	SELECT
			 				round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
			 				round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
			 				count(distinct lasku.tunnus) kpl
			 				FROM lasku use index (tila_index)
			 				JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
			 				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='G' and lasku.tilaustyyppi = 'M' and lasku.alatila = 'V'";
			 $sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			 $sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == "JTTOIMITA") {
			$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='N' and lasku.alatila='U'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila='N' and lasku.alatila='U'";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == 'VALMISTUS') {
			$query = "	SELECT tunnus tilaus, nimi vastaanottaja, ytunnus, lasku.luontiaika, laatija, viesti tilausviite,$toimaikalisa alatila, tila, tilaustyyppi
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'V'
						and lasku.alatila in ('','A','B','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila = 'V'
							and lasku.alatila in ('','A','B','J')
							and lasku.tilaustyyppi != 'W'";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 3;
		}
		elseif ($toim == "VALMISTUSSUPER") {
			$query = "	SELECT tunnus tilaus, nimi vastaanottaja, ytunnus, lasku.luontiaika, laatija, viesti tilausviite,$toimaikalisa alatila, tila, tilaustyyppi
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'V'
						and lasku.alatila in ('','A','B','C','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and lasku.tila = 'V'
							and lasku.alatila in ('','A','B','C','J')
							and lasku.tilaustyyppi != 'W'";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 3;
		}
		elseif ($toim == "VALMISTUSMYYNTI") {
			$query = "	SELECT lasku.tunnus tilaus, $seuranta lasku.nimi asiakas, lasku.viesti, lasku.luontiaika, lasku.laatija,$toimaikalisa lasku.alatila, lasku.tila, kuka.extranet extra, tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
						$seurantalisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and ((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
						$haku
						HAVING extra = '' or extra is null
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and ((tila='V' and alatila in ('','A','B','J')) or (lasku.tila in ('L','N') and lasku.alatila in ('A','')))
							and tilaustyyppi != 'W'";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 4;
		}
		elseif ($toim == "VALMISTUSMYYNTISUPER") {
			$query = "	SELECT lasku.tunnus tilaus, $seuranta lasku.nimi asiakas, lasku.viesti, lasku.luontiaika, lasku.laatija,$toimaikalisa lasku.alatila, lasku.tila, kuka.extranet extra, tilaustyyppi
						FROM lasku use index (tila_index)
						LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and tila in ('L','N','V')
						and alatila not in ('X','V')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus) and tilausrivi.tyyppi IN ('L','W')
							WHERE lasku.yhtio = '$kukarow[yhtio]'
							and tila in ('L','N','V')
							and alatila not in ('X','V')
							and tilaustyyppi != 'W'";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 4;
		}
		elseif ($toim == "TYOMAARAYS" or $toim == "TYOMAARAYSSUPER") {
			$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE yhtio = '$kukarow[yhtio]' and tila in ('A','L','N') and tilaustyyppi='A' and alatila in ('','A','B','C','J')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

		   // haetaan tilausten arvo
		   $sumquery = "SELECT
		    			round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
		    			round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
		    			count(distinct lasku.tunnus) kpl
		    			FROM lasku use index (tila_index)
		    			JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
		    			WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('A','L','N') and lasku.tilaustyyppi='A' and lasku.alatila in ('','A','B','C','J')";
		    $sumresult = mysql_query($sumquery) or pupe_error($sumquery);
		    $sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == "REKLAMAATIO") {
			$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija,$toimaikalisa alatila, tila, tilaustyyppi
						FROM lasku use index (tila_index)
						WHERE yhtio = '$kukarow[yhtio]' and tila in ('L','N','C') and tilaustyyppi='R' and alatila in ('','A','B','C','J','D')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
			   				round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
					    	round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
					    	count(distinct lasku.tunnus) kpl
					    	FROM lasku use index (tila_index)
					    	JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
					    	WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('L','N','C') and lasku.tilaustyyppi='R' and lasku.alatila in ('','A','B','C','J','D')";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 3;
		}
		elseif ($toim == "SIIRTOTYOMAARAYS" or $toim == "SIIRTOTYOMAARAYSSUPER") {
			$query = "	SELECT tunnus tilaus, nimi varasto, ytunnus id, lasku.luontiaika, laatija, viesti tilausviite,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='S' and alatila in ('','A','B','J','C')
						$haku
						order by lasku.luontiaika desc
						$rajaus";
			$miinus = 2;
		}
		elseif ($toim == "TARJOUS") {
			$query = "	SELECT if(tunnusnippu>0,tunnusnippu,lasku.tunnus) tarjous, $seuranta nimi asiakas, ytunnus, lasku.luontiaika,
						if(date_add(lasku.luontiaika, interval $yhtiorow[tarjouksen_voimaika] day) >= now(), '<font color=\'#00FF00\'>Voimassa</font>', '<font color=\'#FF0000\'>Er��ntynyt</font>') voimassa,
						DATEDIFF(lasku.luontiaika, date_sub(now(), INTERVAL $yhtiorow[tarjouksen_voimaika] day)) pva,
						lasku.laatija,$toimaikalisa alatila, tila, lasku.tunnus tilaus, tunnusnippu
						FROM lasku use index (tila_index)
						$seurantalisa
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A')
						$haku
						ORDER BY lasku.tunnus desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila ='T' and lasku.tilaustyyppi='T' and lasku.alatila in ('','A')";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 4;
		}
		elseif ($toim == "TARJOUSSUPER") {
			$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila ='T' and tilaustyyppi='T' and alatila in ('','A','X')
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan kaikkien avoimien tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila ='T' and lasku.tilaustyyppi='T' and lasku.alatila in ('','A')";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == "EXTRANET") {
			$query = "	SELECT tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, laatija,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila = 'F'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila = 'N' and lasku.alatila = 'F'";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == "LASKUTUSKIELTO") {
			$query = "	SELECT lasku.tunnus tilaus, nimi asiakas, ytunnus, lasku.luontiaika, lasku.laatija,$toimaikalisa alatila, tila
						FROM lasku use index (tila_index)
						JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus and lasku.chn = '999'
						WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('N','L') and lasku.alatila != 'X'
						$haku
						order by lasku.luontiaika desc
						$rajaus";

		   // haetaan tilausten arvo
		   $sumquery = "	SELECT
		   				round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
		   				round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
		   				count(distinct lasku.tunnus) kpl
		   				FROM lasku use index (tila_index)
		   				JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
		   				WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('N','L') and lasku.alatila != 'X'";
		   $sumresult = mysql_query($sumquery) or pupe_error($sumquery);
		   $sumrow = mysql_fetch_array($sumresult);

			$miinus = 2;
		}
		elseif ($toim == 'OSTOSUPER') {
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija,$toimaikalisa lasku.alatila, lasku.tila
						FROM tilausrivi use index (yhtio_tyyppi_kerattyaika),
						lasku use index (primary)
						WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
						and tilausrivi.tyyppi = 'O'
						and tilausrivi.uusiotunnus = 0
						and tilausrivi.kerattyaika = '0000-00-00 00:00:00'
						and lasku.yhtio = tilausrivi.yhtio
						and lasku.tunnus = tilausrivi.otunnus
						and lasku.tila = 'O'
						and lasku.alatila != ''
						$haku
						GROUP by 1
						ORDER by lasku.luontiaika desc
						$rajaus";
			$miinus = 2;
		}
		elseif ($toim == 'OSTO') {
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija,$toimaikalisa lasku.alatila, lasku.tila
						FROM lasku use index (tila_index)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila='O' and alatila=''
						$haku
						ORDER by lasku.luontiaika desc
						$rajaus";
			$miinus = 2;
		}
		elseif ($toim == 'PROJEKTI') {
			$query = "	SELECT lasku.tunnus tilaus, $seuranta lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija,$toimaikalisa lasku.alatila, lasku.tila, tunnusnippu
						FROM lasku use index (tila_index)
						$seurantalisa
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila IN ('R','L','N') and alatila NOT IN ('X')
						$haku
						ORDER by lasku.luontiaika desc
						$rajaus";
			$miinus = 3;
		}
		elseif ($toim == 'YLLAPITO') {
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, if(kuka1.kuka != kuka2.kuka, concat_ws(' / ', kuka1.nimi, kuka2.nimi), kuka1.nimi) laatija, lasku.alatila, lasku.tila, tunnusnippu
						FROM lasku use index (tila_index)
						LEFT JOIN kuka as kuka1 ON (kuka1.yhtio=lasku.yhtio and kuka1.kuka=lasku.laatija)
						LEFT JOIN kuka as kuka2 ON (kuka2.yhtio=lasku.yhtio and kuka2.tunnus=lasku.myyja)
						WHERE lasku.yhtio = '$kukarow[yhtio]' and tila = '0' and alatila NOT IN ('D')
						$haku
						ORDER by lasku.luontiaika desc
						$rajaus";

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in ('0') and lasku.alatila != 'D'";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 3;
		}
		else {
			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija,
						$seuranta $toimaikalisa lasku.alatila, lasku.tila, kuka.extranet extra
						FROM lasku use index (tila_index)
						LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
						$seurantalisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ('L','N')
						and lasku.alatila in ('A','')
						$haku
						HAVING extra = '' or extra is null
						order by lasku.luontiaika desc
						$rajaus";

			/*
			Proof of concept --> T�ll� tavalla voidaan tutkia tilauskohtaisia summia

			$query = "	SELECT lasku.tunnus tilaus, lasku.nimi asiakas, lasku.ytunnus, lasku.luontiaika, lasku.laatija,
						round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
						round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
						$seuranta $toimaikalisa lasku.alatila, lasku.tila, kuka.extranet extra
						FROM lasku use index (tila_index)
						JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
						LEFT JOIN kuka ON lasku.yhtio=kuka.yhtio and lasku.laatija=kuka.kuka
						$seurantalisa
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila in ('L','N')
						and lasku.alatila in ('A','')
						$haku
						GROUP BY tilaus,asiakas,ytunnus,luontiaika,laatija,alatila,tila,extra";

			if ($seuranta != '') {
				$query .= ",seuranta ";
			}
			if ($toimaikalisa != '') {
				$query .= ",toimaika ";
			}

			$query .= "	HAVING extra = '' or extra is null
						order by lasku.luontiaika desc
						$rajaus";
			*/

			// haetaan tilausten arvo
			$sumquery = "	SELECT
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa,
							count(distinct lasku.tunnus) kpl
							FROM lasku use index (tila_index)
							JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi!='D')
							WHERE lasku.yhtio = '$kukarow[yhtio]' and lasku.tila in('L','N') and lasku.alatila in ('A','')";
			$sumresult = mysql_query($sumquery) or pupe_error($sumquery);
			$sumrow = mysql_fetch_array($sumresult);

			$miinus = 3;
		}
		$result = mysql_query($query) or pupe_error($query);


		if (mysql_num_rows($result) != 0) {
		
			if(@include('Spreadsheet/Excel/Writer.php')) {
		
				//keksit��n failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";
		
				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$worksheet = $workbook->addWorksheet('Sheet 1');
	
				$format_bold = $workbook->addFormat();
				$format_bold->setBold();
	
				$excelrivi = 0;
			}

			echo "<table border='0' cellpadding='2' cellspacing='1'>";

			echo "<tr>";

			for ($i=0; $i < mysql_num_fields($result)-$miinus; $i++) {
				echo "<th align='left'>".t(mysql_field_name($result,$i))."</th>";
		
				if(isset($workbook)) {
					$worksheet->write($excelrivi, $i, ucfirst(t(mysql_field_name($result,$i))), $format_bold);
				}
			}
			$excelrivi++;
			
			echo "<th align='left'>".t("tyyppi")."</th></tr>";

			while ($row = mysql_fetch_array($result)) {

				$piilotarivi = "";
				$pitaako_varmistaa = "";

				// jos kyseess� on "odottaa JT tuotteita rivi"
				if ($row["tila"] == "N" and $row["alatila"] == "T") {
					$query = "select tunnus from tilausrivi where yhtio='$kukarow[yhtio]' and tyyppi='L' and otunnus='$row[tilaus]'";
					$countres = mysql_query($query) or pupe_error($query);

					// ja sill� ei ole yht��n rivi�
					if (mysql_num_rows($countres) == 0) {
						$piilotarivi = "kylla";
					}
				}


				//	tarjousnipuista halutaan vain se viimeisin..
				if($row["tila"] == "T" and $row["tunnusnippu"] > 0) {
					$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and tila='T' and tunnusnippu='$row[tunnusnippu]' and tunnus > $row[tilaus]";
					$countres = mysql_query($query) or pupe_error($query);

					// ja sill� ei ole yht��n rivi�
					if (mysql_num_rows($countres) > 0) {
						$piilotarivi = "kylla";
					}
				}
				elseif($row["tunnusnippu"] > 0 and $row["tila"] != "R" and $toim == "PROJEKTI") {

					//	Jos meill� on tunnusnippu joka kuuluu projektiin se piilotetaan
					$query = "select tunnus from lasku where yhtio='$kukarow[yhtio]' and tila='R' and tunnusnippu='$row[tunnusnippu]'";
					$countres = mysql_query($query) or pupe_error($query);

					// ja sill� ei ole yht��n rivi�
					if (mysql_num_rows($countres) > 0) {
						$piilotarivi = "kylla";
					}
				}

				if ($piilotarivi == "") {

					// jos kyseess� on "odottaa JT tuotteita rivi ja kyseessa on toim=JTTOIMITA"
					if ($row["tila"] == "N" and $row["alatila"] == "U") {
						$query = "select tuoteno, jt from tilausrivi where yhtio='$kukarow[yhtio]' and tyyppi='L' and otunnus='$row[tilaus]'";
						$countres = mysql_query($query) or pupe_error($query);

						$jtok = 0;
						while($countrow = mysql_fetch_array($countres)) {
							list( , , $jtapu_myytavissa) = saldo_myytavissa($countrow["tuoteno"], "", 0, "");
							if($jtapu_myytavissa < $countrow["jt"]) {
								$jtok--;
							}
						}
					}


					echo "<tr class='aktiivi'>";

					for ($i=0; $i<mysql_num_fields($result)-$miinus; $i++) {
						if (mysql_field_name($result,$i) == 'luontiaika' or mysql_field_name($result,$i) == 'toimaika') {
							echo "<td valign='top'>".tv1dateconv($row[$i],"pitka")."</td>";
						}
						else {
							echo "<td valign='top'>$row[$i]</td>";
						}
					
						if(isset($workbook)) {
							if (mysql_field_type($result,$i) == 'real') {
								$worksheet->writeNumber($excelrivi, $i, sprintf("%.02f",$row[$i]));
							}
							else {						
								$worksheet->writeString($excelrivi, $i, $row[$i]);
							}
						}
					}
				

					if ($row["tila"] == "N" and $row["alatila"] == "U") {
						if ($jtok == 0) {
							echo "<td valign='top'><font color='#00FF00'>Voidaan toimittaa</font></td>";
						
							if(isset($workbook)) {
								$worksheet->writeString($excelrivi, $i, "Voidaan toimittaa");
								$i++;
							}
						}
						else {
							echo "<td valign='top'><font color='#FF0000'>Ei voida toimittaa</font></td>";
						
							if(isset($workbook)) {
								$worksheet->writeString($excelrivi, $i, "Ei voida toimittaa");
								$i++;
							}
						}
					}
					else {

						$laskutyyppi = $row["tila"];
						$alatila	 = $row["alatila"];

						//tehd��n selv�kielinen tila/alatila
						require "inc/laskutyyppi.inc";

						$tarkenne = " ";

						if ($row["tila"] == "V" and $row["tilaustyyppi"] == "V") {
							$tarkenne = " (".t("Asiakkaalle").") ";
						}
						elseif ($row["tila"] == "V" and  $row["tilaustyyppi"] == "W") {
							$tarkenne = " (".t("Varastoon").") ";
						}
						elseif(($row["tila"] == "N" or $row["tila"] == "L") and $row["tilaustyyppi"] == "R") {
							$tarkenne = " (".t("Reklamaatio").") ";
						}

						echo "<td valign='top'>".t("$laskutyyppi")."$tarkenne".t("$alatila")."</td>";
					
						if(isset($workbook)) {
							$worksheet->writeString($excelrivi, $i, t("$laskutyyppi")."$tarkenne".t("$alatila"));
							$i++;
						}
					}
				
					$excelrivi++;

					// tehd��n aktivoi nappi.. kaikki mit� n�ytet��n saa aktvoida, joten tarkkana queryn kanssa.
					if ($toim == "" or $toim == "SUPER" or $toim == "EXTRANET" or $toim == "ENNAKKO" or $toim == "JTTOIMITA" or $toim == "LASKUTUSKIELTO"or (($toim == "VALMISTUSMYYNTI" or $toim == "VALMISTUSMYYNTISUPER") and $row["tila"] != "V")) {
						$aputoim1 = "RIVISYOTTO";
						$aputoim2 = "PIKATILAUS";

						$lisa1 = t("Rivisy�tt��n");
						$lisa2 = t("Pikatilaukseen");
					}
					elseif ($toim == "VALMISTUS" or $toim == "VALMISTUSSUPER" or (($toim == "VALMISTUSMYYNTI" or $toim == "VALMISTUSMYYNTISUPER") and $row["tila"] == "V")) {
						$aputoim1 = "VALMISTAASIAKKAALLE";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "MYYNTITILISUPER" or $toim == "MYYNTITILITOIMITA") {
						$aputoim1 = "MYYNTITILI";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "SIIRTOLISTASUPER") {
						$aputoim1 = "SIIRTOLISTA";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "TARJOUSSUPER") {
						$aputoim1 = "TARJOUS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "TYOMAARAYSSUPER") {
						$aputoim1 = "TYOMAARAYS";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif ($toim == "OSTO" or $toim == "OSTOSUPER") {
						$aputoim1 = "";
						$lisa1 = t("Muokkaa");

						$aputoim2 = "";
						$lisa2 = "";
					}
					elseif($toim=="PROJEKTI" and $row["tila"] != "R") {
						$aputoim1 = "RIVISYOTTO";

						$lisa1 = t("Rivisy�tt��n");
					}
					else {
						$aputoim1 = $toim;
						$aputoim2 = "";

						$lisa1 = t("Muokkaa");
						$lisa2 = "";
					}

					// tehd��n alertteja
					if ($row["tila"] == "L" and $row["alatila"] == "A") {
						$pitaako_varmistaa = t("Ker�yslista on jo tulostettu! Oletko varma, ett� haluat viel� muokata tilausta?");
					}

					if ($row["tila"] == "G" and $row["alatila"] == "A") {
						$pitaako_varmistaa = t("Siirtolista on jo tulostettu! Oletko varma, ett� haluat viel� muokata siirtolistaa?");
					}

					// tehd��n alertti jos sellanen ollaan m��ritelty
					$javalisa = "";
					if ($pitaako_varmistaa != "") {
						echo "	<script language=javascript>
								function lahetys_verify() {
									msg = '$pitaako_varmistaa';
									return confirm(msg);
								}
								</script>";
						$javalisa = "onSubmit = 'return lahetys_verify()'";
					}

					if ($toim == "OSTO" or $toim == "OSTOSUPER") {
						echo "<form method='post' action='tilauskasittely/tilaus_osto.php' $javalisa>";
					}
					else {
						echo "<form method='post' action='tilauskasittely/tilaus_myynti.php' $javalisa>";
					}

					echo "	<input type='hidden' name='toim' value='$aputoim1'>
							<input type='hidden' name='tee' value='AKTIVOI'>
							<input type='hidden' name='tilausnumero' value='$row[tilaus]'>";

					if ($toim == "" or $toim == "SUPER" or $toim == "EXTRANET" or $toim == "ENNAKKO" or $toim == "JTTOIMITA" or $toim == "LASKUTUSKIELTO"or (($toim == "VALMISTUSMYYNTI" or $toim == "VALMISTUSMYYNTISUPER") and $row["tila"] != "V")) {
						echo "<td class='back'><input type='submit' name='$aputoim2' value='$lisa2'></td>";
					}

					echo "<td class='back'><input type='submit' name='$aputoim1' value='$lisa1'></td>";
					echo "</form>";
					echo "</tr>";
				}
			}

			echo "</table>";

			if (is_array($sumrow)) {
				echo "<br><table>";
				echo "<tr><th>".t("Arvo yhteens�")." ($sumrow[kpl] ".t("kpl")."): </th><td align='right'>$sumrow[arvo] $yhtiorow[valkoodi]</td></tr>";
				echo "<tr><th>".t("Summa yhteens�").": </th><td align='right'>$sumrow[summa] $yhtiorow[valkoodi]</td></tr>";
				echo "</table>";
			}

			if (mysql_num_rows($result) == 50) {
				// N�ytet��n muuten vaan sopivia tilauksia
				echo "<br><table>";

				echo "<tr><th>".t("Listauksessa n�kyy 50 ensimm�ist�")." $otsikko.</th>
					<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='etsi' value='$etsi'>
					<input type='hidden' name='limit' value='NO'>
					<td class='back'><input type='Submit' value = '".t("N�yt� kaikki")."'></td>
					</form></tr></table>";
			}
		
			if(isset($workbook)) {
		
				// We need to explicitly close the workbook
				$workbook->close();
		
				echo "<br><table>";
				echo "<tr><th>".t("Tallenna lista").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Tilauslista.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}
		}
		else {
			echo t("Ei tilauksia")."...";
		}

		require ("inc/footer.inc");
	}
?>
