<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3. 
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: Terrasser.php 754 2008-12-16 07:54:24Z yvonnickesnault $
 * $Author: yvonnickesnault $
 * $LastChangedDate: 2008-12-16 08:54:24 +0100 (Tue, 16 Dec 2008) $
 * $LastChangedRevision: 754 $
 * $LastChangedBy: yvonnickesnault $
 */
class Bral_Competences_Terrasser extends Bral_Competences_Competence {

	function prepareCommun() {
		Zend_Loader::loadClass('Monstre');
		Zend_Loader::loadClass('Palissade');  
		Zend_Loader::loadClass('Route');
		Zend_Loader::loadClass('Zone');
	
		$this->view->terrasserOk = false;
		
		$monstreTable = new Monstre();
		$monstres = $monstreTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		$palissadeTable = new Palissade();
		$palissades = $palissadeTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		$hobbitTable = new Hobbit();
		$hobbits = $hobbitTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		$routeTable = new Route();
		$routes = $routeTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		
		$zoneTable = new Zone();
		$zone = $zoneTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		unset($zoneTable);
		
		if (count($zone) == 1) {
			$case = $zone[0];
			$this->environnement = $case["nom_systeme_environnement"];
			$this->view->environnement = $case["nom_environnement"];
		} else {
			throw new Zend_Exception(get_class($this)."::calculNbPa : Nombre de case invalide");
		}
		unset($zone);
		
		if (count($monstres) <= 0 && count($hobbits) == 1 && count($palissades) <= 0 && count($routes) <= 0 && $this->estEnvironnementValid($this->environnement)) {
			$this->view->terrasserOk = true;
		}
	}

	function prepareFormulaire() {
		if ($this->view->assezDePa == false) {
			return;
		}
	}

	function prepareResultat() {
		// Verification des Pa
		if ($this->view->assezDePa == false) {
			throw new Zend_Exception(get_class($this)." Pas assez de PA : ".$this->view->user->pa_hobbit);
		}
		
		if ($this->view->terrasserOk == false) {
			throw new Zend_Exception(get_class($this)." Terrasser interdit");
		}

		// calcul des jets
		$this->calculJets();

		if ($this->view->okJet1 === true) {
			$this->calculTerrasser();
		}
		
		$this->calculPx();
		$this->calculPoids();
		$this->calculBalanceFaim();
		$this->majHobbit();
	}
	
	private function calculTerrasser() {
		
		$date_creation = date("Y-m-d H:00:00");
		$nb_jours = ($this->view->user->vigueur_base_hobbit / 2) + Bral_Util_De::get_1d3();
		$date_fin = Bral_Util_ConvertDate::get_date_add_day_to_date($date_creation, $nb_jours);
		
		$data = array(
			"x_route"  => $this->view->user->x_hobbit,
			"y_route" => $this->view->user->y_hobbit,
			"id_fk_hobbit_route" => $this->view->user->id_hobbit,
			"est_route" => "non",
			"date_creation_route" => $date_creation,
			"date_fin_route" => $date_fin,
		);
		
		$routeTable = new Route();
		$routeTable->insert($data);
		unset($routeTable);
		
		$this->view->route = $data;
		$this->calculEvenement();
	}
	
	private function calculEvenement() {
		$estEvenement = false;
		$evenementMinerai = null;
		
		$de = Bral_Util_De::get_1d2();
		$de10 = Bral_Util_De::get_1d10();
		if ($de == 1) {
			//if ($de10 == 1) {
			if ($de10 > 0) {	
				$estEvenement = true;
				$evenementMinerai = $this->calculEvenementMinerai();
			}
		} else {
			//if ($de10 == 1) {
			if ($de10 > 0) {	
				$estEvenement = true;
				$this->calculEvenementRune();
			}
		}
		
		$this->view->estEvenement = $estEvenement;
		$this->view->evenementMinerai = $evenementMinerai;
	}
	
	private function calculEvenementMinerai() {
		Zend_Loader::loadClass("ElementMinerai");
		Zend_Loader::loadClass("LabanMinerai");
		Zend_Loader::loadClass("TypeMinerai");
		
		$retour["dansLaban"] = false;
		
		$typeMinerai = new TypeMinerai();
		$types = $typeMinerai->fetchAll();
		
		$nb = count($types);
		$deType = Bral_Util_De::get_de_specifique(1, $nb);
		foreach ($types as $t) {
			if ($t["id_type_minerai"] == $deType) {
				$retour["typeMinerai"] = $t["nom_type_minerai"];
				break;
			}
		}
		
		$poidsRestant = $this->view->user->poids_transportable_hobbit - $this->view->user->poids_transporte_hobbit;
		if ($poidsRestant < 0) $poidsRestant = 0;
		$nbMineraisPossible = floor($poidsRestant / Bral_Util_Poids::POIDS_MINERAI);
		
		if ($nbMineraisPossible >= 1) { // depot dans le laban
			$labanMineraiTable = new LabanMinerai();
			$data = array(
				"quantite_brut_laban_minerai" => 1,
				"id_fk_type_laban_minerai" => $deType,
				"id_fk_hobbit_laban_minerai" => $this->view->user->id_hobbit,
			);
			$labanMineraiTable->insertOrUpdate($data);
			$retour["dansLaban"] = true;
		} else { // depot a terre
			$elementMineraiTable = new ElementMinerai();
			$data = array (
				"x_element_minerai" => $this->view->user->x_hobbit,
				"y_element_minerai" => $this->view->user->y_hobbit,
				"id_fk_type_element_minerai" => $deType,
				"quantite_brut_element_minerai" => 1,
			);
			$elementMineraiTable->insertOrUpdate($data);
			$retour["dansLaban"] = false;
		}
		
		return $retour;
	}
	
	private function calculEvenementRune() {
		Zend_Loader::loadClass("Bral_Monstres_VieMonstre");
		Bral_Monstres_VieMonstre::dropRune($this->view->user->x_hobbit, $this->view->user->y_hobbit, $this->view->user->niveau_hobbit);
	}
	
	private function estEnvironnementValid($environnement) {
		$retour = false;
		switch($environnement) {
			case "plaine" :
			case "marais" :
			case "montagne" :
			case "foret" :
				$retour = true;
				break;
			case "caverne" :
			case "gazon" :
				$retour = false;
				break;
			default:
				throw new Zend_Exception(get_class($this)."::environnement invalide :".$this->nom_systeme_environnement);
		}
		return $retour;
	}
	
	function calculNbPa() {
		switch($this->environnement) {
			case "plaine" :
				$this->nb_pa = 2;
				break;
			case "marais" :
				$this->nb_pa = 2;
				break;
			case "montagne" :
				$this->nb_pa = 2;
				break;
			case "foret" :
				$this->nb_pa = 3;
				break;
			case "caverne" :
			case "gazon" :
				$this->nb_pa = false;
				break;
			default:
				throw new Zend_Exception(get_class($this)."::environnement invalide :".$this->environnement);
		}
		
		if ($this->view->user->pa_hobbit - $this->nb_pa < 0) {
			$this->view->assezDePa = false;
		} else {
			$this->view->assezDePa = true;
		}
	}
	
	function getListBoxRefresh() {
		return $this->constructListBoxRefresh(array("box_competences_metiers", "box_vue", "box_laban"));
	}
}