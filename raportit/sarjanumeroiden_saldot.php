<?php

	require("../inc/parametrit.inc");

	echo "<font class='head'>".t("Sarjanumeroiden saldokorjaus")."</font><hr>";

	echo "<form name='haku' action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='toiminto' value='TULOSTA'>";
	echo "<input type='submit' name='$subnimi' value='Korjaa!'>";
	echo "</form>";

	if ($toiminto == 'TULOSTA') {

		// haetaan tuotteet
		list($saldot, $lisavarusteet) = hae_tuotteet();

		// korjataan saldot
		// nollataan eka tuotteiden kaikki saldot
		foreach($saldot as $tuote => $kpl) {
			list($tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("#!#", $tuote);
			$query = "	UPDATE tuotepaikat SET
						saldo			= '0',
						saldoaika		= now(),
						muuttaja		= '$kukarow[kuka]',
						muutospvm		= now()
						WHERE tuoteno	= '$tuoteno'
						and yhtio		= '$kukarow[yhtio]'";
			$paikres = mysql_query($query) or pupe_error($query);
		}
		
		// p�ivitet��n saldot
		foreach($saldot as $tuote => $kpl) {

			list($tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("#!#", $tuote);

			// katotaan l�ytyyk� paikka mik� oli sarjanumerolla
			$query = "	SELECT *
						FROM tuotepaikat
						WHERE tuoteno	= '$tuoteno'
						and yhtio		= '$kukarow[yhtio]'
						and hyllyalue	= '$hyllyalue'
						and hyllynro	= '$hyllynro'
						and hyllyvali	= '$hyllyvali'
						and hyllytaso	= '$hyllytaso'";
			$alkuresult = mysql_query($query) or pupe_error($query);
			$alkurow = mysql_fetch_array($alkuresult);
			
			// katotaan onko paikka OK
			$tunnus = kuuluukovarastoon($hyllyalue, $hyllynro);

			// jos paikka on OK
			if ($tunnus != 0) {

				// tuotteella ei ollut perustettu t�t� paikkaa 
				if (mysql_num_rows($alkuresult) == 0) {
					// katotaaan eka onko joku paikka jo oletus
					$query = "	SELECT *
								FROM tuotepaikat
								WHERE tuoteno	= '$tuoteno'
								and yhtio		= '$kukarow[yhtio]'
								and oletus		!= ''";
					$paikres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($paikres) == 0) {
						$oletus = 'X';
					}
					else {
						$oletus = '';
					}

					$query = "	INSERT into tuotepaikat SET
								tuoteno		= '$tuoteno',
								yhtio		= '$kukarow[yhtio]',
								hyllyalue	= '$hyllyalue',
								hyllynro	= '$hyllynro',
								hyllyvali	= '$hyllyvali',
								hyllytaso	= '$hyllytaso',
								saldo		= '$kpl',
								saldoaika	= now(),
								laatija		= '$kukarow[kuka]',
								luontiaika	= now(),
								muuttaja	= '$kukarow[kuka]',
								muutospvm	= now(),
								oletus		= '$oletus'";
					$paikres = mysql_query($query) or pupe_error($query);
				}
				elseif (mysql_num_rows($alkuresult) == 1) {
					$query = "	UPDATE tuotepaikat SET
								saldo			= '$kpl',
								saldoaika		= now(),
								muuttaja		= '$kukarow[kuka]',
								muutospvm		= now()
								WHERE tuoteno	= '$tuoteno'
								and yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '$hyllyalue'
								and hyllynro	= '$hyllynro'
								and hyllyvali	= '$hyllyvali'
								and hyllytaso	= '$hyllytaso'";
					$alkuresult = mysql_query($query) or pupe_error($query);
				}
				else {
					echo "Tuotteella on useampi SAMA tuotepaikka!?!?! unpossible.";
				}
				
				echo "<br>Tuote $tuoteno saldo muutettu $kpl paikalla $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso";
			}
			
		}

		// haetaan tuotteet
		list($saldot, $lisavarusteet) = hae_tuotteet();

		// korjataan saldo_varatut
		// nollataan eka kaikkien tuotteiden saldo_varattu
		$query = "	UPDATE tuotepaikat SET
					saldo_varattu	= '0',
					saldoaika		= now(),
					muuttaja		= '$kukarow[kuka]',
					muutospvm		= now()
					WHERE yhtio		= '$kukarow[yhtio]'";
		$paikres = mysql_query($query) or pupe_error($query);
			
		foreach($lisavarusteet as $tuote => $kpl) {

			list($tuoteno, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode("#!#", $tuote);

			$query = "	SELECT saldo, saldo_varattu
						FROM tuotepaikat
						WHERE tuoteno	= '$tuoteno'
						and yhtio		= '$kukarow[yhtio]'
						and hyllyalue	= '$hyllyalue'
						and hyllynro	= '$hyllynro'
						and hyllyvali	= '$hyllyvali'
						and hyllytaso	= '$hyllytaso'";
			$alkuresult = mysql_query($query) or pupe_error($query);
			$alkurow = mysql_fetch_array($alkuresult);

			// katotaan onko paikka OK
			$tunnus = kuuluukovarastoon($hyllyalue, $hyllynro);
			$toiminto = "";

			// jos paikka on OK
			if ($tunnus != 0) {

				// tuotteella ei ollut perustettu t�t� paikkaa 
				if (mysql_num_rows($alkuresult) == 0) {

					// katotaaan eka onko joku paikka jo oletus
					$query = "	SELECT *
								FROM tuotepaikat
								WHERE tuoteno	= '$tuoteno'
								and yhtio		= '$kukarow[yhtio]'
								and oletus		!= ''";
					$paikres = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($paikres) == 0) {
						$oletus = 'X';
					}
					else {
						$oletus = '';
					}

					$query = "	INSERT into tuotepaikat SET
								tuoteno		= '$tuoteno',
								yhtio		= '$kukarow[yhtio]',
								hyllyalue	= '$hyllyalue',
								hyllynro	= '$hyllynro',
								hyllyvali	= '$hyllyvali',
								hyllytaso	= '$hyllytaso',
								saldo_varattu = '$kpl',
								saldoaika	= now(),
								laatija		= '$kukarow[kuka]',
								luontiaika	= now(),
								muuttaja	= '$kukarow[kuka]',
								muutospvm	= now(),
								oletus		= '$oletus'";
					$paikres = mysql_query($query) or pupe_error($query);
				}
				elseif (mysql_num_rows($alkuresult) == 1) {
					$query = "	UPDATE tuotepaikat SET
								saldo_varattu	= '$kpl',
								saldoaika		= now(),
								muuttaja		= '$kukarow[kuka]',
								muutospvm		= now()
								WHERE tuoteno	= '$tuoteno'
								and yhtio		= '$kukarow[yhtio]'
								and hyllyalue	= '$hyllyalue'
								and hyllynro	= '$hyllynro'
								and hyllyvali	= '$hyllyvali'
								and hyllytaso	= '$hyllytaso'";
					$alkuresult = mysql_query($query) or pupe_error($query);
				}
				else {
					echo "Tuotteella on useampi SAMA tuotepaikka!?!?! wtf?";
				}
				
				echo "<br>Tuote $tuoteno saldo_varattu muutettu $kpl paikalla $hyllyalue-$hyllynro-$hyllyvali-$hyllytaso";
			}
		}

	}

	require ("../inc/footer.inc");
	
	function hae_tuotteet() {
		
		global $kukarow;

		$lisavarusteet = array();
		$saldot = array();
		
		// N�ytet��n kaikki vapaana/myym�tt� olevat sarjanumerot
		$query	= "	SELECT sarjanumeroseuranta.*,
					if(tilausrivi_osto.nimitys!='', tilausrivi_osto.nimitys, tuote.nimitys) nimitys,
					tuote.myyntihinta 									tuotemyyntihinta,
					lasku_osto.tunnus									osto_tunnus,
					lasku_osto.nimi										osto_nimi,
					lasku_myynti.tunnus									myynti_tunnus,
					lasku_myynti.nimi									myynti_nimi,
					lasku_myynti.tila									myynti_tila,
					(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl)		ostohinta,
					tilausrivi_osto.perheid2							osto_perheid2,
					tilausrivi_osto.kpl									osto_kpl,
					tilausrivi_osto.tilkpl								osto_tilkpl,
					(tilausrivi_myynti.rivihinta/tilausrivi_myynti.kpl)	myyntihinta,
					varastopaikat.nimitys								varastonimi,
					sarjanumeroseuranta.lisatieto						lisatieto,
					tilausrivi_myynti.laskutettuaika					myynti_laskutettuaika,
					tilausrivi_osto.laskutettuaika						osto_laskutettuaika,
					concat_ws(' ', sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso) tuotepaikka,
					sarjanumeroseuranta.hyllyalue, sarjanumeroseuranta.hyllynro, sarjanumeroseuranta.hyllyvali, sarjanumeroseuranta.hyllytaso
					FROM sarjanumeroseuranta
					LEFT JOIN tuote use index (tuoteno_index) ON sarjanumeroseuranta.yhtio = tuote.yhtio and sarjanumeroseuranta.tuoteno = tuote.tuoteno
					LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus = sarjanumeroseuranta.myyntirivitunnus
					LEFT JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON tilausrivi_osto.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus = sarjanumeroseuranta.ostorivitunnus
					LEFT JOIN lasku lasku_myynti use index (PRIMARY) ON lasku_myynti.yhtio = sarjanumeroseuranta.yhtio and lasku_myynti.tunnus = tilausrivi_myynti.otunnus
					LEFT JOIN lasku lasku_osto use index (PRIMARY) ON lasku_osto.yhtio = sarjanumeroseuranta.yhtio and lasku_osto.tunnus = tilausrivi_osto.otunnus
					LEFT JOIN varastopaikat ON sarjanumeroseuranta.yhtio = varastopaikat.yhtio
					AND concat(rpad(upper(varastopaikat.alkuhyllyalue)  ,5,'0'),lpad(upper(varastopaikat.alkuhyllynro)  ,5,'0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					AND concat(rpad(upper(varastopaikat.loppuhyllyalue) ,5,'0'),lpad(upper(varastopaikat.loppuhyllynro) ,5,'0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue) ,5,'0'),lpad(upper(sarjanumeroseuranta.hyllynro) ,5,'0'))
					WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
					AND sarjanumeroseuranta.myyntirivitunnus != -1
					AND (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
					AND tilausrivi_osto.laskutettuaika != '0000-00-00'
					ORDER BY sarjanumeroseuranta.kaytetty, sarjanumeroseuranta.tuoteno, sarjanumeroseuranta.myyntirivitunnus";
		$sarjares = mysql_query($query) or pupe_error($query);

		while ($sarjarow = mysql_fetch_array($sarjares)) {

			// lasketaan lis�varusteet
			if ($sarjarow["osto_perheid2"] > 0) {
				$query = "	SELECT
							tilausrivi.tuoteno,
							tilausrivi.nimitys,
							if(tilausrivi.kpl!=0, round(tilausrivi.kpl/$sarjarow[osto_kpl],2), round(tilausrivi.tilkpl/$sarjarow[osto_tilkpl],2)) kpl
							FROM tilausrivi use index (yhtio_perheid2)
							WHERE tilausrivi.yhtio 		= '$sarjarow[yhtio]'
							and tilausrivi.tyyppi 	   != 'D'
							and tilausrivi.perheid2 	= '$sarjarow[osto_perheid2]'
							and tilausrivi.tunnus	   != tilausrivi.perheid2
							order by tilausrivi.tunnus";
				$tilrivires = mysql_query($query) or pupe_error($query);

				while ($tilrivirow2 = mysql_fetch_array($tilrivires)) {
					$key = $tilrivirow2["tuoteno"]."#!#".$sarjarow["hyllyalue"]."#!#".$sarjarow["hyllynro"]."#!#".$sarjarow["hyllyvali"]."#!#".$sarjarow["hyllytaso"];
					$lisavarusteet[$key] += $tilrivirow2["kpl"];
				}
			}

			// normituotteet
			$key = $sarjarow["tuoteno"]."#!#".$sarjarow["hyllyalue"]."#!#".$sarjarow["hyllynro"]."#!#".$sarjarow["hyllyvali"]."#!#".$sarjarow["hyllytaso"];
			$saldot[$key] += 1;
		}

		ksort($lisavarusteet);
		ksort($saldot);

		return array($saldot, $lisavarusteet);
	}

?>