<?php

if (@include_once("inc/functions.inc"));
elseif (@include_once("functions.inc"));
else exit;

// tarvitaan $ftphost $ftpuser $ftppass $ftppath $ftpfile
// palautetaan $palautus ja $syy

$dummy			= array();
$syy			= "";
$palautus		= 0;
$tulos_ulos_ftp	= "";

$filenimi = basename($ftpfile);

if ($ftphost=='' or $ftpuser=='' or $ftppass=='' or $ftppath=='' or $ftpfile=='') {
	$tulos_ulos_ftp .= "<font class='error'>".t("L�hetykseen tarvittavia tietoja puuttuu")."! (host, user, pass, path, file)</font><br>";
}
else {

	//l�hetet��n tiedosto
	$conn_id = ftp_connect($ftphost);

	// jos connectio ok, kokeillaan loginata
	if ($conn_id) {
		$login_result = ftp_login($conn_id, $ftpuser, $ftppass);
	}

	// jos viimeinen merkki pathiss� ei ole kauttaviiva lis�t��n kauttaviiva...
	if (substr($ftppath, -1) != "/") {
		$ftppath .= "/";
	}

	$cmd = "/usr/bin/ncftpput -u $ftpuser -p $ftppass $ftphost $ftppath $ftpfile";

	// jos login ok kokeillaan uploadata
	if ($login_result) {

		ftp_pasv($conn_id, false);
		$upload = @ftp_put($conn_id, $ftppath.$filenimi, realpath($ftpfile), FTP_ASCII);

		// kokeillaan passiivista siirtoa jos aktiivi feilaa
		if ($upload === FALSE) {
			ftp_pasv($conn_id, true);
			$upload = ftp_put($conn_id, $ftppath.$filenimi, realpath($ftpfile), FTP_ASCII);
		}

		if ($upload === FALSE) {
			exec($cmd, $skit, $upload_ncftp);

			if ($upload_ncftp == 0) $upload = TRUE;
		}

		if ($upload === TRUE and $renameftpfile != "") {
			$renameftpfile = basename($renameftpfile);
			$rename = ftp_rename($conn_id, $ftppath.$filenimi, $ftppath.$renameftpfile);
		}
	}

	if ($conn_id) {
		ftp_close($conn_id);
	}

	// mik� feilas?
	if ($conn_id === FALSE) {
		$palautus = 1;
	}
	if ($login_result === FALSE) {
		$palautus = 2;
	}
	if ($upload === FALSE) {
		$palautus = 3;
	}
	if ($rename === FALSE) {
		$palautus = 4;
	}

	$to_email = $yhtiorow['alert_email'];

	// jos siirto ep�onnistuu
	if ($palautus<>0) {

		// ncftpput:in exit valuet
		switch ($palautus) {
			case  1:
				$syy = "Could not connect to remote host. ($ftphost)";
				break;
			case  2:
				$syy = "Could not login to remote host ($conn_id, $ftpuser, $ftppass)";
				break;
			case  3:
				$syy = "Transfer failed ($conn_id, $ftppath, ".realpath($ftpfile).")";
				break;
			case  4:
				$syy = "Rename failed ($conn_id, $ftppath, $ftppath.$filenimi -> $ftppath.$renameftpfile)";
				break;
			default:
				$syy = t("Tuntematon errorkoodi")." ($palautus)!!";
		}

		$rivi  = "$PHP_SELF\n";
		$rivi .= "\n";
		$rivi .= "".t("Tiedoston")." '$ftpfile' ".t("l�hetys ep�onnistui")."!\n";
		$rivi .= "\n";
		$rivi .= "$cmd\n";
		$rivi .= "\n";
		$rivi .= "$syy\n";

		$boob = mail("$to_email", mb_encode_mimeheader(t("Tiedostonsiirto ep�onnistui")."!", "ISO-8859-1", "Q"), $rivi, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");

		if ($boob===FALSE) {
			$tulos_ulos_ftp .= "<font class='error'>\n";
			$tulos_ulos_ftp .= "".t("Tiedostonsiirto ep�onnistui! Ja meilin l�hett�minen")." $to_email ".t("ep�onnistui my�s!")."\n";
			$tulos_ulos_ftp .= "</font><br>\n";

			$tulos_ulos_ftp .= "<br><pre>$rivi</pre><br>\n";
		}
		else {
			$tulos_ulos_ftp .= "<font class='error'>FAILED! (".t("l�hetettiin s�hk�posti")." $to_email)</font><br>\n";
		}
	}
	elseif ($palautus_pyynto != "") {
		$rivi  = "$PHP_SELF\n";
		$rivi .= "\n";
		$rivi .= "".t("Tiedoston")." '$ftpfile' ".t("l�hetys onnistui")."!\n";
		$rivi .= "\n";
		$rivi .= "$ftpuser @ $ftphost : $ftppath\n";
		$rivi .= "\n";
		$rivi .= "\n";

		$silari = mail("$to_email", mb_encode_mimeheader(t("Tiedostonsiirto onnistui")."!", "ISO-8859-1", "Q"), $rivi, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
	}
}

//echotaan rudulle jos kyseess� ei ole batch-ajo
if ($tulos_ulos == "") {
	echo $tulos_ulos_ftp;
}
elseif ($tulos_ulos == "edi") {
	$edi_ulos .= strip_tags($tulos_ulos_ftp);
}

?>