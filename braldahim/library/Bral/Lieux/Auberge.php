<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3.
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id$
 * $Author$
 * $LastChangedDate$
 * $LastChangedRevision$
 * $LastChangedBy$
 */
class Bral_Lieux_Auberge extends Bral_Lieux_Lieu {

	private $_utilisationPossible = false;
	private $_coutCastars = null;

	function prepareCommun() {
		Zend_Loader::loadClass("Lieu");
		Zend_Loader::loadClass("Laban");

		$this->_coutCastars = $this->calculCoutCastars();
		$this->_utilisationPossible = (($this->view->user->castars_hobbit -  $this->_coutCastars) >= 0);

		$this->view->poidsRestant = $this->view->user->poids_transportable_hobbit - $this->view->user->poids_transporte_hobbit;
		if ($this->view->poidsRestant < 0) $this->view->poidsRestant = 0;
		$this->view->nbPossible = floor($this->view->poidsRestant / Bral_Util_Poids::POIDS_RATION);

		$castarsRestants = $this->view->user->castars_hobbit -  $this->_coutCastars;
		$nbPossibleAvecCastars = floor($this->view->user->castars_hobbit / $this->_coutCastars);

		$this->view->nbDeduction = 0;
		if ($this->view->nbPossible >= $nbPossibleAvecCastars) {
			$this->view->nbPossible = $nbPossibleAvecCastars;
			$this->view->nbDeduction = 1;
		}

		$achatRation = true;
		if ($this->view->nbPossible < 1) {
			$this->view->nbPossible = 0;
			$achatRation = false;
		}

		$achatRationEtResto = true;
		if ( floor($castarsRestants / $this->_coutCastars) < 1 || $achatRation == false){
			$achatRationEtResto = false;
		}

		$tabChoix[1]["nom"] = "Se restaurer uniquement";
		$tabChoix[1]["valid"] = $this->_utilisationPossible;
		$tabChoix[1]["bouton"] = "Se Restaurer";
		$tabChoix[2]["nom"] = "Acheter des rations uniquement";
		$tabChoix[2]["valid"] = $achatRation;
		$tabChoix[2]["bouton"] = "Acheter";
		$tabChoix[3]["nom"] = "Se restaurer et acheter des rations";
		$tabChoix[3]["valid"] = $achatRationEtResto;
		$tabChoix[3]["bouton"] = "Se Restaurer et Acheter";

		$this->view->tabChoix = $tabChoix;
	}

	function prepareFormulaire() {
		$this->view->utilisationPossible = $this->_utilisationPossible;
		$this->view->coutCastars = $this->_coutCastars;
	}

	function prepareResultat() {

		// verification qu'il y a assez de castars
		if ($this->_utilisationPossible == false) {
			throw new Zend_Exception(get_class($this)." Achat impossible : castars:".$this->view->user->castars_hobbit." cout:".$this->_coutCastars);
		}

		if (((int)$this->request->get("valeur_1").""!=$this->request->get("valeur_1")."")) {
			throw new Zend_Exception("Bral_Lieux_Auberge :: Choix invalide : ".$this->request->get("valeur_1"));
		} else {
			$this->view->idChoix = (int)$this->request->get("valeur_1");
		}

		if ($this->view->idChoix == 2 || $this->view->idChoix == 3) {
			if (((int)$this->request->get("valeur_2").""!=$this->request->get("valeur_2")."")) {
				throw new Zend_Exception("Bral_Lieux_Auberge :: Nombre invalide : ".$this->request->get("valeur_2"));
			} else {
				$this->view->nbAcheter = (int)$this->request->get("valeur_2");
			}
		}

		if ($this->view->idChoix < 1 || $this->view->idChoix > 3) {
			throw new Zend_Exception("Bral_Lieux_Auberge :: Choix invalide 2 : ".$this->request->get("valeur_1"));
		}

		if ($this->view->tabChoix[$this->view->idChoix]["valid"] == false) {
			throw new Zend_Exception("Bral_Lieux_Auberge :: Choix invalide 3 : ".$this->view->tabChoix[$this->view->idChoix]["valid"]);
		}

		if ($this->view->nbAcheter > $this->view->nbPossible) {
			throw new Zend_Exception("Bral_Lieux_Auberge :: Nombre Rations invalide : ".$this->view->nbAcheter. " possible=".$this->view->nbPossible);
		}

		if ($this->view->idChoix == 1 || $this->view->idChoix == 3) {
			$this->view->user->balance_faim_hobbit = $this->view->user->balance_faim_hobbit + 80;
			if ($this->view->user->balance_faim_hobbit > 100) {
				$this->view->user->balance_faim_hobbit = 100;
			}
			Zend_Loader::loadClass("Bral_Util_Quete");
			$this->view->estQueteEvenement = Bral_Util_Quete::etapeManger($this->view->user, true);
		} else {
			$this->_coutCastars = 0;
		}

		if ($this->view->idChoix == 2 || $this->view->idChoix == 3) {
			if ($this->view->nbAcheter > 0) {
				$this->calculAchat();
				$this->_coutCastars = $this->_coutCastars + ($this->calculCoutCastars() * $this->view->nbAcheter);
			}
		}

		$this->view->user->castars_hobbit = $this->view->user->castars_hobbit - $this->_coutCastars;
		Bral_Util_Faim::calculBalanceFaim($this->view->user);
		$this->majHobbit();

		$this->view->coutCastars = $this->_coutCastars;
	}

	private function calculAchat() {
		$labanTable = new Laban();
		$data = array(
			'id_fk_hobbit_laban' => $this->view->user->id_hobbit,
			'quantite_ration_laban' => $this->view->nbAcheter,
		);
		$labanTable->insertOrUpdate($data);
	}

	function getListBoxRefresh() {
		return $this->constructListBoxRefresh(array("box_laban"));
	}

	private function calculCoutCastars() {
		return 5;
	}
}