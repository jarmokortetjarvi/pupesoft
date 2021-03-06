<?php

/*
	Tiedostohallintalinkki
*/

//	Tsekataan ett� pakolliset muuttujat on, muuten skipataan automatic

//	Tsekataan onko meill� t�m� k�yt�ss� vai ei..
if(!function_exists("svnInit")) {
	function svnInit () {
		
		if(defined("svnStatus")) {
			return svnStatus;
		}
		
		//	Oko n�it� ei ole joten skipataan aina..
		if(!defined("svnMethod") or !defined("svnUrl") or !defined("svnPath") or !defined("svnLanguage")) {
			$status = false;
		}
		else {			
			$status = true;
		}

		define("svnStatus", $status);
		
		return $status;
	}
}

if(!function_exists("svnAction")) {
	function svnAction ($action, $src, $dst="", $comments="") {
		global $kukarow;
		
		if(svnStatus) {
			
			//	Tarkastetaan, ett� tiedot on muodollisesti oikein..
			$check = svnCheckCommit($action, $src, $dst, $comments);
			if($check === true) {
								
				if($comments != "") {
					$comments = "-m \"".t($comments, svnLanguage)."\n\n- $kukarow[nimi]\"";
				}
				
				//	Koska joissain asennuksissa PHP:ll� ei ole mit��n j�rjellist� localea pakotetaan se aina mukaan constanilla console_LC_CTYPE
				
				$cmd = "LC_CTYPE=".console_LC_CTYPE." svn $action $comments $src $dst";
				
				if(strpos(console_LC_CTYPE, "UTF-8") !== false) {
					$cmd = utf8_encode($cmd);
				}

				exec($cmd, $output, $retval);
				
				if(svnDebug>0) {
					echo "cmd: $cmd<br>retval: $retval<br>output: ".implode("<br>", $output)."<br>";
				}

				if($retval == 0) {
					
					if(strpos(console_LC_CTYPE, "UTF-8") !== false) {
						foreach($output as &$o) {
							$o = utf8_decode($o);
						}
					}
					
					return $output;
				}
				else {
					return false;
				}			
			}
			else {
				if(svnDebug>0) {
					echo "<br><font class='error'>Virhe tarkastuksessa:</font><br>action: $action<br>src: $src<br>dst: $dst<br>comments: $comments<br>$check<br><br>";
				}
				return false;
			}
		} 
	}		
}

if(!function_exists("makeSvnTarget")) {
	function makeSvnTarget (&$target) {
		
		if(svnStatus===false) return false;
		
		if($target == "") {
			return false;			
		}
		else {
			
			//	jos on paikallinen file tarkistetaan ett� sellanen oikeesti on
			if(substr($target, 0, 6) == "local:") {
				$target = substr($target, 6);
				if(!file_exists($target)) {
					return false;
				}

				$target = str_replace(" ", "\ ", $target);
			}
			else {
				
				if($target == "root") {
					$target = "";
				}
				
				$target = svnMethod."://".svnUrl."/".svnPath."/".str_replace("%2F", "/", rawurlencode(utf8_encode($target)));
			}
		}
	}
}

if(!function_exists("svnCheckCommit")) {
	function svnCheckCommit ($action, &$src, &$dst="", &$comments="") {
		
		if(svnStatus===false) return false;
		
		$action = trim($action);
		$noDstAction = array("delete", "list", "list -R");
		$nocommentsAction = array("list", "list -R");
		$allowedActions = array("move", "copy", "import", "delete", "list" , "list -R");
		
		if($action == "delete" and $src == "") {
			return "juurikansiota et voi poistaa";
		}
		
		if(in_array($action, $allowedActions)) {
			
			if(in_array($action, $nocommentsAction)) {
				$comments="";
			}
			
			if(in_array($action, $noDstAction)) {
				$dst="";
			}
			
			if($comments != "" or in_array($action, $nocommentsAction)) {
				
				if(makeSvnTarget($src) !== false) {
					if(makeSvnTarget($dst) !== false or in_array($action, $noDstAction)) {

						return true;
					}

					return "dst virheellinen $dst";
				}
				return "src virheellinen $src";
			}
		}
		else {
			return "svn toiminto $action ei kelpaa";
		}
	}		

}

if(!function_exists("svnListDirectory")) {
	function svnListDirectory ($dir, $params="") {
		
		if(svnStatus===false) return false;
		
		$list = svnAction("list $params", $dir);
		if(count($list) > 0) {

			foreach($list as &$p) {
				$p = "$dir$p";				
			}
			
			return $list;
		}
		
		return false;
	}
}

if(!function_exists("svnFileLink")) {
	function svnFileLink ($file) {
		
		if(svnStatus===false) return false;
		
		return svnPreviewMethod."://".svnPreviewUrl."/$file";
	}		
}

if(!function_exists("svnFindActiveProject")) {
	function svnFindActiveProject ($tunnus) {
		
		if(svnStatus===false) return false;
		
		$list = svnAction("list", svnProjectsActive);
		
		foreach($list as $p) {			
			if(substr($p, 0, strpos($p, " ")) == $tunnus) {
				return svnProjectsActive."/$p";
			}
		}
	}		
}
if(!function_exists("svnFindActiveQuotation")) {
	function svnFindActiveQuotation ($tunnus) {

		if(svnStatus===false) return false;

		$list = svnAction("list", svnQuotationsActive);
		foreach($list as $p) {			
			if(substr($p, 0, strpos($p, " ")) == $tunnus) {
				return svnQuotationsActive."/$p";
			}
		}
	}		
}

if(!function_exists("svnFindCompleteProject")) {
	function svnFindCompleteProject ($tunnus) {
		
		if(svnStatus===false) return false;
		
		$list = svnAction("list", svnProjectsComplete);
		
		foreach($list as $p) {			
			if(substr($p, 0, strpos($p, " ")) == $tunnus) {
				return svnProjectsComplete."/$p";
			}
		}
	}		
}
if(!function_exists("svnFindRejectedQuotation")) {
	function svnFindRejectedQuotation ($tunnus) {
		
		if(svnStatus===false) return false;
		
		$list = svnAction("list", svnQuotationsRejected);
		
		foreach($list as $p) {			
			if(substr($p, 0, strpos($p, " ")) == $tunnus) {
				return svnQuotationsRejected."/$p";
			}
		}
	}		
}

if(!function_exists("svnListProjectDirectory")) {

	if(svnStatus===false) return false;

	function svnListProjectDirectory ($tunnus, $params="") {
		return svnListDirectory(svnProjectDirectory($tunnus), $params);
	}
}
if(!function_exists("svnListQuotationDirectory")) {
	function svnListQuotationDirectory ($tunnus, $params="") {
		
		if(svnStatus===false) return false;
		
		return svnListDirectory(svnQuotationDirectory($tunnus), $params);
	}
}

if(!function_exists("svnProjectDirectory")) {
	function svnProjectDirectory ($tunnus) {
		
		if(svnStatus===false) return false;
		
		//	Koitetaan eka aktiivisista
		$path = svnFindActiveProject($tunnus);
		
		if($path == "") {
			$path = svnFindCompleteProject($tunnus);			
		}

		if($path != "") {
			return $path;
		}

		return false;
	}
}

if(!function_exists("svnQuotationDirectory")) {
	function svnQuotationDirectory ($tunnus) {
		
		if(svnStatus===false) return false;
		
		//	Koitetaan eka aktiivisista
		$path = svnFindActiveQuotation($tunnus);
		
		if($path == "") {
			$path = svnFindRejectedQuotation($tunnus);			
		}

		if($path != "") {
			return $path;
		}

		return false;
	}
}

if(!function_exists("svnOpenNew")) {
	function svnOpenNew ($tunnus, $type) {
		global $kukarow;
		
		if(svnStatus===false) return false;
		
		if($type == "PROJEKTI") {
			$path 			= svnProjectsActive;
			$templatePath 	= svnDocumentSamplesDirProjects;
		}
		elseif($type == "TARJOUS") {
			$path 			= svnQuotationsActive;
			$templatePath 	= svnDocumentSamplesDirQuotations;			
		}
		else {
			return "Tyyppi puuttuu";
		}
		
		//	Haetaan vaadittava tieto
		$query = "	SELECT lasku.nimi nimi, asiakkaan_kohde.kohde kohde, laskun_lisatiedot.seuranta seuranta
					FROM lasku
					JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
					LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=laskun_lisatiedot.yhtio and asiakkaan_kohde.tunnus= laskun_lisatiedot.asiakkaan_kohde
					WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnus='$tunnus' and lasku.tunnusnippu = lasku.tunnus";
		$kohderes = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($kohderes) > 0) {
			$kohderow = mysql_fetch_array($kohderes);
			$pDir = trim($path."/{$tunnus} {$kohderow["seuranta"]} {$kohderow["nimi"]} {$kohderow["kohde"]}");
			$sDir = $templatePath."/{$kohderow["seuranta"]}";
			
			//	Tsekataan onko seuranta specifi pohja
			if(svnListDirectory($sDir) === false) {
				$sDir = $templatePath."/default";
			}
			
			if(svnAction("copy", $sDir, $pDir, t(ucfirst(strtolower($type))." avattu")) !== false) {
				//	Uudelleennimet��nt tiedostot projektille
				$list = svnAction("list -R", $pDir);
				foreach($list as $file) {
					if(strpos(basename($file), "XXXXXX") !== false) {
						$file = $pDir."/".$file;
						svnAction("move", $file, str_replace("XXXXXX", $tunnus, $file), "Nimet��n tiedosto uudestaan");
					}
				}
				
				return true;
			}
			else {
				return t("Projektin avauksessa tapahtui virhe");
			}
		}
		else {
			return t("Projekti ei kelpaa");
		}
	}
}

if(!function_exists("svnClose")) {
	function svnClose ($tunnus, $type) {
		global $kukarow;
		
		if($type == "PROJEKTI") {
			$svnKansio 	= svnFindActiveProject($tunnus);
			$closeMsg 	= t("Projekti suljettu", $kieli);
			$targetDir 	= svnProjectsComplete; 
		}
		else {
			$svnKansio 	= svnFindActiveQuotation($tunnus);
			$closeMsg 	= t("Tarjous hyl�tty", $kieli);
			$targetDir 	= svnQuotationsRejected;
		}
		

		if($svnKansio != "") {
			if(strpos(svnProjectsComplete, "customerFolder") !== FALSE) {
				

				$query = "	SELECT liitostunnus
							FROM lasku
							WHERE yhtio='$kukarow[yhtio]' and tunnus = '$tunnus'";
				$res = mysql_query($query) or pupe_error($query);
				if(mysql_num_rows($res) == 1) {
					$row = mysql_fetch_array($res);
					$destDir = svnFindCustomerDir($row["liitostunnus"]);
					
					$targetDir = str_replace("customerFolder" , $destDir, $targetDir);
					
					if(svnListDirectory($targetDir) === false) {
						$tempDir = "/tmp/svnClose-".md5(uniqid());
						mkdir($tempDir);
						$tempDirCreate = $tempDir;
						foreach(explode("/", str_replace($destDir, "", $targetDir)) as $dir) {
							if($dir != "") {
								$tempDirCreate = $tempDirCreate."/$dir";
								mkdir($tempDirCreate);
							}
						}
						
						svnAction("import",	"local:$tempDir", $destDir, t("Lis�ttiin puuttuva kansio", $kieli));
						
						system("rm -rf $tempDir");
						
					}
					
					svnAction("move", $svnKansio, "$targetDir/".basename($svnKansio), $closeMsg);
					return true;
				}
				
				return false;
			}
			else {
				svnAction("move", $svnKansio, "$targetDir/".basename($svnKansio), $closeMsg);
			}
		}
	}
}

if(!function_exists("svnFindCustomerDir")) {
	function svnFindCustomerDir ($tunnus) {
		global $kukarow;
		
		if(svnStatus===false) return false;
		
		$query = "	SELECT concat_ws(' ', nimi, if(nimitark='', NULL, nimitark), if(toim_ovttunnus='', NULL, concat('[', toim_ovttunnus, ']'))) dir
					FROM asiakas
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
		$res = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($res) == 1) {
			$row = mysql_fetch_array($res);
			if(svnAction("list", svnCustomers."/".$row["dir"]) !== false) {
				return svnCustomers."/".$row["dir"];
			}
		}

		return false;
	}		
}

if(!function_exists("svnSyncMaintenanceFolders")) {
	function svnSyncMaintenanceFolders ($type, $tunnus, $oldRow="") {
		global $kukarow;
		
		$type = strtoupper($type);
		
		if(svnStatus===false) return false;
		
		$table = $path = $templatePath = "";
		
		if($type == "ASIAKAS") {
			$table 			= "asiakas";
			$dirName 		= "concat_ws(' ', nimi, if(nimitark='', NULL, nimitark), if(toim_ovttunnus='', NULL, concat('[', toim_ovttunnus, ']')))";
			if($oldRow["toim_ovttunnus"] != "") {
				$oldDirName 	= trim("$oldRow[nimi] $oldRow[nimitark] [$oldRow[toim_ovttunnus]]");	
			}
			else {
				$oldDirName 	= trim("$oldRow[nimi] $oldRow[nimitark]");
			}
			
			$path 			= svnCustomers;
			$templatePath 	= svnCustomersDefaultDir;
		}
		elseif($type == "ASIAKKAAN_KOHDE") {
			$query = "	SELECT liitostunnus
						FROM asiakkaan_kohde
						WHERE yhtio='$kukarow[yhtio]' and tunnus = '$tunnus' and liitostunnus > 0";
			$res = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($res) == 1) {
				$row = mysql_fetch_array($res);
				$path = svnFindCustomerDir($row["liitostunnus"]);
				if($path !== false) {
					$table 			= "asiakkaan_kohde";
					$dirName 		= "kohde";
					$oldDirName 	= $oldRow["kohde"];
					$templatePath 	= svnCustomersTargetDefaultDir;
				}
				else {
					return false;
				}				
			}
			else {
				return false;
			}
		}
		else {
			return "Tyyppi puuttuu";
		}
		
		if(count($oldRow)<2) {
			$oldDirName = "";
		}
		
		//	Haetaan vaadittava tieto
		$query = "	SELECT $dirName dirName
					FROM $table
					WHERE yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
		$res = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($res) > 0) {
			$row = mysql_fetch_array($res);
			
			//	T�m� on meid�n tuleva dirrikka
			$pDir = trim($path."/".$row["dirName"]);
			
			if($oldDirName == "") {
				if($templatePath != "") {
					if(svnAction("copy", $templatePath, $pDir, t("%s avattu", $kieli, ucfirst(strtolower(t($type))))) !== false) {
						return true;
					}
					else {
						return t("Tapahtui virhe perustettaessa uusi $type");
					}
				}
				else {
					$tmpdir = "/tmp/svnImport-".md5(uniqid())."/".basename($pDir);
					mkdir(dirname($tmpdir));
					mkdir($tmpdir);
					if(svnAction("import",	"local:$tmpdir", $pDir, t("%s avattu", $kieli, ucfirst(strtolower(t($type))))) !== false) {
						return true;
					}
					else {
						return t("Tapahtui virhe perustettaessa uusi $type");
					}
					
					unlink(dirname($tmpdir));
				}
			}
			else {
				
				//	Jos kansiot omat samat, ei tartte tehd� mitt��
				if($path."/".$oldDirName == $pDir) {
					return true;
				}
				
				//	L�ydet��nk� vanha dirrikka?
				if(svnAction("move", $path."/".$oldDirName, $pDir, t("%s nimi muutettu", $kieli, ucfirst(strtolower(t($type))))) !== false) {
					return true;
				}
				else {
					echo "<font class='error'>".t("VIRHE: Koitettiin muuttaa nime�, mutta se ep�onnistui. Ota yhteytt� j�rjestelm�nvalvojaan.")."</font>";
					return t("Tapahtui virhe synkronoidessa $type");
				}
				
			}
		}
		else {
			return t("Projekti ei kelpaa");
		}
	}
}


//	Tarkastetaan/avataan lopuksi se yhteys
svnInit();

?>
