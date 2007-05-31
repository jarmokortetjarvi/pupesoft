<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Kohdista viitesuoritus korkoihin")."</font><hr>";

if (isset($tilino)){

	// tutkaillaan tili�
	$query = "select * from tili where yhtio='$kukarow[yhtio]' and tilino='$tilino'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Kirjanpidon tili� ei l�ydy")."!</font> $tilino<br><br>";
		unset($viite);
		unset($tilino);
		unset($valitut);
	}
	else {
		$tilirow = mysql_fetch_array($result);
	}
}

if (isset($tapa) and $tapa == 'paalle') {
	if (isset($viite)) {
		// tutkaillaan suoritusta
		$query = "select suoritus.* from suoritus, yriti where suoritus.yhtio='$kukarow[yhtio]' and summa != 0 and kohdpvm = '0000-00-00' and viite like '$viite%' and suoritus.yhtio=yriti.yhtio and suoritus.tilino=yriti.tilino and yriti.factoring != ''";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sopivia suorituksia ei l�ydy")."!</font><br><br>";
			unset($viite);
			unset($tilino);
		}
		else {
			echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input name='tilino' type='hidden' value='$tilino'>";
			echo "<input name='tapa'   type='hidden' value='$tapa'>";
			echo "<table>
				<tr><th>".t("Viite")."</th><th>".t("Asiakas")."</th><th>".t("Summa")."</th><th>".t("Valitse")."</th></tr>";

			while ($suoritusrow=mysql_fetch_array($result)) {
				echo "<tr><td>$suoritusrow[viite]</td><td>$suoritusrow[nimi_maksaja]</td><td>$suoritusrow[summa]</td><td><input name='valitut[]' type='checkbox' value='$suoritusrow[tunnus]' CHECKED></td></tr>";
			}				
			echo "</table><br><input type='submit' value='".t("Kohdista")."'></form>";
		}

	}

	if (isset($tilino) and is_array($valitut)) {
		echo "Kohdistan!<br>";
		foreach ($valitut as $valittu) {
			$query = "select * from suoritus where yhtio='$kukarow[yhtio]' and tunnus='$valittu' and kohdpvm='0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Suoritus on kadonnut tai se on k�ytetty")."!</font><br><br>";
			}
			else {
				$suoritusrow=mysql_fetch_array($result);
				// p�ivitet��n suoritus
				$query = "update suoritus set kohdpvm = now(), summa = 0 where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[tunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				//echo "$query<br>";
				if (mysql_affected_rows() == 0) {
					echo "<font class='error'>".t("Suorituksen p�ivitys ep�onnistui")."! $tunnus</font><br>";	
				}

				// tehd��n kirjanpitomuutokset
				$query = "update tiliointi set tilino='$tilino', selite = '".t("Kohdistettiin korkoihin")."' where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[ltunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				//echo "$query<br>";
				if (mysql_affected_rows() == 0) {
					echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehd�! Korjaa kirjanpito k�sin")."!</font><br>";
				}
			}
		}
		unset($viite);
		unset($tapa);
	}
}
if (isset($tapa) and $tapa == 'pois') {
	if (isset($viite)) {
		$query = "select suoritus.*, tiliointi.summa from suoritus, yriti, tiliointi where suoritus.yhtio='$kukarow[yhtio]' and suoritus.summa = 0 and kohdpvm != '0000-00-00' and viite like '$viite%' and suoritus.yhtio=yriti.yhtio and suoritus.tilino=yriti.tilino and yriti.factoring != '' and tiliointi.yhtio=suoritus.yhtio and selite = '".t("Kohdistettiin korkoihin")."' and tiliointi.tunnus=suoritus.ltunnus";
		$result = mysql_query($query) or pupe_error($query);
		//echo $query."<br>";
		
		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sopivia korkovientej� ei l�ydy")."!</font><br><br>";
			unset($viite);
			unset($tilino);
		}
		else {
			echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input name='tilino' type='hidden' value='$yhtiorow[factoringsaamiset]'>";
			echo "<input name='tapa'   type='hidden' value='$tapa'>";
			echo "<table>
				<tr><th>".t("Viite")."</th><th>".t("Asiakas")."</th><th>".t("Summa")."</th><th>".t("Valitse")."</th></tr>";

			while ($suoritusrow=mysql_fetch_array($result)) {
				echo "<tr><td>$suoritusrow[viite]</td><td>$suoritusrow[nimi_maksaja]</td><td>$suoritusrow[summa]</td><td><input name='valitut[]' type='checkbox' value='$suoritusrow[tunnus]' CHECKED></td></tr>";
			}				
			echo "</table><br><input type='submit' value='".t("Peru korkoviennit")."'></form>";
		}
	}
	
	if (isset($tilino) and is_array($valitut)) {
		echo "Kohdistan!<br>";
		foreach ($valitut as $valittu) {
			$query = "select * from suoritus where yhtio='$kukarow[yhtio]' and tunnus='$valittu' and kohdpvm!='0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);
			//echo "$query<br>";
			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Suoritus on kadonnut tai se ei ole en�� k�ytetty")."!</font><br><br>";
			}
			else {
				$suoritusrow=mysql_fetch_array($result);
				// Etsit��n kirjanpitotapahtuma
				$query = "select summa from tiliointi where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[ltunnus]'";
				$result = mysql_query($query) or pupe_error($query);
				//echo "$query<br>";
				if (mysql_num_rows($result) == 0) {
					echo "<font class='error'>".t("Tili�inti on kadonnut")."!</font><br><br>";
				}
				else {
					$tiliointirow=mysql_fetch_array($result);
					$query = "select pankki_tili from factoring where yhtio='$kukarow[yhtio]' and pankki_tili='$suoritusrow[tilino]'";
					$result = mysql_query($query) or pupe_error($query);
					//echo "$query<br>";
					if (mysql_num_rows($result) == 0) {
						$tilino = $yhtiorow['myyntisaamiset'];
					}
					else {
						$tilino = $yhtiorow['factoringsaamiset'];
					}
					// p�ivitet��n suoritus
					$query = "update suoritus set kohdpvm = '0000-00-00', summa = -1 * $tiliointirow[summa] where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[tunnus]'";
					$result = mysql_query($query) or pupe_error($query);
					//echo "$query<br>";
					if (mysql_affected_rows() == 0) {
						echo "<font class='error'>".t("Suorituksen p�ivitys ep�onnistui")."! $tunnus</font><br>";	
					}
					// tehd��n kirjanpitomuutokset
					$query = "update tiliointi set tilino='$tilino', selite = '".t("Korjatttu suoritus")."' where yhtio='$kukarow[yhtio]' and tunnus='$suoritusrow[ltunnus]'";
					$result = mysql_query($query) or pupe_error($query);
					//echo "$query<br>";
					if (mysql_affected_rows() == 0) {
						echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehd�! Korjaa kirjanpito k�sin")."!</font><br>";
					}
				}
			}
		}
		unset($viite);
		unset($tapa);
	}
}

if (!isset($tapa)) {
		echo "<form action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<table>";
		echo "<tr><th>".t("Suorituksia siirret��n korkoihin")."</th>";
		echo "<td><input type='radio' name='tapa' value='paalle' checked></td></tr>";
		echo "<tr><th>".t("Suorituksia siirret��n koroista normaaleiksi suorituksiksi")."</th>";
		echo "<td><input type='radio' name='tapa' value='pois'></td></tr>";
		echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Valitse")."'></td></tr>";
		echo "</table>";
		echo "</form>";
}
else {
	if (!isset($viite)) { 
		if ($tapa == 'paalle') {
			echo "<form name='eikat' action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tapa' value='$tapa'><table>";
			echo "<tr><th>".t("Anna viitteen alku suorituuksista, jotka haluat k�sitelt�viksi")."</th>";
			echo "<td><input type='text' name='viite' value = '50009'></td></tr>";
			echo "<tr><th>".t("Mille tilille n�m� varat tili�id��n")."</th>";
			echo "<td><input type='text' name='tilino'></td></tr>";
			echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Hae suoritukset")."'></td></tr>";
			echo "</table>";
			echo "</form>";
		}
		else {
			echo "<form name='eikat' action='$PHP_SELF' method='post' autocomplete='off'>";
			echo "<input type='hidden' name='tapa' value='$tapa'><table>";
			echo "<tr><th>".t("Anna viitteen alku korkovienneist�, jotka haluat k�sitelt�viksi")."</th>";
			echo "<td><input type='text' name='viite'></td></tr>";
			echo "<tr><td class='back'><input name='subnappi' type='submit' value='".t("Hae korkoviennit")."'></td></tr>";
			echo "</table>";
			echo "</form>";
		}
	}
}

// kursorinohjausta
$formi = "eikat";
$kentta = "viite";

require ("../inc/footer.inc");

?>
