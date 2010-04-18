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

/*
 * Attaque : 0.5*(jet d'AGI)+BM AGI + bonus arme att
 * dégats : 0.5*(jet FOR)+BM FOR+ bonus arme dégats
 * dégats critiques : (1.5*(0.5*FOR))+BM FOR+bonus arme dégats
 */
class Bral_Competences_Frenesie extends Bral_Competences_Competence {

	private $_coef = 1;

	function prepareCommun() {
		Zend_Loader::loadClass("Monstre");
		Zend_Loader::loadClass("Bral_Monstres_VieMonstre");
		Zend_Loader::loadClass('Bral_Util_Attaque');
		Zend_Loader::loadClass("HobbitEquipement");

		$tabHobbits = null;
		$tabMonstres = null;

		$armeTirPortee = false;
		$hobbitEquipement = new HobbitEquipement();
		$equipementPorteRowset = $hobbitEquipement->findByTypePiece($this->view->user->id_hobbit,"arme_tir");

		if (count($equipementPorteRowset) > 0){
			$armeTirPortee = true;
		} else if ($this->view->user->est_intangible_hobbit == "non") {
			$estRegionPvp = Bral_Util_Attaque::estRegionPvp($this->view->user->x_hobbit, $this->view->user->y_hobbit);

			if ($estRegionPvp) {
				// recuperation des hobbits qui sont presents sur la vue
				$hobbitTable = new Hobbit();
				$hobbits = $hobbitTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit, $this->view->user->z_hobbit, $this->view->user->id_hobbit, false);
				foreach($hobbits as $h) {
					$tab = array(
						'id_hobbit' => $h["id_hobbit"],
						'nom_hobbit' => $h["nom_hobbit"],
						'prenom_hobbit' => $h["prenom_hobbit"],
					);
					if ($this->view->user->est_soule_hobbit == 'non' ||
					($this->view->user->est_soule_hobbit == 'oui' && $h["soule_camp_hobbit"] != $this->view->user->soule_camp_hobbit)) {
						$tabHobbits[] = $tab;
					}
				}
			}

			// recuperation des monstres qui sont presents sur la vue
			$monstreTable = new Monstre();
			$monstres = $monstreTable->findByCase($this->view->user->x_hobbit, $this->view->user->y_hobbit, $this->view->user->z_hobbit);
			foreach($monstres as $m) {
				if ($m["genre_type_monstre"] == 'feminin') {
					$m_taille = $m["nom_taille_f_monstre"];
				} else {
					$m_taille = $m["nom_taille_m_monstre"];
				}
				if ($m["id_fk_type_groupe_monstre"] == $this->view->config->game->groupe_monstre->type->gibier) {
					$estGibier = true;
				} else {
					$estGibier = false;
				}
				$tabMonstres[] = array("id_monstre" => $m["id_monstre"], "nom_monstre" => $m["nom_type_monstre"], 'taille_monstre' => $m_taille, 'niveau_monstre' => $m["niveau_monstre"], 'est_gibier' => $estGibier);
			}

			$this->view->nHobbits = count($tabHobbits);
			if ($this->view->nHobbits > 0) shuffle($tabHobbits);
			$this->view->tabHobbits = $tabHobbits;
			
			$this->view->nMonstres = count($tabMonstres);
			if ($this->view->nMonstres > 0) shuffle($tabMonstres);
			$this->view->tabMonstres = $tabMonstres;
			
			$this->view->estRegionPvp = $estRegionPvp;
		}
		$this->view->armeTirPortee = $armeTirPortee;
	}

	function prepareFormulaire() {
		// rien à faire ici
	}

	function prepareResultat() {

		if ($this->view->assezDePa == true && $this->view->armeTirPortee == false && 
		($this->view->nHobbits > 0 || $this->view->nMonstres > 0) && $this->view->user->est_soule_hobbit == 'non' && $this->view->user->est_intangible_hobbit == 'non') {
			// OK
		} else {
			throw new Zend_Exception("Erreur Frenesie");
		}
		
		// calcul des jets
		$this->calculJets();
		$retours = null;

		if ($this->view->okJet1 === true) {
			if (isset($this->view->tabHobbits) && count($this->view->tabHobbits) > 0) {
				foreach ($this->view->tabHobbits as $h) {
					$this->retourAttaque = $this->attaqueHobbit($this->view->user, $h["id_hobbit"]);
					$this->calculPx();
					$retours[] = $this->retourAttaque;
					$this->_coef = $this->_coef * 0.8;
				}
			}

			if (isset($this->view->tabMonstres) && count($this->view->tabMonstres) > 0) {
				foreach ($this->view->tabMonstres as $m) {
					$this->retourAttaque = $this->attaqueMonstre($this->view->user, $m["id_monstre"]);
					$this->calculPx();
					$retours[] = $this->retourAttaque;
					$this->_coef = $this->_coef * 0.8;
				}
			}
		}

		$this->view->retoursAttaques = $retours;
		$this->calculBalanceFaim();
		$this->majHobbit();
	}

	function getListBoxRefresh() {
		return $this->constructListBoxRefresh(array("box_competences_metiers", "box_vue", "box_lieu"));
	}

	protected function calculJetAttaque($hobbit) {
		//Attaque : 0.5*(jet d'AGI)+BM AGI + bonus arme att
		$jetAttaquant = Bral_Util_De::getLanceDe6($this->view->config->game->base_agilite + $hobbit->agilite_base_hobbit);
		$jetAttaquant = floor((0.5 * $jetAttaquant) + $hobbit->agilite_bm_hobbit + $hobbit->agilite_bbdf_hobbit + $hobbit->bm_attaque_hobbit);

		if ($jetAttaquant < 0) {
			$jetAttaquant = 0;
		}
		return floor($this->_coef * $jetAttaquant);
	}

	protected function calculDegat($hobbit) {
		$this->view->effetRune = false;

		$jetsDegat["critique"] = 0;
		$jetsDegat["noncritique"] = 0;
		$jetDegatForce = 0;
		$coefCritique = 1.5;
			
		$jetDegatForce = Bral_Util_De::getLanceDe6($this->view->config->game->base_force + $hobbit->force_base_hobbit);

		if (Bral_Util_Commun::isRunePortee($hobbit->id_hobbit, "EM")) {
			$this->view->effetRune = true;
			// dégats : Jet FOR + BM + Bonus de dégat de l'arme
			// dégats critiques : Jet FOR *1,5 + BM + Bonus de l'arme
			$jetsDegat["critique"] = $coefCritique * $jetDegatForce;
			$jetsDegat["noncritique"] = $jetDegatForce;
		} else {
			// * dégats : 0.5*(jet FOR)+BM FOR+ bonus arme dégats
			// * dégats critiques : (1.5*(0.5*FOR))+BM FOR+bonus arme dégats
			$jetsDegat["critique"] = $coefCritique * (0.5 * $jetDegatForce);
			$jetsDegat["noncritique"] = 0.5 * $jetDegatForce;
		}

		$jetsDegat["critique"] = floor($this->_coef * ($jetsDegat["critique"] + $hobbit->force_bm_hobbit + $hobbit->force_bbdf_hobbit + $hobbit->bm_degat_hobbit));
		$jetsDegat["noncritique"] = floor($this->_coef * ($jetsDegat["noncritique"] + $hobbit->force_bm_hobbit + $hobbit->force_bbdf_hobbit + $hobbit->bm_degat_hobbit));

		return $jetsDegat;
	}

	public function calculPx() {

		if ($this->_coef == 1) { // pour la première cible
			parent::calculPx();

			$this->view->nb_px_commun = 0;
			$this->view->calcul_px_generique = false;

			if ($this->view->retourAttaque["attaqueReussie"] === true) {
				$this->view->nb_px_perso = $this->view->nb_px_perso + 1;
			}
		}

		if ($this->retourAttaque["mort"] === true) {
			// [10+2*(diff de niveau) + Niveau Cible ]
			$this->view->nb_px_commun = $this->view->nb_px_commun + 10+2*($this->view->retourAttaque["cible"]["niveau_cible"] - $this->view->user->niveau_hobbit) + $this->view->retourAttaque["cible"]["niveau_cible"];
			if ($this->view->nb_px_commun < $this->view->nb_px_perso) {
				$this->view->nb_px_commun = $this->view->nb_px_perso;
			}
		}
		$this->view->nb_px = $this->view->nb_px_perso + $this->view->nb_px_commun;
	}
}