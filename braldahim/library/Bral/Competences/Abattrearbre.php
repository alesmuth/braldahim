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
class Bral_Competences_Abattrearbre extends Bral_Competences_Competence {

	function prepareCommun() {
		Zend_Loader::loadClass("Charrette");
		Zend_Loader::loadClass("Zone");
		Zend_Loader::loadClass('Lieu'); 	
		Zend_Loader::loadClass('Echoppe'); 
		Zend_Loader::loadClass('Ville'); 
		
		$villeTable = new Ville();
		$villes = $villeTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		unset($villeTable);
		$lieuxTable = new Lieu();
		$lieux = $lieuxTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		unset($lieuxTable);
		$zoneTable = new Zone();
		$zones = $zoneTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		unset($zoneTable);
		$echoppeTable = new Echoppe();
		$echoppes = $echoppeTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		unset($echoppeTable);
		
		$this->view->abattreArbreLieuOk = true;
		$this->view->abattreArbreVilleOk = true;
		$this->view->abattreArbreEchoppeOk = true;
		
		if (count($lieux) > 0) {
			$this->view->abattreArbreLieuOk = false;
		}
		unset($lieux);
		
		if (count($villes) > 0) {
			$this->view->abattreArbreVilleOk = false;
		}		
		unset($villes);
		
		if (count($echoppes) > 0) {
			$this->view->abattreArbreEchoppeOk = false;
		}		
		unset($echoppes);
				
		$zone = $zones[0];
		
		
		switch($zone["nom_systeme_environnement"]) {
			case "foret" :
				$this->view->abattreArbreEnvironnementOk = true;
				break;
			case "marais":
			case "montagne":
			case "caverne":
			case "plaine" :
				$this->view->abattreArbreEnvironnementOk = false;
				break;
			default :
				throw new Exception("Abattre un arbre Environnement invalide:".$zone["nom_systeme_environnement"]. " x=".$this->view->user->x_hobbit." y=".$this->view->user->y_hobbit);
		}
		
		$charretteTable = new Charrette();
		$nombre = $charretteTable->countByIdHobbit($this->view->user->id_hobbit);
		$this->view->charettePleine = true;
		if ($nombre == 1) {
			$this->view->possedeCharrette = true;
			$tabPoidsRondins = Bral_Util_Poids::calculPoidsCharretteTransportable($this->view->user->id_hobbit, $this->view->user->vigueur_base_hobbit);
			if ($tabPoidsRondins["nb_rondins_transportables"] - $tabPoidsRondins["nb_rondins_presents"] > 0) {
				$this->view->charettePleine = false;
			}
		} else {
			$this->view->possedeCharrette = false;
		}
		
		unset($zones);
		unset($zone);
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
		
		// Verification abattre arbre
		if ($this->view->abattreArbreEnvironnementOk == false || $this->view->abattreArbreLieuOk == false || $this->view->abattreArbreVilleOk == false || $this->view->possedeCharrette == false || $this->view->abattreArbreEchoppeOk == false) {
			throw new Zend_Exception(get_class($this)." Abattre un arbre interdit ");
		}
		
		if ($this->view->charettePleine == true) {
			throw new Zend_Exception(get_class($this)." Charette pleine !");
		}
		
		// calcul des jets
		$this->calculJets();

		if ($this->view->okJet1 === true) {
			$this->calculAbattreArbre();
		}
		
		$this->calculPx();
		$this->calculBalanceFaim();
		$this->majHobbit();
	}
	
	/*
	 * Uniquement utilisable en forêt.
	 * Le Hobbit abat un arbre : il ramasse n rondins (directement dans la charrette). 
	 * Le nombre de rondins ramassés est fonction de la VIGUEUR :
     * de 0 à 4 : 1D3 + BM VIG/2
     * de 5 à 9 : 2D3 + BM VIG/2
     * de 10 à 14 :3D3 + BM VIG/2
     * de 15 à 19 : 4D3 + BM VIG/2
     * etc ... 
	 */
	private function calculAbattreArbre() {
		Zend_Loader::loadClass("Charrette");
		Zend_Loader::loadClass('Bral_Util_Commun');
		
		$this->view->effetRune = false;
		
		$nb = floor($this->view->user->vigueur_base_hobbit / 5) + 1;
		$this->view->nbRondins = Bral_Util_De::getLanceDeSpecifique($nb, 1, 3);
		
		if (Bral_Util_Commun::isRunePortee($this->view->user->id_hobbit, "VA")) { // s'il possède une rune VA
			$this->view->effetRune = true;
			$this->view->nbRondins = ceil($this->view->nbRondins * 1.5);
		}
		
		$this->view->nbRondins  = $this->view->nbRondins  + ($this->view->user->vigueur_bm_hobbit + $this->view->user->vigueur_bbdf_hobbit) / 2 ;
		$this->view->nbRondins  = intval($this->view->nbRondins);
		
		$tabPoidsRondins = Bral_Util_Poids::calculPoidsCharretteTransportable($this->view->user->id_hobbit, $this->view->user->vigueur_base_hobbit);
		
		if ($this->view->nbRondins <= 0) {
			$this->view->nbRondins  = 1;
		}
		
		if ($this->view->nbRondins > $tabPoidsRondins["nb_rondins_transportables"] - $tabPoidsRondins["nb_rondins_presents"]) {
			$this->view->nbRondins = $tabPoidsRondins["nb_rondins_transportables"] - $tabPoidsRondins["nb_rondins_presents"];
		}
		
		$charretteTable = new Charrette();
		$data = array(
			'quantite_rondin_charrette' => $this->view->nbRondins,
			'id_fk_hobbit_charrette' => $this->view->user->id_hobbit,
		);
		$charretteTable->updateCharrette($data);
		unset($charretteTable);
	}
	
	function getListBoxRefresh() {
		return array("box_profil", "box_competences_metiers", "box_vue", "box_laban", "box_charrette", "box_evenements");
	}
}
