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
class PalmaresController extends Zend_Controller_Action {

	function init() {
		Zend_Loader::loadClass("Bral_Xml_Response");
		Zend_Loader::loadClass("Bral_Xml_Entry");
		Zend_Loader::loadClass("Bral_Util_String");
		$this->initView();
		$this->view->config = Zend_Registry::get('config');
	}

	function indexAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesNaissance($this->_request, $this->view, false), "boite_a");
		$this->prepareCommun();
		$this->render();
	}
	
	function naissanceAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesNaissance($this->_request, $this->view, false), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	function combattantspveAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesCombattantspve($this->_request, $this->view, false), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	function combattantspvpAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesCombattantspvp($this->_request, $this->view, false), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	function mortsAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesMorts($this->_request, $this->view, false), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	function experienceAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesExperience($this->_request, $this->view, false), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	function monstresAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesMonstres($this->_request, $this->view, false), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	function mineursAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesRecolteurs($this->_request, $this->view, "mineurs"), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	function herboristesAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesRecolteurs($this->_request, $this->view, "herboristes"), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	function bucheronsAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesRecolteurs($this->_request, $this->view, "bucherons"), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	function chasseursAction() {
		$this->prepareFiltre();
		$this->addBoxes(Bral_Palmares_Factory::getBoxesRecolteurs($this->_request, $this->view, "chasseurs"), "boite_a");
		$this->prepareCommun();
		$this->render("index");
	}
	
	private function prepareCommun() {
		$this->getBoxesData();
		$this->prepareSelection();
	}
	
	private function prepareSelection() {
		$selection[] = array("nom" => "Naissance", "url" => "naissance");
		$selection[] = array("nom" => "Grands Combattants PvE", "url" => "combattantspve");
		$selection[] = array("nom" => "Grands Combattants PvP", "url" => "combattantspvp");
		$selection[] = array("nom" => "Morts", "url" => "morts");
		$selection[] = array("nom" => "Expérience", "url" => "experience");
		$selection[] = array("nom" => "Monstres", "url" => "monstres");
//		$selection[] = array("nom" => "Super Hobbit", "url" => "superhobbits");
		$this->view->selection = $selection;
		
		$selectionRecolteurs = null;
		$selectionRecolteurs[] = array("nom" => "Mineurs", "url" => "mineurs");
		$selectionRecolteurs[] = array("nom" => "Herboristes", "url" => "herboristes");
		$selectionRecolteurs[] = array("nom" => "Bûcherons", "url" => "bucherons");
		$selectionRecolteurs[] = array("nom" => "Chasseurs", "url" => "chasseurs");
		$this->view->selectionRecolteurs = $selectionRecolteurs;
		
		$selectionFabricants = null;
//		$selectionFabricants[] = array("nom" => "Menuisier", "url" => "menuisiers");
//		$selectionFabricants[] = array("nom" => "Forgeron", "url" => "forgerons");
//		$selectionFabricants[] = array("nom" => "Tanneur", "url" => "tanneurs");
		$this->view->selectionFabricants = $selectionFabricants;
	}
	
	private function prepareFiltre() {
		$listFiltre[] = array("nom" => "Mois en cours", "valeur" => 1);
		$listFiltre[] = array("nom" => "Dernier mois", "valeur" => 2);
		$listFiltre[] = array("nom" => "Année en cours", "valeur" => 3);
		$listFiltre[] = array("nom" => "Année précédente", "valeur" => 4);
		$listFiltre[] = array("nom" => "Depuis toujours", "valeur" => 5);		
		$this->view->listFiltre = $listFiltre;	
	}
	
	function loadAction() {
		$this->prepareFiltre();
		Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
		Zend_Layout::resetMvcInstance();
		$this->xml_response = new Bral_Xml_Response();
		$xml_entry = new Bral_Xml_Entry();
		$xml_entry->set_type("display");
		$box = Bral_Palmares_Factory::getBox($this->_request, $this->view, true);
		$xml_entry->set_box($box);
		$xml_entry->set_valeur($box->getNomInterne());
		$this->xml_response->add_entry($xml_entry);
		unset($xml_entry);
		$this->xml_response->render();
	}
	
	private function addBoxes($tab, $position) {
		foreach($tab as $t) {
			$this->m_list[$position][] = $t;
		}
	}
	
	private function addBox($p, $position) {
		$this->m_list[$position][] = $p;
	}

	private function getBoxesData() {
		return $this->getDataList("boite_a");
	}

	private function getDataList($nom) {
		$l = $this->m_list[$nom];
		$liste = "";
		$data = "";
		$onglets = null;

		if ($nom != "aucune") {
			for ($i = 0; $i < count($l); $i ++) {
				if ($i == 0) {
					$css = "actif";
				} else {
					$css = "inactif";
				}
				$tab = array ("titre" => $l[$i]->getTitreOnglet(), "nom" => $l[$i]->getNomInterne(), "css" => $css, "chargementInBoxes" => $l[$i]->getChargementInBoxes());
				$onglets[] = $tab;
				$liste .= $l[$i]->getNomInterne();
				if ($i < count($l)-1 ) {
					$liste .= ",";
				}
			}

			for ($i = 0; $i < count($l); $i ++) {
				if ($i == 0) {
					$display = "block";
				} else {
					$display = "none";
				}

				$l[$i]->setDisplay($display);
				$data .= $l[$i]->render();
			}

			$this->view->onglets = $onglets;
			$this->view->liste = $liste;
			$this->view->data = $data;
			$this->view->conteneur = $nom;
			unset($onglets);
			unset($liste);
			unset($data);
			unset($nom);
		}
	}
}