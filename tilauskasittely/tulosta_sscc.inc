<?php

	$query  = "SELECT * FROM lasku WHERE tunnus = '{$otunnus}' AND yhtio = '{$kukarow['yhtio']}' AND tila = 'K'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$laskurow = mysql_fetch_assoc($result);
	}
	else {
		echo "<font class='error'>",t("VIRHE: Tilausta ei en�� l�ydy"),"!</font>";
		exit;
	}

	if (!isset($kappalemaara)) $kappalemaara = '';
	if (!isset($kirjoitin)) $kirjoitin = '';

	if ($tee == 'tulosta_sscc') {

		$kappalemaara = trim($kappalemaara);
		$kirjoitin = trim($kirjoitin);

		$query = "	SELECT komento
					FROM kirjoittimet 
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$kirjoitin}'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);
		$kirow = mysql_fetch_assoc($kires);
		$komento = $kirow['komento'];

		if (is_numeric($kappalemaara) and $kappalemaara > 0 and trim($komento) != '') {

			// emuloidaan transactioita mysql LOCK komennolla
			$query = "LOCK TABLES avainsana WRITE";
			$res   = mysql_query($query) or pupe_error($query);

			// koko korkeus 92mm
			// printin korkeus 73mm
			// leveys 105mm

			for ($i = 0; $i < $kappalemaara; $i++) {
				$sscc = tulosta_sscc($komento);
			}

			// poistetaan lukko
			$query = "UNLOCK TABLES";
			$res   = mysql_query($query) or pupe_error($query);

			$tee = $lopetus = '';

			echo "<font class='message'>",t("K�ytett�v� SSCC-koodi"),": $sscc</font><br /><br />";

			require('suuntalavat.inc');
			require('inc/footer.inc');
			exit;
		}
		else {
			$tee = '';
		}
	}

	if ($tee == '') {
		echo "<form action='' method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
		echo "<input type='hidden' name='toiminto' value='tulosta_sscc'>";
		echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
		echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
		echo "<input type='hidden' name='tee' value='tulosta_sscc' />";
		echo "<input type='hidden' name='lopetus' value='{$palvelin2}tilauskasittely/keikka.php////tee=//toimittajaid={$toimittajaid}//toiminto=suuntalavat//otunnus={$otunnus}//ytunnus={$laskurow['ytunnus']}//suuntalavan_tunnus='>";

		echo "<table>";
		echo "<tr><th>",t("Kpl"),"</th><td><select name='kappalemaara' />";
		echo "<option value='1'>1</option>";
		echo "<option value='2'>2</option>";
		echo "<option value='3'>3</option>";
		echo "<option value='4'>4</option>";
		echo "<option value='5'>5</option>";
		echo "</select></td></tr>";

		echo "<tr><th>",t("Valitse tulostin"),"</th><td><select name='kirjoitin'>";
		echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

		$query = "	SELECT * 
					FROM kirjoittimet 
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND komento != 'email'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		while ($kirow = mysql_fetch_assoc($kires)) {
			if ($kirow['tunnus'] == $kirjoitin) $select = ' selected';
			else $select = '';
			echo "<option value='{$kirow['tunnus']}'{$select}>{$kirow['kirjoitin']}</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><td class='back' colspan='2'><input type='submit' value='",t("Tulosta"),"' /></td></tr>";
		echo "</table>";

		echo "</form>";
	}
