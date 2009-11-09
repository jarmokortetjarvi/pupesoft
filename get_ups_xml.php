<?php

	if (empty($argv)) {
	    die('T�m�n scriptin voi ajaa ainoastaan komentorivilt�.');
	}

	require 'inc/salasanat.php';
	require 'inc/connect.inc';
	require 'inc/functions.inc';

	echo date("d.m.Y @ G:i:s").": UPS-rahtikirjojen paivitys\n";

	if ($ups_host != "" and $ups_user != "" and $ups_pass != "" and $ups_path != "") {

		unset($conn_id, $login, $changedir, $files_xxx, $files, $fileget, $xml);

		$ups_dellattuja = 0;
		$ups_lisattuja = 0;
		
		// avataan yhteys
		$conn_id = ftp_connect($ups_host) or die("Connection failed @ $ups_host!\n");

		// kokeillaan login
		if ($conn_id) {
			$login = @ftp_login($conn_id, $ups_user, $ups_pass);
		}
		
		// vaihdetaan dirikka
		if ($login) {
			$changedir = ftp_chdir($conn_id, $ups_path);
		}
		
		// haetaan filet active modella
		if ($changedir) {
			ftp_pasv($conn_id, false);
			$files_xxx = @ftp_nlist($conn_id, "*.xxx");
			$files = @ftp_nlist($conn_id, "*.Out");
		}

		// joe ep�onnistu, haetaaan filet passive modella
		if (!$files_xxx) {
			ftp_pasv($conn_id, true);
			$files_xxx = ftp_nlist($conn_id, "*.xxx");
			$files = ftp_nlist($conn_id, "*.Out");
		}
		
		// dellataan l�ydetyt *.xxx filet
		if ($files_xxx) {
			foreach ($files_xxx as $file) {											
				ftp_delete($conn_id, $file);
				$ups_dellattuja++;
			}
		}

		// k�yd��n l�pi kaikki *.out filet
		if ($files) {

			foreach ($files as $file) {
				
				$fileget = ftp_get($conn_id, '/tmp/ups_temp_file.xml', $file, FTP_BINARY);
				
				if ($fileget) {
					$xml = simplexml_load_file('/tmp/ups_temp_file.xml');
				}
				
				if ($xml) {				
					foreach ($xml->children() as $children) {

						$reference_number = '';
						$ups_tracking_number = '';
						$xml_yhtio = '';

						foreach ($children as $child) {
							if ($child->getName() == 'ShipmentInformation') {
								$reference_number = $child->Reference1;
							}
							elseif ($child->getName() == 'ProcessMessage') {
								$ups_tracking_number = $child->ShipmentRates->PackageRates->PackageRate->TrackingNumber;
							}
							elseif ($child->getName() == 'ShipFrom') {
								$xml_yhtio = $child->CustomerID;
							}
						}

						if ($reference_number != '' and $ups_tracking_number != '' and $xml_yhtio != '') {
							$query = "	SELECT yhtio
										FROM yhtio
										WHERE ytunnus = '$xml_yhtio'";
							$result = mysql_query($query) or die("Ei saatu yhtiota ytunnuksella $xml_yhtio\n".mysql_error()."\n\n");

							if (mysql_num_rows($result) == 1) {
								$row = mysql_fetch_assoc($result);

								$query = "	UPDATE rahtikirjat SET
											rahtikirjanro = concat(rahtikirjanro, ' ', 'UPS:$ups_tracking_number')
											WHERE yhtio = '$row[yhtio]'
											AND otsikkonro = $reference_number";
								$result = mysql_query($query) or die("Ei voitu paivittaa $reference_number $ups_tracking_number\n".mysql_error()."\n\n");

								$ups_lisattuja++;
							}
							else {
								echo "Ei loydetty yhtiota ytunnuksella $xml_yhtio!!!\n";
							}
						}
					}
					ftp_delete($conn_id, $file);
					$ups_dellattuja++;
				}			
			}

			echo date("d.m.Y @ G:i:s").": P�ivitettiin $ups_lisattuja rahtikirjanumeroa. Dellattiin $ups_dellattuja tiedostoa.\n";
		}

		if ($conn_id) {
			ftp_close($conn_id);
		}

	}
?>