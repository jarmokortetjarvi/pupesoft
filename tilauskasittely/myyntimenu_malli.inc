<?php

/*
	T��ll� siis handlataan kaikki menun rakentelut
	T�ss� siis pelkk� kysely tilaus_myynti.php handlaa kyselyt
*/

$myyntimenu = array("oletus"	=> array(	"menuset" 	=> "Perustuotteet", 
											"query" 	=> "tuotetyyppi =  '' ORDER BY tuoteno"),
											
					"palvelut"	=> array(	"menuset" 	=> "Palvelut",
											"query"		=> "tuotetyyppi = 'K' ORDER BY tuoteno")
					);

?>