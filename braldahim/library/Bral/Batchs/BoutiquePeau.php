<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3.
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: $
 * $Author: $
 * $LastChangedDate: $
 * $LastChangedRevision: $
 * $LastChangedBy: $
 */
class Bral_Batchs_BoutiquePeau extends Bral_Batchs_Boutique {

	public function calculBatchImpl() {
		Bral_Util_Log::batchs()->trace("Bral_Batchs_BoutiquePeau - calculBatchImpl - enter -");

		Zend_Loader::loadClass('StockPeau');
		Zend_Loader::loadClass('BoutiquePeau');

		$stockPeauTable = new StockPeau();
		$mDate = date("Y-m-d");
		$stockPeauRowset = $stockPeauTable->findByDate($mDate);
		if (count($stockPeauRowset) > 0) {
			Bral_Util_Log::batchs()->info("Bral_Batchs_BoutiquePeau - calculBatchImpl - Stock Peau deja present pour le ".$mDate);
			return "Stock Peau deja present pour le ".$mDate;
		}

		Zend_Loader::loadClass('Region');
		$regionTable = new Region();
		$regionRowset = $regionTable->fetchall();

		$this->initDate();

		$this->calculAchatVente();
		$this->calculMoyennes();
		$this->calculRatios();
		foreach($regionRowset as $r) {
			$this->calculStock($r->id_region);
		}

		Bral_Util_Log::batchs()->trace("Bral_Batchs_BoutiquePeau - calculBatchImpl - exit -");
		return "Stock Peau cree pour le ".$mDate;
	}

	public function calculAchatVente() {
		Bral_Util_Log::batchs()->trace("Bral_Batchs_BoutiquePeau - calculAchatVente - enter -");
		$boutiquePeauTable = new BoutiquePeau();
		$this->nombreReprise = $boutiquePeauTable->countRepriseByDate($this->dateDebut, $this->dateFin);
		$this->nombreReprisePrecedent = $boutiquePeauTable->countRepriseByDate($this->dateDebutPrecedent, $this->dateFinPrecedent);
		$this->nombreVente = $boutiquePeauTable->countVenteByDate($this->dateDebut, $this->dateFin);
		$this->nombreVentePrecedent = $boutiquePeauTable->countVenteByDate($this->dateDebutPrecedent, $this->dateFinPrecedent);
		Bral_Util_Log::batchs()->trace("Bral_Batchs_BoutiquePeau - calculAchatVente - exit -");
	}


	public function calculStock($idRegion) {
		Bral_Util_Log::batchs()->trace("Bral_Batchs_BoutiquePeau - calculStock - ratio:".$this->ratio. " ratioPrecedent:".$this->ratioPrecedent);

		$stockPeauTable = new StockPeau();
		$stockPeauRowset = $stockPeauTable->findDernierStockByIdRegion($idRegion);

		foreach($stockPeauRowset as $s) {
			$nbInitial = $s["nb_peau_initial_stock_peau"];
			$tabPrix["prixReprise"] = $s["prix_unitaire_reprise_stock_peau"];
			$tabPrix["prixVente"] = $s["prix_unitaire_vente_stock_peau"];
			$tabPrix = $this->calculPrix($tabPrix);
			$this->updateStockBase($idRegion, $nbInitial, $tabPrix);
		}
		Bral_Util_Log::batchs()->trace("Bral_Batchs_BoutiquePeau - calculStock - exit -");
	}

	public function updateStockBase($idRegion, $nbInitial, $tabPrix) {
		$mDate = date("Y-m-d");

		$data = array(
			"date_stock_peau" => $mDate,
			"nb_peau_initial_stock_peau" => $nbInitial,
			"nb_peau_restant_stock_peau" => $nbInitial,
			"prix_unitaire_vente_stock_peau" => $tabPrix["prixVente"],
			"prix_unitaire_reprise_stock_peau" => $tabPrix["prixReprise"],
			"id_fk_region_stock_peau" => $idRegion,	
		);
		$stockPeauTable = new StockPeau();
		$stockPeauTable->insert($data);
	}

}