<?php

	$otsikko = 'Yrityksen pankkitilit';
	$otsikko_nappi = "yrityksen pankkitili";

	$kentat = 'tunnus, nimi, tilino, maksulimitti, oletus_kulutili, oletus_rahatili, oletus_selvittelytili, kaytossa HIDDEN_kaytossa';
	$jarjestys = 'nimi, tunnus';

	if ($nayta_poistetut == "") {
		$rajauslisa = " and yriti.kaytossa != 'E' ";
	}
	else {
		$rajauslisa	= "";
	}

?>