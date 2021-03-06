<?php

$laskuformaatti 		= "FINVOICE";

// parseroidaan xml
$yhtio 					= $xml->BuyerOrganisationUnitNumber;
$verkkotunnus_vas		= $xml->BuyerPartyDetails->BuyerOrganisationTaxCode;
$laskun_tyyppi 			= $xml->InvoiceDetails->InvoiceTypeCode;
$laskun_numero 			= $xml->InvoiceDetails->InvoiceNumber;
$laskun_ebid 			= "FINVOICEXML";
$laskun_tapvm			= $xml->EpiDetails->EpiIdentificationDetails->EpiDate;
$laskun_lapvm			= $xml->EpiDetails->EpiIdentificationDetails->EpiDate;
$laskun_erapaiva		= $xml->EpiDetails->EpiPaymentInstructionDetails->EpiDateOptionDate;
$laskun_kapvm 			= "";
$laskun_kasumma 		= "";
$laskuttajan_ovt		= $xml->SellerOrganisationUnitNumber;
$laskuttajan_nimi		= utf8_decode($xml->SellerPartyDetails->SellerOrganisationName);
$laskuttajan_vat		= $xml->SellerPartyDetails->SellerOrganisationTaxCode;
$laskuttajan_valkoodi   = "";
$laskun_pankkiviite 	= $xml->EpiDetails->EpiPaymentInstructionDetails->EpiRemittanceInfoIdentifier;
$laskun_asiakastunnus	= $xml->BuyerPartyDetails->BuyerPartyIdentifier;
$laskun_summa_eur		= (float) str_replace(",", ".", $xml->EpiDetails->EpiPaymentInstructionDetails->EpiInstructedAmount);
$laskun_tilausviite 	= utf8_decode($xml->InvoiceDetails->AgreementIdentifier);
$laskun_asiakkaan_tilausnumero = utf8_decode($xml->InvoiceDetails->OrderIdentifier);
$kauttalaskutus 		= utf8_decode($xml->DeliveryDetails->TerminalAddressText);

$toim_asiakkaantiedot = array();
$toim_asiakkaantiedot["toim_ovttunnus"] = trim($xml->DeliveryOrganisationUnitNumber);
$toim_asiakkaantiedot["ytunnus"] 		= trim($xml->DeliveryPartyDetails->DeliveryPartyIdentifier);
$toim_asiakkaantiedot["nimi"] 			= trim(utf8_decode($xml->DeliveryPartyDetails->DeliveryOrganisationName));
$toim_asiakkaantiedot["osoite"] 		= trim(utf8_decode($xml->DeliveryPartyDetails->DeliveryPostalAddressDetails->DeliveryStreetName));
$toim_asiakkaantiedot["postino"] 		= trim(utf8_decode($xml->DeliveryPartyDetails->DeliveryPostalAddressDetails->DeliveryPostCodeIdentifier));
$toim_asiakkaantiedot["postitp"] 		= trim(utf8_decode($xml->DeliveryPartyDetails->DeliveryPostalAddressDetails->DeliveryTownName));

$ostaja_asiakkaantiedot = array();
$ostaja_asiakkaantiedot["toim_ovttunnus"] 	= trim($xml->BuyerOrganisationUnitNumber);
$ostaja_asiakkaantiedot["ytunnus"] 			= trim($xml->BuyerPartyDetails->BuyerPartyIdentifier);
$ostaja_asiakkaantiedot["nimi"] 			= trim(utf8_decode($xml->BuyerPartyDetails->BuyerOrganisationName));
$ostaja_asiakkaantiedot["osoite"] 			= trim(utf8_decode($xml->BuyerPartyDetails->BuyerPostalAddressDetails->BuyerStreetName));
$ostaja_asiakkaantiedot["postino"] 			= trim(utf8_decode($xml->BuyerPartyDetails->BuyerPostalAddressDetails->BuyerPostCodeIdentifier));
$ostaja_asiakkaantiedot["postitp"] 			= trim(utf8_decode($xml->BuyerPartyDetails->BuyerPostalAddressDetails->BuyerTownName));

$laskuttajan_toimittajanumero = "";

if ($yhtio == "") {
	$yhtio = $xml->BuyerPartyDetails->BuyerPartyIdentifier;
}

if ($yhtio == "") {
	$yhtio = $xml->BuyerPartyDetails->BuyerOrganisationTaxCode;
}


// Onko tunnus IBAN-muodossa, jos ei niin varmistetaan, ett� se on ovtmuodossa
if (strlen($yhtio) != 18 or strtoupper(substr($yhtio,0,2)) != "FI") {
	// Varmistetaan, ett� tunnus on ovtunnus muodossa
	$yhtio = preg_replace("/[^0-9]/", "", $yhtio);

	if (strpos($yhtio, "0037") === FALSE) {
		$yhtio = "0037".$yhtio;
	}
}

if ($laskun_erapaiva == "") {
	$laskun_erapaiva = $xml->PaymentTermsDetails->InvoiceDueDate;
}

if ($laskun_pankkiviite == "") {
	$laskun_pankkiviite = $xml->EpiDetails->EpiIdentificationDetails->EpiReference;
}

if ($laskun_pankkiviite == "") {
	$laskun_pankkiviite = $xml->InvoiceDetails->SellerReferenceIdentifier;
}

if ($laskun_summa_eur == 0) {
	$laskun_summa_eur = (float) str_replace(",", ".", $xml->InvoiceDetails->InvoiceTotalVatIncludedAmount);
}

// Varmistetaan, ett� tunnus on ytunnus muodossa (Finvoicessa t��ll� tulee siis vastanottajan ytunnus FI etuliitteell�)
$verkkotunnus_vas = preg_replace("/[^0-9]/", "", $verkkotunnus_vas);

if ($laskun_tyyppi == "INV02") {
	// Hyvityslaskut pit�� merkata hyvitykseksi jotta ne osataan k�sitell� oikein
	$laskun_tyyppi = "381";
}

// K�sitell��n tuoterivit
$tuotetiedot	= $xml->InvoiceRow;
$i				= 0;
$rtuoteno		= array();
$ealvi		 	= array("");
$lisavat	 	= array("");

if (count($tuotetiedot) > 0) {
	foreach ($tuotetiedot as $tuotetieto) {
		// tuotekoodi
		$rtuoteno[$i]["tuoteno"] = utf8_decode($tuotetieto->ArticleIdentifier);

		if ($rtuoteno[$i]["tuoteno"] != "") {

			// Tallennetaan riville jos t�m� on kauttalaskutusta
			if ($kauttalaskutus == "KAUTTALASKUTUS") {
				$rtuoteno[$i]["kauttalaskutus"] = "KAUTTALASKUTUS";
			}

			$rtuoteno[$i]["nimitys"] = utf8_decode($tuotetieto->ArticleName);
			$rtuoteno[$i]["rivihinta"] = (float) str_replace(",", ".", $tuotetieto->RowVatExcludedAmount);

			$xvati = str_replace(",", ".", $tuotetieto->RowVatRatePercent);

			$rtuoteno[$i]["alv"] = $xvati;
			$ealvi[$i] = $xvati;

			$rtuoteno[$i]["ale"] = (float) str_replace(",", ".", $tuotetieto->RowDiscountPercent);
			$rtuoteno[$i]["hinta"] = (float) str_replace(",", ".", $tuotetieto->UnitPriceAmount);

			// rivikommentti
			$xrinfo = utf8_decode($tuotetieto->RowFreeText);

			if (is_array($xrinfo)) {
				foreach ($xrinfo as $xln) {
					$rtuoteno[$i]["kommentti"] .= $xln."\n";
				}
			}
			else {
				$rtuoteno[$i]["kommentti"] = $xrinfo;
			}

			$rtuoteno[$i]["kommentti"] = "Tilausnumero: ".$tuotetieto->RowIdentifierUrlText."\n".$rtuoteno[$i]["kommentti"]; // alkuper�inen tilausnumero rivikommenttiin mukaan
			$rtuoteno[$i]["kommentti"] = trim($rtuoteno[$i]["kommentti"]);

			$rtuoteno[$i]["kpl"] = (float) str_replace(",", ".", $tuotetieto->DeliveredQuantity);
			$rtuoteno[$i]["yksikko"] = $tuotetieto->DeliveredQuantity->attributes()->QuantityUnitCode;
			$rtuoteno[$i]["tilaajanrivinro"] = $tuotetieto->RowIdentifier;
		}
		else {
			//	Poistetaan t�m� tietue
			unset($rtuoteno[$i]);
			$i--;
		}
		$i++;
	}
}

// Verotiedot
$vattiedot	= $xml->InvoiceDetails->VatSpecificationDetails;
$i 			= 0;

if (count($vattiedot) > 0) {
	foreach ($vattiedot as $vattieto) {
		$lisavat[$i] = str_replace(",", ".", $vattieto->VatRatePercent);
		$i++;
	}
}

?>