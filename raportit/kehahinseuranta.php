<?php
	///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
	$useslave = 1;

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Keskihankintahintaseuranta")."</font><hr>";

	if ($tee != '') {

		$lisaa = "";

		if ($osasto != '') {
			$lisaa .= " and tuote.osasto = '$osasto' ";
		}
		if ($tuoryh != '') {
			$lisaa .= " and tuote.try = '$tuoryh' ";
		}

		$query = "	SELECT tapahtuma.*, tuote.nimitys
					FROM tapahtuma, tuote
					WHERE
					tapahtuma.yhtio='$kukarow[yhtio]'
					and tapahtuma.laji='tulo'
					and tapahtuma.laadittu >= '$vva-$kka-$ppa'
					and tapahtuma.laadittu <= '$vvl-$kkl-$ppl'
					and tuote.yhtio=tapahtuma.yhtio
					and tuote.tuoteno=tapahtuma.tuoteno
					$lisaa
					ORDER BY tuoteno, laadittu";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><th>".t("Tuoteno")."</th>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Uusikeha")."</th>";
		echo "<th>".t("Edkeha")."</th>";
		echo "<th>".t("Eropros")."</th>";
		echo "<th>".t("Uusikehapvm")."</th>";
		echo "<th>".t("Edkehapvm")."</th></tr>";

		$rivit1 = array();
		$rivit2 = array();

		while ($lrow = mysql_fetch_array($result)) {
			//haetaan edellinen myyntitapahtuma
			$query = "	SELECT *
						FROM tapahtuma
						WHERE yhtio='$kukarow[yhtio]'
						and tuoteno='$lrow[tuoteno]'
						and laji='laskutus'
						and tunnus < $lrow[tunnus]
						ORDER BY tunnus desc
						LIMIT 1";
			$eresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($eresult) > 0) {
				$erow = mysql_fetch_array($eresult);

				if($erow["hinta"] != 0) {
					$eropros = 100 * ($lrow["hinta"] - $erow["hinta"]) / $erow["hinta"];
				}
				else {
					$eropros = 100;
				}

				if (abs($eropros) <= $pros1 and abs($eropros) >= $pros2) {
					$rivit1[] = abs($eropros);
					$rivit2[] = "<tr><td><a href='../tuote.php?tee=Z&tuoteno=$lrow[tuoteno]'>$lrow[tuoteno]</a></td><td>$lrow[nimitys]</td><td>$lrow[hinta]</td><td>$erow[hinta]</td><td>".sprintf('%.1f',$eropros)."</td><td>".substr($lrow["laadittu"],0,10)."</td><td>".substr($erow["laadittu"],0,10)."</td></tr>";
				}
			}
		}


		array_multisort($rivit1, SORT_DESC, $rivit2);

		foreach($rivit2 as $rivi) {
			echo $rivi;
		}

		echo "</table><br><br>";
	}


	//K�ytt�liittym�

	echo "<table><form name='piiri' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='tee' value='kaikki'>";

	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");


	echo "<tr><th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";

	echo "<tr><th>".t("Muutosprosentin yl�raja").":</th>
			<td colspan='3'><input type='text' name='pros1' value='$pros1' size='15'></td></tr>";


	echo "<tr><th>".t("Muutosprosentin alaraja").":</th>
			<td colspan='3'><input type='text' name='pros2' value='$pros2' size='15'></td></tr>";

	echo "<tr><th>".t("Osasto")."</th><td colspan='3'>";

	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='osasto'>";
	echo "<option value=''>".t("N�yt� kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($osasto == $srow["selite"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
	}
	echo "</select>";


	echo "</td></tr>
			<tr><th>".t("Tuoteryhm�")."</th><td colspan='3'>";

	//Tehd��n osasto & tuoteryhm� pop-upit
	$query = "	SELECT distinct selite, selitetark
				FROM avainsana
				WHERE yhtio='$kukarow[yhtio]' and laji='TRY'
				ORDER BY selite+0";
	$sresult = mysql_query($query) or pupe_error($query);

	echo "<select name='tuoryh'>";
	echo "<option value=''>".t("N�yt� kaikki")."</option>";

	while ($srow = mysql_fetch_array($sresult)) {
		$sel = '';
		if ($tuoryh == $srow["selite"]) {
			$sel = "selected";
		}
		echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
	}
	echo "</select>";

	echo "	</table>";

	echo "<br><input type='submit' value='".t("Aja raportti")."'>";
	echo "</form>";

	// kursorinohjausta
	$formi  = "piiri";
	$kentta = "piiri";

	require ("../inc/footer.inc");

?>