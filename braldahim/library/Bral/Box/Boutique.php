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
abstract class Bral_Box_Boutique extends Bral_Box_Box {
	
	public function getNomInterne() {
		return "box_lieu";		
	}
	
	public function setDisplay($display) {
		$this->view->display = $display;
	}
	
	protected function preRender() {
		Zend_Loader::loadClass("Lieu");
		
		$lieuxTable = new Lieu();
		$lieuRowset = $lieuxTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit, $this->view->user->z_hobbit);
		unset($lieuxTable);
		
		if (count($lieuRowset) <= 0) {
			throw new Zend_Exception("Bral_Box_Boutique::nombre de lieux invalide <= 0 !");
		} elseif (count($lieuRowset) > 1) {
			throw new Zend_Exception("Bral_Box_Boutique::nombre de lieux invalide > 1 !");
		} elseif (count($lieuRowset) == 1) {
			$lieu = $lieuRowset[0];
			unset($lieuRowset);
			$this->view->nomLieu = $lieu["nom_lieu"];
			$this->view->paUtilisationBoutique = $lieu["pa_utilisation_type_lieu"];
		}
		
		$this->view->nom_interne = $this->getNomInterne();
	}
}

