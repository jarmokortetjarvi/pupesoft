<?php

// otetaan sis��n $hetu
// palautetaan $hetuok

// Henkil�tunnus on muotoa PPKKVVSNNNT

$hetuok = 0; // kaikki ok

if (strlen($hetu) == 11) {

	// muutetaan kaikki kirjamet kapitaaleiksi
	$hetu = strtoupper($hetu);

	// konkatenoidaan pvm ja yksil�numero tarkistuslaskua varten
	$hetutarkista1 = substr($hetu, 0, 6).substr($hetu, 7, 3);

	// otetaan tarkistusmerkki talteen
	$hetutarkista2 = substr($hetu, 10, 1);

	// otetaan v�limerkki tarkistusta varten
	$hetutarkista3 = substr($hetu, 6, 1);

	// otetaan tarkistettavasta osasta mod 31
	$jaannos = $hetutarkista1 % 31;

	// tehd��n jakoj��nn�ksest� konversio kirjaimeen
	if ($jaannos <  10) $tarkiste = $jaannos;
	if ($jaannos == 10) $tarkiste = "A";
	if ($jaannos == 11) $tarkiste = "B";
	if ($jaannos == 12) $tarkiste = "C";
	if ($jaannos == 13) $tarkiste = "D";
	if ($jaannos == 14) $tarkiste = "E";
	if ($jaannos == 15) $tarkiste = "F";
	if ($jaannos == 16) $tarkiste = "H";
	if ($jaannos == 17) $tarkiste = "J";
	if ($jaannos == 18) $tarkiste = "K";
	if ($jaannos == 19) $tarkiste = "L";
	if ($jaannos == 20) $tarkiste = "M";
	if ($jaannos == 21) $tarkiste = "N";
	if ($jaannos == 22) $tarkiste = "P";
	if ($jaannos == 23) $tarkiste = "R";
	if ($jaannos == 24) $tarkiste = "S";
	if ($jaannos == 25) $tarkiste = "T";
	if ($jaannos == 26) $tarkiste = "U";
	if ($jaannos == 27) $tarkiste = "V";
	if ($jaannos == 28) $tarkiste = "W";
	if ($jaannos == 29) $tarkiste = "X";
	if ($jaannos == 30) $tarkiste = "Y";

	// katotaan p��stiink� samaan tarkisteeseen
	if ($tarkiste != $hetutarkista2) {
		$hetuok = 1; // ei ole ok
	}

	// katotaan, ett� v�limerkki on oikein
	if ($hetutarkista3 != "-" and $hetutarkista3 != '+' and $hetutarkista3 != 'A') {
		$hetuok = 1; // ei ole ok
	}

}
else {
	$hetuok = 1; // ei ole ok
}

?>
