<?php

require ("inc/parametrit.inc");
require ("inc/functions.inc");

//katsotaan millon www roottia on viimeks modifioitu.. otetaan siit� versionumero.
$polku=dirname($_SERVER['SCRIPT_FILENAME'])."/.";

if ($yhtiorow["logo"] != '') {

	$image = getimagesize($yhtiorow["logo"]);
	$ix    = $image[0];			// kuvan x
	$iy    = $image[1];			// kuvan y

	if ($ix > $iy) {
		$koko = "width='150'";
	}
	else {
		$koko = "height='70'";
	}

	$logo = $yhtiorow["logo"];
}
else {
	$logo = "http://www.pupesoft.com/pupesoft.gif";
	$koko = "height='70'";
}

echo "<a href='".$palvelin2."logout.php?toim=change'><img border='0' src='$logo' alt='logo' $koko style='padding:0px 3px 7px 3px;'></a><br>";
//echo "<font class='info'>pupesoft.com v.".date("d/m/y@H:i", filemtime($polku))."</font><br><br>";

echo "$yhtiorow[nimi]<br>";
echo "$kukarow[nimi]<br><br>";

// estet��n errorit tyhj�st� arrayst�
if (!isset($menu)) $menu = array();

// mit� sovelluksia k�ytt�j� saa k�ytt��
$query = "	SELECT distinct sovellus
			FROM oikeu
			WHERE yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'
			order by sovellus";
$result = mysql_query($query) or pupe_error($query);

// l�ytyi usea sovellus
if (mysql_num_rows($result) > 1) {

	// jos ollaan tulossa loginista, valitaan oletussovellus...
	if ($go != "") {
		$query = "select sovellus from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi='$go' order by sovellus, jarjestys limit 1";
		$gores = mysql_query($query) or pupe_error($query);
		$gorow = mysql_fetch_array($gores);
		$sovellus = $gorow["sovellus"];
	}

	echo "	<form action='$PHP_SELF' name='vaihdaSovellus' method='POST'>
			<select name='sovellus' onchange='submit()'>";

	$sovellukset = array();

	while ($orow = mysql_fetch_array($result)) {
		$sovellukset[$orow['sovellus']] = t($orow['sovellus']);
	}

	//sortataan array phpss� jotta se menee kielest� riippumatta oikeeseen j�rjestykseen
	//k�yet��n asort funktiota koska se ei riko mun itse antamia array-indexej�
	asort($sovellukset, SORT_STRING);

	foreach ($sovellukset as $key => $val) {
		$sel = '';
		if ($sovellus == $key) $sel = "SELECTED";

		echo "<option value='$key' $sel>$val</option>";

		// sovellus on tyhj� kun kirjaudutaan sis��n, ni otetaan eka..
		if ($sovellus == '') $sovellus = $key;
	}

	echo "</select></form>";
}
else
{
	// l�ytyi vaan yksi sovellus, otetaan se
	$orow = mysql_fetch_array($result);
	$sovellus = $orow['sovellus'];
}


//N�ytet��n aina exit-nappi
echo "<a class='menu' href='logout.php' target='main'>".t("Exit")."</a><br>";


// Mit� k�ytt�j� saa tehd�?
// Valitaan ensin vain yl�taso jarjestys2='0'

$query = "SELECT nimi, jarjestys
		FROM oikeu
		WHERE yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and sovellus='$sovellus' and jarjestys2='0'
		ORDER BY jarjestys";
$result = mysql_query($query) or pupe_error($query);

while ($orow = mysql_fetch_array($result))
{
	// tutkitaan onko meill� alamenuja
	$query = "SELECT nimi, nimitys, alanimi
			FROM oikeu
			WHERE yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and sovellus='$sovellus' and jarjestys='$orow[jarjestys]'
			ORDER BY jarjestys, jarjestys2";
	$xresult = mysql_query($query) or pupe_error($query);
	$mrow = mysql_fetch_array($xresult);

	// alamenuja l�ytyy, eli t�m� on menu
	if (mysql_num_rows($xresult) > 1)
	{
		// jos ykk�nen niin n�ytet��n avattu menu itemi
		if($menu[$mrow['nimitys']] == 1)
		{
			echo "- <a class='menu' href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=0'>".t("$mrow[nimitys]")."</a><br>";

			// tehd��n submenu itemit
			while ($mrow = mysql_fetch_array($xresult))
			{
				echo "&nbsp;&bull; <a class='menu' href='$mrow[nimi]";
				if ($mrow['alanimi'] != '') echo "?toim=$mrow[alanimi]";
				echo "' target='main'>".t("$mrow[nimitys]")."</a><br>";
			}
		}
		else
		{
			// muuten n�ytet��n suljettu menuotsikko
			echo "+ <a class='menu' href='$PHP_SELF?sovellus=$sovellus&menu[$mrow[nimitys]]=1'>".t("$mrow[nimitys]")."</a><br>";
		}
	}
	else
	{
		// normaali menuitem
		echo "<a class='menu' href='$mrow[nimi]";
		if ($mrow['alanimi'] != '') echo "?toim=$mrow[alanimi]";
		echo "' target='main'>".t("$mrow[nimitys]")."</a><br>";
	}

}

require("inc/footer.inc");

?>