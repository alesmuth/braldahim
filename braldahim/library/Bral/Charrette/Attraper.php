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
class Bral_Charrette_Attraper extends Bral_Charrette_Charrette {

	function getNomInterne() {
		return "box_action";
	}

	function getTitreAction() {
		return "Attraper une charrette";
	}

	function prepareCommun() {
		Zend_Loader::loadClass("Charrette");

		$tabCharrettes = null;
		$this->view->possedeCharrette = false;
		$this->view->attraperCharrettePossible = false;

		$charretteTable = new Charrette();

		$nombre = $charretteTable->countByIdHobbit($this->view->user->id_hobbit);
		if ($nombre > 0) {
			$this->view->possedeCharrette = true;
		}
		
		Zend_Loader::loadClass("Bral_Util_Metier");
		$tab = Bral_Util_Metier::prepareMetier($this->view->user->id_hobbit, $this->view->user->sexe_hobbit);
		$this->tabMetierCourant = $tab["tabMetierCourant"];
		$estMenuisierOuBucheron = false;
		if ($this->tabMetierCourant["nom_systeme"] == "bucheron" || $this->tabMetierCourant["nom_systeme"] == "menuisier") {
			$estMenuisierOuBucheron = true;
		}
		

		$charrettes = $charretteTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit);
		foreach ($charrettes as $c) {
			$this->view->attraperCharrettePossible = true;
			$possible = false;
			$detail = "";
			if (($this->view->user->force_base_hobbit >= $c["force_base_min_type_materiel"] &&
				$this->view->user->agilite_base_hobbit >= $c["agilite_base_min_type_materiel"] &&
				$this->view->user->vigueur_base_hobbit >= $c["vigueur_base_min_type_materiel"] &&
				$this->view->user->sagesse_base_hobbit >= $c["sagesse_base_min_type_materiel"]) ||
				($c["nom_systeme_type_materiel"] == "charrette_legere" && $estMenuisierOuBucheron)		
				) {
				$possible = true;
			} else {
				if ($this->view->user->force_base_hobbit < $c["force_base_min_type_materiel"]) {
					$detail .= " Niv. requis FOR:".$c["force_base_min_type_materiel"];
				}
				if ($this->view->user->agilite_base_hobbit < $c["agilite_base_min_type_materiel"]) {
					$detail .= " Niv. requis AGI:".$c["agilite_base_min_type_materiel"];
				}
				if ($this->view->user->sagesse_base_hobbit < $c["sagesse_base_min_type_materiel"]) {
					$detail .= " Niv. requis SAG:".$c["sagesse_base_min_type_materiel"];
				}
				if ($this->view->user->vigueur_base_hobbit < $c["vigueur_base_min_type_materiel"]) {
					$detail .= " Niv. requis VIG:".$c["vigueur_base_min_type_materiel"];
				}
			}
			$tabCharrettes[] = array ("id_charrette" => $c["id_charrette"],"nom" => $c["nom_type_materiel"], "possible" => $possible, "detail" => $detail);
		}
		$this->view->charrettes = $tabCharrettes;
	}

	function prepareFormulaire() {
	}

	function prepareResultat() {

		// Verification des Pa
		if ($this->view->assezDePa == false) {
			throw new Zend_Exception(get_class($this)." Pas assez de PA : ".$this->view->user->pa_hobbit);
		}

		// Verification abattre arbre
		if ($this->view->possedeCharrette == true) {
			throw new Zend_Exception(get_class($this)." Possede deja charrette ");
		}

		if (((int)$this->request->get("valeur_1").""!=$this->request->get("valeur_1")."")) {
			throw new Zend_Exception(get_class($this)." Charrette invalide : ".$this->request->get("valeur_1"));
		} else {
			$this->view->idCharrette = (int)$this->request->get("valeur_1");
		}

		$controle = false;
		foreach ($this->view->charrettes as $c) {
			if ($this->view->idCharrette == $c["id_charrette"] && $c["possible"] == true) {
				$controle = true;
				break;
			}
		}
		if ($controle == false) {
			throw new Zend_Exception(get_class($this)." Charrette invalide idh:".$this->view->user->pa_hobbit. " ihc:".$this->view->idCharrette);
		}

		$this->calculAttrapperCharrette($this->view->idCharrette);
		$this->calculBalanceFaim();

		$id_type = $this->view->config->game->evenements->type->ramasser;
		$details = "[h".$this->view->user->id_hobbit."] a attrapé une charrette";
		$this->setDetailsEvenement($details, $id_type);
	}

	private function calculAttrapperCharrette($idCharrette) {
		$charretteTable = new Charrette();
		$dataUpdate = array(
			"id_fk_hobbit_charrette" => $this->view->user->id_hobbit,
			"x_charrette" => null,
			"y_charrette" => null,
		);
		$where = "id_charrette = ".$idCharrette;
		$charretteTable->update($dataUpdate, $where);
	}

	function getListBoxRefresh() {
		return $this->constructListBoxRefresh(array("box_vue"));
	}
}
