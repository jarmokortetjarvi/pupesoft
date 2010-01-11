#!/usr/bin/php
<?php

	// t�ss� scp komennon host ja host dir muuttujat, muuta n�m� sopiviksi muuhun ei tarvitse koskea
	$scp_host = "joni@d90.arwidson.fi";
	$scp_dir  = "/backup/arwidson-backup";

	if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

	echo date("d.m.Y @ G:i:s")." - Backup svn.\n";

	$filename = "svn-backup-".date("Y-m-d").".bz2";

	// siirryt��n svn-dirriin
	chdir("/var/svn");

	// pakataan failit
	system("/bin/tar -cf $filename --use-compress-prog=pbzip2 *");

	echo date("d.m.Y @ G:i:s")." - Bzip2 done.\n";

	// kopsataan faili
	$scpma = "scp $filename $scp_host:$scp_dir";
	system($scpma);

	echo date("d.m.Y @ G:i:s")." - Transfer done.\n";

	// dellataan pois
	system("rm -f $filename");

	echo date("d.m.Y @ G:i:s")." - All done.\n";

?>