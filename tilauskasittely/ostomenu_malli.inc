<?php

/*
	T��ll� siis handlataan kaikki menun rakentelut
	T�ss� siis pelkk� kysely tilaus_osto.php handlaa kyselyt
*/

$ostomenu = array("oletus"	=> array(		"menuset" 	=> "Toimittajan tuotteet", 
											"query" 	=> "liitostunnus =  '$laskurow[liitostunnus]' ORDER BY tuoteno")
				);

?>