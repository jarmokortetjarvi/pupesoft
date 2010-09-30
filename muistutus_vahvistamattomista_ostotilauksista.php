<?php

	if (isset($argv[1]) and trim($argv[1]) != '') {

		if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

		// otetaan tietokanta connect
		require ("inc/connect.inc");
		require ("inc/functions.inc");

		// hmm.. jännää
		$kukarow['yhtio'] = $argv[1];

		$query    = "select * from yhtio where yhtio='$kukarow[yhtio]'";
		$yhtiores = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($yhtiores) == 1) {
			$yhtiorow = mysql_fetch_array($yhtiores);

			// haetaan yhtiön parametrit
			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$yhtiorow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);
				// lisätään kaikki yhtiorow arrayseen, niin ollaan taaksepäinyhteensopivia
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}

		}
		else {
			die ("Yhtiö $kukarow[yhtio] ei löydy!");
		}

		$query = "	SELECT lasku.laatija, kuka.eposti, tilausrivi.otunnus, lasku.nimi, lasku.ytunnus, count(*) kpl
					FROM tilausrivi
					JOIN lasku ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and lasku.tila = 'O' and lasku.alatila != '')
					JOIN kuka ON (kuka.yhtio = tilausrivi.yhtio and kuka.kuka = lasku.laatija)
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					AND tilausrivi.toimitettu = ''
					AND tilausrivi.tyyppi = 'O'
					AND tilausrivi.kpl = 0
					and tilausrivi.varattu != 0
					AND tilausrivi.jaksotettu = 0
					AND lasku.lahetepvm < SUBDATE(CURDATE(), INTERVAL 5 DAY)
					AND kuka.eposti != ''
					GROUP BY tilausrivi.otunnus
					ORDER BY lasku.laatija";
		$result = mysql_query($query) or pupe_error($query);

		while ($trow = mysql_fetch_array($result)) {

			if ($trow['eposti'] != $veposti) {
				if ($veposti != '') {
					$meili = t("Sinulla on vahvistamatta seuraavien ostotilauksien rivit:").":\n\n" . $meili;
					$tulos = mail($veposti, t("Muistutus vahvistamattomista ostotilausriveistä")."", $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
					$maara++;
				}
				$meili = '';
				$veposti = $trow['eposti'];
			}


			$meili .= "Ostotilaus: " . $trow['otunnus'] . "\n";
			$meili .= "Toimittaja: " . $trow['nimi'] . "\n";
			$meili .= "Vahvistamattomia rivejä: " . $trow['kpl'] . "\n\n";

		}

		if ($meili != '') {
			$meili = t("Sinulla on vahvistamatta seuraavien ostotilauksien rivit:").":\n\n" . $meili;
			$tulos = mail($veposti, t("Muistutus vahvistamattomista ostotilausriveistä")."", $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
			$maara++;
		}

	}

?>