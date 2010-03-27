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
class Bral_Box_Tour extends Bral_Box_Box {

	function __construct($request, $view, $interne) {
		Zend_Loader::loadClass("Bral_Util_Log");

		$this->_request = $request;
		$this->view = $view;
		$this->view->affichageInterne = $interne;

		$hobbitTable = new Hobbit();
		$hobbitRowset = $hobbitTable->find($this->view->user->id_hobbit);
		$this->hobbit = $hobbitRowset->current();

		$this->nomsTour = Zend_Registry::get('nomsTour');
		$this->view->user->nom_tour = $this->nomsTour[$this->view->user->tour_position_hobbit];
		$this->calculInfoTour();
	}

	function getTitreOnglet() {
		return false;
	}

	function getNomInterne() {
		return "box_tour";
	}

	function setDisplay($display) {
		$this->view->display = $display;
	}

	function render() {
		$this->view->messages = Bral_Util_Messagerie::prepareMessages($this->view->user->id_hobbit, null, 1, 5, true);
		Zend_Loader::loadClass("Message");
		$messageTable = new Message();
		$this->view->nbMessagesNonLus = $messageTable->countByToIdNotRead($this->view->user->id_hobbit);
		$this->view->user->nom_tour = $this->nomsTour[$this->view->user->tour_position_hobbit];
		return $this->view->render("interface/tour.phtml");
	}

	public function modificationTour() {
		Bral_Util_Log::tour()->debug(get_class($this)." modificationTour - enter - user=".$this->view->user->id_hobbit);

		$this->is_update_tour = false;
		$this->is_nouveau_tour = false;

		if ($this->view->user->activation === false) {
			return false;
		}

		// Calcul de la nouvelle date de fin
		$date_courante = date("Y-m-d H:i:s");
		$this->is_nouveau_tour = $this->calcul_debut_nouveau($date_courante);

		// nouveau tour (ou ko : en cas de ko : la date de fin de tour doit aªtre positionnee au ko)
		if ($this->is_nouveau_tour) {
			Bral_Util_Log::tour()->debug(get_class($this)." Nouveau tour");
			$this->calculDLA();

			$this->hobbit->tour_position_hobbit = $this->view->config->game->tour->position_latence;
			$this->is_update_tour = true;
		}

		/* Si des DLA ont ete manquees, on prend comme date de debut la date courante
		 * et la date de fin, la date courante + 6 heures, le joueur se trouve
		 * directement en position de cumul
		 */

		Bral_Util_Log::tour()->debug(get_class($this)." date_fin_latence=".$this->hobbit->date_fin_latence_hobbit);
		Bral_Util_Log::tour()->debug(get_class($this)." date_debut_cumul".$this->hobbit->date_debut_cumul_hobbit);
		Bral_Util_Log::tour()->debug(get_class($this)." date_courante=".$date_courante);
		Bral_Util_Log::tour()->debug(get_class($this)." date fin tour=".$this->hobbit->date_fin_tour_hobbit);
		Bral_Util_Log::tour()->debug(get_class($this)." tour position=".$this->hobbit->tour_position_hobbit);

		$this->is_tour_manque = false;
		// Mise a jour du nombre de PA + position tour
		if ($this->hobbit->est_ko_hobbit == "oui") {
			Bral_Util_Log::tour()->debug(get_class($this)." KO du hobbit");
			$mdate = date("Y-m-d H:i:s");
			$this->hobbit->date_debut_cumul_hobbit = $mdate;
			$this->hobbit->date_fin_tour_hobbit = Bral_Util_ConvertDate::get_date_add_time_to_date($mdate, $this->view->config->game->tour->inscription->duree_base_cumul);
			$this->hobbit->date_debut_tour_hobbit = Bral_Util_ConvertDate::get_date_remove_time_to_date($mdate, $this->view->config->game->tour->inscription->duree_base_cumul);
			$this->hobbit->date_fin_latence_hobbit = Bral_Util_ConvertDate::get_date_remove_time_to_date($mdate, $this->view->config->game->tour->inscription->duree_base_milieu);
			$this->hobbit->tour_position_hobbit = $this->view->config->game->tour->position_cumul;
			$this->is_update_tour = true;
			$this->hobbit->pa_hobbit = $this->view->config->game->pa_max_cumul;
		} else if ($date_courante > $this->hobbit->date_fin_tour_hobbit) { // Perte d'un tour
			Bral_Util_Log::tour()->debug(get_class($this)." Perte d'un tour");
			$this->hobbit->date_fin_tour_hobbit = Bral_Util_ConvertDate::get_date_add_time_to_date($date_courante, $this->view->config->game->tour->duree_tour_manque);
			$this->hobbit->tour_position_hobbit = $this->view->config->game->tour->position_cumul;
			$this->hobbit->pa_hobbit = $this->view->config->game->pa_max_cumul;
			$this->hobbit->est_engage_next_dla_hobbit = "non";
			$this->hobbit->est_engage_hobbit = "non";
			$this->is_tour_manque  = true;
			$this->is_update_tour = true;
		} elseif(($date_courante < $this->hobbit->date_fin_latence_hobbit) // Latence
		&& $this->is_nouveau_tour) {
			Bral_Util_Log::tour()->debug(get_class($this)." Latence Tour");
			$this->hobbit->tour_position_hobbit = $this->view->config->game->tour->position_latence;
			$this->hobbit->pa_hobbit = 0;
			$this->is_update_tour = true;
		} elseif(($date_courante >= $this->hobbit->date_fin_latence_hobbit && $date_courante < $this->hobbit->date_debut_cumul_hobbit) // Milieu
		&& ( (!$this->is_nouveau_tour && ($this->hobbit->tour_position_hobbit != $this->view->config->game->tour->position_milieu))
		|| ($this->is_nouveau_tour))) {
			Bral_Util_Log::tour()->debug(get_class($this)." Milieu Tour");
			$this->hobbit->tour_position_hobbit = $this->view->config->game->tour->position_milieu;
			$this->hobbit->pa_hobbit = $this->view->config->game->pa_max;
			$this->is_update_tour = true;
		} elseif(($date_courante >= $this->hobbit->date_debut_cumul_hobbit && $date_courante < $this->hobbit->date_fin_tour_hobbit)  // Cumul
		&& ( (!$this->is_nouveau_tour && ($this->hobbit->tour_position_hobbit != $this->view->config->game->tour->position_cumul))
		|| ($this->is_nouveau_tour))) {
			Bral_Util_Log::tour()->debug(get_class($this)." Cumul tour");
			// Si le joueur a deja  eu des PA
			if ($this->hobbit->tour_position_hobbit == $this->view->config->game->tour->position_milieu && !$this->is_nouveau_tour) {
				Bral_Util_Log::tour()->debug(get_class($this)." Le joueur a deja eu des PA");
				$this->hobbit->pa_hobbit = $this->hobbit->pa_hobbit + $this->view->config->game->pa_max;
			} else { // S'il vient d'activer et qu'il n'a jamais eu de PA dans ce tour
				Bral_Util_Log::tour()->debug(get_class($this)." Le joueur n'a pas encore eu de PA");
				$this->hobbit->pa_hobbit = $this->view->config->game->pa_max_cumul;
			}
			$this->hobbit->tour_position_hobbit = $this->view->config->game->tour->position_cumul;
			$this->is_update_tour = true;
		}

		if (($this->is_update_tour) || ($this->is_nouveau_tour) || ($this->hobbit->est_ko_hobbit == "oui")) {
			Bral_Util_Log::tour()->debug(get_class($this)." modificationTour - exit - true");
			return true;
		} else {
			Bral_Util_Log::tour()->debug(get_class($this)." modificationTour - exit - false");
			return false;
		}
	}

	public function activer() {
		Bral_Util_Log::tour()->trace(get_class($this)." activer - enter -");

		if ($this->view->user->activation === false) {
			Bral_Util_Log::tour()->trace(get_class($this)." le joueur n'a pas activé la DLA");
			return false;
		}

		$this->view->effetPotion = false;
		$this->view->effetHobbit = false;

		$this->view->effetMotB = false;
		$this->view->effetMotE = false;
		$this->view->effetMotK = false;
		$this->view->effetMotM = false;
		$this->view->effetMotN = false;
		$this->view->effetMotO = false;
		$this->view->effetMotU = false;
		$this->view->effetMotV = false;

		$this->view->ciblesEffetN = null;
		$this->view->ciblesEffetO = null;
		$this->view->ciblesEffetU = null;

		$this->is_tour_manque = false;

		$this->view->charretteDetruite = false;

		$this->modificationTour();

		// Mise a jour en cas de KO
		$this->calculKo();

		// Si c'est un nouveau tour, on met les BM de force, agi, sag, vue, vig a  0
		// Ensuite, on les recalcule suivant l'equipement porte et les potions en cours
		if ($this->is_nouveau_tour) {
			Bral_Util_Log::tour()->trace(get_class($this)." activer - is_nouveau_tour - true");
			$this->hobbit->force_bm_hobbit = 0;
			$this->hobbit->agilite_bm_hobbit = 0;
			$this->hobbit->vigueur_bm_hobbit = 0;
			$this->hobbit->sagesse_bm_hobbit = 0;
			$this->hobbit->vue_bm_hobbit = 0;
			$this->hobbit->regeneration_hobbit = 0;
			$this->hobbit->armure_naturelle_hobbit = 0;
			$this->hobbit->armure_equipement_hobbit = 0;
			$this->hobbit->armure_bm_hobbit = 0;
			$this->hobbit->pv_max_bm_hobbit = 0;
			$this->hobbit->bm_attaque_hobbit = 0;
			$this->hobbit->bm_degat_hobbit = 0;
			$this->hobbit->bm_defense_hobbit = 0;
			$this->hobbit->duree_bm_tour_hobbit = 0;
			$this->hobbit->bm_marcher_hobbit = 0;
			
			// Nouvelle DLA
			$this->hobbit->nb_dla_jouees_hobbit = $this->hobbit->nb_dla_jouees_hobbit + 1;
			 
			// Recalcul de l'armure naturelle
			$this->hobbit->armure_naturelle_hobbit = Bral_Util_Commun::calculArmureNaturelle($this->hobbit->force_base_hobbit, $this->hobbit->vigueur_base_hobbit);

			/* Application du malus de vue. */
			$this->hobbit->vue_bm_hobbit = $this->hobbit->vue_malus_hobbit;
			/* Remise a  zero du malus de vue. */
			$this->hobbit->vue_malus_hobbit = 0;

			/* Application du malus d'agilite. */
			$this->hobbit->agilite_bm_hobbit = $this->hobbit->agilite_malus_hobbit;
			/* Remise a  zero du malus d'agilite. */
			$this->hobbit->agilite_malus_hobbit = 0;

			// Calcul du poids transportable. // c'est aussi mis a  jour dans l'eujimnasiumne
			Zend_Loader::loadClass("Bral_Util_Poids");
			$this->hobbit->poids_transportable_hobbit = Bral_Util_Poids::calculPoidsTransportable($this->hobbit->force_base_hobbit);
			$this->hobbit->poids_transporte_hobbit = Bral_Util_Poids::calculPoidsTransporte($this->hobbit->id_hobbit, $this->hobbit->castars_hobbit);

			$this->calculBMEquipement();
			$this->calculBMPotion();
			$this->calculBMEffet();

			// Mise a  jour de la regeneration // c'est aussi mis a  jour dans l'eujimnasiumne
			$this->hobbit->regeneration_hobbit = floor($this->hobbit->vigueur_base_hobbit / 4) + 1;

			// calcul des pvs restants avec la regeneration
			$this->hobbit->pv_max_hobbit = Bral_Util_Commun::calculPvMaxBaseSansEffetMotE($this->view->config, $this->hobbit->vigueur_base_hobbit);

			$this->hobbit->est_engage_hobbit = $this->hobbit->est_engage_next_dla_hobbit;
			$this->hobbit->est_engage_next_dla_hobbit = 'non';

			$effetMotE = Bral_Util_Commun::getEffetMotE($this->view->user->id_hobbit);
			if ($effetMotE != null) {
				Bral_Util_Log::tour()->trace(get_class($this)." activer - effetMotE Actif - effetMotE=".$effetMotE);
				$this->view->effetMotE = true;
				$this->hobbit->pv_max_bm_hobbit = $this->hobbit->pv_max_bm_hobbit - ($effetMotE * 3);
			}

			if ($this->hobbit->pv_restant_hobbit > $this->hobbit->pv_max_hobbit + $this->hobbit->pv_max_bm_hobbit) {
				$this->hobbit->pv_restant_hobbit = $this->hobbit->pv_max_hobbit + $this->hobbit->pv_max_bm_hobbit;
			}

			$this->calculPv();

			if ($this->est_ko == false) {
				$this->hobbit->est_intangible_hobbit = "non";
			}

			Zend_Loader::loadClass("Bral_Util_Faim");
			Bral_Util_Faim::calculBalanceFaim($this->hobbit);
			Zend_Loader :: loadClass("Bral_Util_Tour");
			Bral_Util_Tour::updateTourTabac($this->hobbit);

			Zend_Loader::loadClass("Bral_Monstres_Util");
			Bral_Monstres_Util::marqueAJouer($this->hobbit->x_hobbit, $this->hobbit->y_hobbit);

			Zend_Loader::loadClass("Bral_Util_Charrette");
			$this->view->charretteDetruite = Bral_Util_Charrette::calculNouvelleDlaCharrette($this->hobbit->id_hobbit, $this->hobbit->x_hobbit, $this->hobbit->y_hobbit);

			Zend_Loader::loadClass("Bral_Util_Equipement");
			$this->view->equipementDetruit = Bral_Util_Equipement::calculNouvelleDlaEquipement($this->hobbit->id_hobbit, $this->hobbit->x_hobbit, $this->hobbit->y_hobbit);
		}

		if ($this->is_update_tour) {
			Bral_Util_Log::tour()->trace(get_class($this)." activer - is_update_tour - true");
			$this->updateDb();
		}

		$this->view->is_update_tour = $this->is_update_tour;
		$this->view->is_nouveau_tour = $this->is_nouveau_tour;
		$this->view->is_tour_manque = $this->is_tour_manque;
		$this->view->is_ko = $this->est_ko;

		if (($this->is_update_tour) || ($this->is_nouveau_tour)) {
			$this->calculInfoTour();
			Bral_Util_Log::tour()->trace(get_class($this)." activer - exit - true");
			return true;
		} else {
			Bral_Util_Log::tour()->trace(get_class($this)." activer - exit - false");
			return false;
		}
	}
	/* Verification que c'est bien le debut d'un
	 * nouveau tour pour le joueur
	 * @return false si non
	 * @return true si oui
	 */
	private function calcul_debut_nouveau($date_courante) {
		Bral_Util_Log::tour()->trace(get_class($this)." calcul_debut_nouveau - enter -");
		Bral_Util_Log::tour()->debug(get_class($this)." calcul_debut_nouveau - this->hobbit->date_fin_tour_hobbit=".$this->hobbit->date_fin_tour_hobbit);
		Bral_Util_Log::tour()->debug(get_class($this)." calcul_debut_nouveau - date_courante=".$date_courante);
		Bral_Util_Log::tour()->debug(get_class($this)." calcul_debut_nouveau - this->hobbit->est_ko_hobbit=".$this->hobbit->est_ko_hobbit);
		if ($this->hobbit->date_fin_tour_hobbit < $date_courante || $this->hobbit->est_ko_hobbit == 'oui') {
			Bral_Util_Log::tour()->debug(get_class($this)." calcul_debut_nouveau - exit - true");
			return true;
		} else {
			Bral_Util_Log::tour()->debug(get_class($this)." calcul_debut_nouveau - exit - false");
			return false;
		}
	}

	private function calculKo() {
		Bral_Util_Log::tour()->trace(get_class($this)." calculKo - enter -");
		$this->est_ko = ($this->hobbit->est_ko_hobbit == "oui");

		if ($this->est_ko) {
			Zend_Loader::loadClass('Lieu');
			Zend_Loader::loadClass('Bral_Util_De');
			$this->is_update_tour = true;

			// remise en vu
			$this->hobbit->est_ko_hobbit = "non";

			$this->hobbit->est_intangible_hobbit = "oui";

			// perte des PX
			if ($this->hobbit->est_soule_hobbit == "non") {
				$this->hobbit->px_commun_hobbit = 0;
				$this->hobbit->px_perso_hobbit = $this->hobbit->px_perso_hobbit - floor($this->hobbit->px_perso_hobbit / 3);
			}

			// balance de faim
			$this->hobbit->balance_faim_hobbit = 50;

			// points de vie
			$this->hobbit->pv_restant_hobbit = floor(($this->view->config->game->pv_base + $this->hobbit->vigueur_base_hobbit*$this->view->config->game->pv_max_coef) / 2);

			// statut engage
			$this->hobbit->est_engage_hobbit = "non";
			$this->hobbit->est_engage_next_dla_hobbit = "non";

			$this->calculKoPosition();

			Zend_Loader::loadClass("EffetPotionHobbit");
			$effetPotionHobbitTable = new EffetPotionHobbit();
			$where = "id_fk_hobbit_cible_effet_potion_hobbit = ".intval($this->hobbit->id_hobbit);
			$effetPotionHobbitTable->delete($where);

			Zend_Loader::loadClass("EffetHobbit");
			$effetHobbitTable = new EffetHobbit();
			$where = "id_fk_hobbit_cible_effet_hobbit = ".intval($this->hobbit->id_hobbit);
			$effetHobbitTable->delete($where);

			Zend_Loader::loadClass("HobbitsCompetences");
			$hobbitsCompetencesTable = new HobbitsCompetences();
			$hobbitsCompetencesTable->annuleEffetsTabacByIdHobbit($this->hobbit->id_hobbit);
		}
		Bral_Util_Log::tour()->trace(get_class($this)." calculKo - exit -");
	}

	// recalcule de la position suite à un KO
	private function calculKoPosition() {
		Zend_Loader::loadClass("TypeLieu");
		
		// recalcul de la position
		$lieuTable = new Lieu();
		if ($this->hobbit->est_soule_hobbit == "oui" && $this->hobbit->id_fk_soule_match_hobbit != null) { // match de Soule
			Zend_Loader::loadClass("SouleMatch");
			$souleMatchTable = new SouleMatch();
			$rowset = $souleMatchTable->findByIdMatch($this->hobbit->id_fk_soule_match_hobbit);
			$match = $rowset[0];

			$x = $match["x_min_soule_terrain"] + ($match["x_max_soule_terrain"] - $match["x_min_soule_terrain"]);
			if ($this->hobbit->soule_camp_hobbit == "a") {
				$y = $match["y_max_soule_terrain"];
			} else {
				$y = $match["y_min_soule_terrain"];
			}

			$lieuRowset = $lieuTable->findByTypeAndPosition(TypeLieu::ID_TYPE_HOPITAL, $x, $y);
		} elseif ($this->hobbit->est_donjon_hobbit == "oui") { // Donjon
			$lieuRowset = $lieuTable->findByTypeAndPosition(TypeLieu::ID_TYPE_POSTEDEGARDE, $this->hobbit->x_hobbit, $this->hobbit->y_hobbit, "non");
			$lieuRowset[0]["z_lieu"] = $lieuRowset[0]["z_lieu"] - 1; // 1 case en dessous le poste de garde
		} else {
			$lieuRowset = $lieuTable->findByTypeAndPosition(TypeLieu::ID_TYPE_HOPITAL, $this->hobbit->x_hobbit, $this->hobbit->y_hobbit, "non");
		}
		$this->hobbit->x_hobbit = $lieuRowset[0]["x_lieu"];
		$this->hobbit->y_hobbit = $lieuRowset[0]["y_lieu"];
		$this->hobbit->z_hobbit = $lieuRowset[0]["z_lieu"];
	}

	private function calculBMEquipement() {
		Bral_Util_Log::tour()->trace(get_class($this)." calculBMEquipement - enter -");
		Zend_Loader::loadClass("HobbitEquipement");
		Zend_Loader::loadClass("EquipementRune");
		Zend_Loader::loadClass("EquipementBonus");
		Zend_Loader::loadClass("Bral_Util_Attaque");

		// on va chercher l'equipement porte et les runes
		$tabEquipementPorte = null;
		$hobbitEquipementTable = new HobbitEquipement();
		$equipementPorteRowset = $hobbitEquipementTable->findByIdHobbit($this->view->user->id_hobbit);
		unset($hobbitEquipementTable);
		Zend_Loader::loadClass("Bral_Util_Equipement");
		$tabEquipementPorte = Bral_Util_Equipement::prepareTabEquipements($equipementPorteRowset, false, $this->view->user->niveau_hobbit);

		if (count($tabEquipementPorte) > 0) {

			Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement nb equipement porte:".count($equipementPorteRowset));

			$tabWhere = null;
			$equipementRuneTable = new EquipementRune();
			$equipementBonusTable = new EquipementBonus();
			$equipements = null;

			$idEquipements = null;

			foreach ($tabEquipementPorte as $e) {
				$idEquipements[] = $e["id_equipement"];

				$this->hobbit->force_bm_hobbit = $this->hobbit->force_bm_hobbit + $e["force"];
				$this->hobbit->agilite_bm_hobbit = $this->hobbit->agilite_bm_hobbit + $e["agilite"];
				$this->hobbit->vigueur_bm_hobbit = $this->hobbit->vigueur_bm_hobbit + $e["vigueur"];
				$this->hobbit->sagesse_bm_hobbit = $this->hobbit->sagesse_bm_hobbit + $e["sagesse"];
				$this->hobbit->vue_bm_hobbit = $this->hobbit->vue_bm_hobbit + $e["vue"];
				$this->hobbit->armure_equipement_hobbit = $this->hobbit->armure_equipement_hobbit + $e["armure"];
				$this->hobbit->bm_attaque_hobbit = $this->hobbit->bm_attaque_hobbit + $e["attaque"];
				$this->hobbit->bm_degat_hobbit = $this->hobbit->bm_degat_hobbit + $e["degat"];
				$this->hobbit->bm_defense_hobbit = $this->hobbit->bm_defense_hobbit + $e["defense"];
					
				if ($e["nom_systeme_mot_runique"] == "mot_b") {
					$this->view->effetMotB = true;
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotB actif - avant : this->hobbit->sagesse_bm_hobbit".$this->hobbit->sagesse_bm_hobbit);
					$this->hobbit->sagesse_bm_hobbit = $this->hobbit->sagesse_bm_hobbit + (2 * ($e["niveau"] + 1));
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotB actif - apres : this->hobbit->sagesse_bm_hobbit".$this->hobbit->sagesse_bm_hobbit. " ajout de :".(2 * $e["niveau"]));
				}

				if ($e["nom_systeme_mot_runique"] == "mot_k") {
					$this->view->effetMotK = true;
					if ($e["attaque"] > 0) { // positif
						$val = $e["attaque"];
					} else { // negatif
						$val = abs($e["attaque"]) / 2;
					}
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotK actif - avant : val a ajouer au bm_attaque_hobbit=".$val);
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotK actif - avant : this->hobbit->bm_attaque_hobbit".$this->hobbit->bm_attaque_hobbit);
					$this->hobbit->bm_attaque_hobbit = $this->hobbit->bm_attaque_hobbit + $val;
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotK actif - apres : this->hobbit->bm_attaque_hobbit".$this->hobbit->bm_attaque_hobbit);
				}

				if ($e["nom_systeme_mot_runique"] == "mot_m") {
					$this->view->effetMotM = true;
					if ($e["defense"] > 0) { // positif
						$val = $e["defense"];
					} else { // negatif
						$val = abs($e["defense"]) / 2;
					}
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotM actif - avant : val a ajouer au bm_defense_hobbit=".$val);
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotM actif - avant : this->hobbit->bm_defense_hobbit".$this->hobbit->bm_defense_hobbit);
					$this->hobbit->bm_defense_hobbit = $this->hobbit->bm_defense_hobbit + $val;
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotM actif - apres : this->hobbit->bm_defense_hobbit".$this->hobbit->bm_defense_hobbit);
				}

				if ($e["nom_systeme_mot_runique"] == "mot_n") {
					$this->view->effetMotN = true;
					$this->view->effetMotNPointsDegats = 2 * $e["niveau_recette_equipement"];
					$this->view->ciblesEffetN = Bral_Util_Attaque::calculDegatCase($this->view->config, $this->hobbit, $this->view->effetMotNPointsDegats, $this->view);
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotN actif - logs presents dans bral_attaque.log");
				}

				if ($e["nom_systeme_mot_runique"] == "mot_o") {
					$this->view->effetMotO = true;
					$this->view->ciblesEffetO = Bral_Util_Attaque::calculSoinCase($this->view->config, $this->hobbit, 2 * $e["niveau_recette_equipement"]);
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotO actif - logs presents dans bral_attaque.log");
				}

				if ($e["nom_systeme_mot_runique"] == "mot_u") {
					$this->view->effetMotU = true;
					$this->view->effetMotUPointsDegats = $e["niveau"] / 2;
					$this->view->effetMotUNbCibles = 0;
					$ciblesEffetU = Bral_Util_Attaque::calculDegatCase($this->view->config, $this->hobbit, $this->view->effetMotUPointsDegats, $this->view);
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotU actif - avant recuperation pv this->hobbit->pv_restant_hobbit=".$this->hobbit->pv_restant_hobbit);
					if ($ciblesEffetU != null && $ciblesEffetU["n_cible"] != null) {
						$this->view->effetMotUNbCibles = $ciblesEffetU["n_cible"];
						$this->hobbit->pv_restant_hobbit = $this->hobbit->pv_restant_hobbit + $ciblesEffetU["n_cible"];
						if ($this->hobbit->pv_restant_hobbit > $this->hobbit->pv_max_hobbit + $this->hobbit->pv_max_bm_hobbit) {
							$this->hobbit->pv_restant_hobbit = $this->hobbit->pv_max_hobbit + $this->hobbit->pv_max_bm_hobbit;
						}
					}
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotU actif - apres recuperation pv this->hobbit->pv_restant_hobbit=".$this->hobbit->pv_restant_hobbit);
					$this->view->ciblesEffetU = $ciblesEffetU;
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotU actif - logs presents dans bral_attaque.log");
				}

				if ($e["nom_systeme_mot_runique"] == "mot_v") {
					$this->view->effetMotV = true;
					$this->hobbit->vue_bm_hobbit = $this->hobbit->vue_bm_hobbit + 2;
					Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - effetMotV actif - this->hobbit->vue_bm_hobbit=".$this->hobbit->vue_bm_hobbit);
				}

			}

			$equipementBonus = $equipementBonusTable->findByIdsEquipement($idEquipements);
			unset($equipementBonusTable);

			if (count($equipementBonus) > 0) {
				foreach($equipementBonus as $b) {
					$this->hobbit->armure_equipement_hobbit = intval($this->hobbit->armure_equipement_hobbit + $b["armure_equipement_bonus"] + $b["vernis_bm_armure_equipement_bonus"]);
					$this->hobbit->agilite_bm_hobbit = intval($this->hobbit->agilite_bm_hobbit + $b["agilite_equipement_bonus"] + $b["vernis_bm_agilite_equipement_bonus"]);
					$this->hobbit->force_bm_hobbit = intval($this->hobbit->force_bm_hobbit + $b["force_equipement_bonus"] + $b["vernis_bm_force_equipement_bonus"]);
					$this->hobbit->sagesse_bm_hobbit = intval($this->hobbit->sagesse_bm_hobbit + $b["sagesse_equipement_bonus"] + $b["vernis_bm_sagesse_equipement_bonus"]);
					$this->hobbit->vigueur_bm_hobbit = intval($this->hobbit->vigueur_bm_hobbit + $b["vigueur_equipement_bonus"] + $b["vernis_bm_vigueur_equipement_bonus"]);

					$this->hobbit->vue_bm_hobbit = intval($this->hobbit->vue_bm_hobbit + $b["vernis_bm_vue_equipement_bonus"]);
					$this->hobbit->bm_attaque_hobbit = intval($this->hobbit->bm_attaque_hobbit + $b["vernis_bm_attaque_equipement_bonus"]);
					$this->hobbit->bm_degat_hobbit = intval($this->hobbit->bm_degat_hobbit + $b["vernis_bm_degat_equipement_bonus"]);
					$this->hobbit->bm_defense_hobbit = intval($this->hobbit->bm_defense_hobbit + $b["vernis_bm_defense_equipement_bonus"]);
				}
			}

			$equipementRunes = $equipementRuneTable->findByIdsEquipement($idEquipements);

			unset($equipementRuneTable);

			if (count($equipementRunes) > 0) {
				foreach($equipementRunes as $r) {
					if ($r["nom_type_rune"] == "KR") {
						// KR Bonus de AGI = Niveau d'AGI/3 arrondi inferieur
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune KR active - avant this->hobbit->agilite_bm_hobbit=".$this->hobbit->agilite_bm_hobbit);
						$this->hobbit->agilite_bm_hobbit = $this->hobbit->agilite_bm_hobbit + floor($this->hobbit->agilite_base_hobbit / 3);
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune KR active - apres this->hobbit->agilite_bm_hobbit=".$this->hobbit->agilite_bm_hobbit);
					} else if ($r["nom_type_rune"] == "ZE") {
						// ZE Bonus de FOR = Niveau de FOR/3 arrondi inferieur
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune ZE active - avant this->hobbit->force_bm_hobbit=".$this->hobbit->force_bm_hobbit);
						$this->hobbit->force_bm_hobbit = $this->hobbit->force_bm_hobbit + floor($this->hobbit->force_base_hobbit / 3);
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune Ze active - apres this->hobbit->force_bm_hobbit=".$this->hobbit->force_bm_hobbit);
					} else if ($r["nom_type_rune"] == "IL") {
						// IL Reduit le tour de jeu de 10 minutes
						$this->hobbit->duree_bm_tour_hobbit = $this->hobbit->duree_bm_tour_hobbit - 10;
					} else if ($r["nom_type_rune"] == "MU") {
						// MU PV + niveau du Hobbit/10 arrondi inferieur
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune MU active - avant this->hobbit->pv_max_bm_hobbit=".$this->hobbit->pv_max_bm_hobbit);
						$this->hobbit->pv_max_bm_hobbit = $this->hobbit->pv_max_bm_hobbit + floor($this->hobbit->niveau_hobbit / 10) + 1;
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune MU active - apres this->hobbit->pv_max_bm_hobbit=".$this->hobbit->pv_max_bm_hobbit);
					} else if ($r["nom_type_rune"] == "RE") {
						// RE ARM NAT + Niveau du Hobbit/10 arrondi inferieur
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune RE active - apres this->hobbit->armure_naturelle_hobbit=".$this->hobbit->armure_naturelle_hobbit);
						$this->hobbit->armure_naturelle_hobbit = $this->hobbit->armure_naturelle_hobbit + floor($this->hobbit->niveau_hobbit / 10);
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune RE active - apres this->hobbit->armure_naturelle_hobbit=".$this->hobbit->armure_naturelle_hobbit);
					} else if ($r["nom_type_rune"] == "OG") {
						// OG Bonus de VIG = Niveau de VIG/3 arrondi inferieur
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune OG active - avant this->hobbit->vigueur_bm_hobbit=".$this->hobbit->vigueur_bm_hobbit);
						$this->hobbit->vigueur_bm_hobbit = $this->hobbit->vigueur_bm_hobbit + floor($this->hobbit->vigueur_base_hobbit / 3);
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune OG active - avant this->hobbit->vigueur_bm_hobbit=".$this->hobbit->vigueur_bm_hobbit);
					} else if ($r["nom_type_rune"] == "OX") {
						// OX Poids maximum porte augmente de Niveau du Hobbit/10 arrondi inferieur
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune OX active - avant this->hobbit->poids_transportable_hobbit=".$this->hobbit->poids_transportable_hobbit);
						$this->hobbit->poids_transportable_hobbit = $this->hobbit->poids_transportable_hobbit + floor($this->hobbit->niveau_hobbit / 10);
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune OX active - avant this->hobbit->poids_transportable_hobbit=".$this->hobbit->poids_transportable_hobbit);
					} else if ($r["nom_type_rune"] == "UP") {
						// UP Bonus de SAG = Niveau de SAG/3 arrondi inferieur
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune UP active - avant this->hobbit->sagesse_bm_hobbit=".$this->hobbit->sagesse_bm_hobbit);
						$this->hobbit->sagesse_bm_hobbit = $this->hobbit->sagesse_bm_hobbit + floor($this->hobbit->sagesse_base_hobbit / 3);
						Bral_Util_Log::tour()->debug(get_class($this)." calculBMEquipement - rune UP active - avant this->hobbit->sagesse_bm_hobbit=".$this->hobbit->sagesse_bm_hobbit);
					}
				}
				unset($equipementRunes);
			}
			unset($equipementPorteRowset);
		}
		Bral_Util_Log::tour()->trace(get_class($this)." calculBMEquipement - exit -");
	}

	private function calculBMPotion() {
		Bral_Util_Log::tour()->trace(get_class($this)." calculBMPotion - enter -");
		Zend_Loader::loadClass("Bral_Util_EffetsPotion");
		$effetsPotions = Bral_Util_EffetsPotion::calculPotionHobbit($this->hobbit, true);

		if (count($effetsPotions) > 0) {
			$this->view->effetPotion = true;
			$this->view->effetPotionPotions = $effetsPotions;
		}
		Bral_Util_Log::tour()->trace(get_class($this)." calculBMPotion - exit -");
	}

	private function calculBMEffet() {
		Bral_Util_Log::tour()->trace(get_class($this)." calculBMEffet - enter -");
		Zend_Loader::loadClass("Bral_Util_Effets");
		$effetsHobbit = Bral_Util_Effets::calculEffetHobbit($this->hobbit, true);

		if (count($effetsHobbit) > 0) {
			$this->view->effetHobbit = true;
			$this->view->effetHobbitEffets = $effetsHobbit;
		}
		Bral_Util_Log::tour()->trace(get_class($this)." calculBMEffet - exit -");
	}

	private function calculInfoTour() {
		Bral_Util_Log::tour()->trace(get_class($this)." calculInfoTour - enter -");
		$info = "";
		if ($this->view->user->tour_position_hobbit == $this->view->config->game->tour->position_latence) {
			$info = "Fin latence &agrave; ".$this->hobbit->date_fin_latence_hobbit;
		} else if ($this->view->user->tour_position_hobbit == $this->view->config->game->tour->position_milieu) {
			$info = "Cumul &agrave; ".$this->hobbit->date_debut_cumul_hobbit;
		}
		$this->view->user->info_prochaine_position = $info;
		Bral_Util_Log::tour()->trace(get_class($this)." calculInfoTour - exit -");
	}

	private function calculDLA() {
		Bral_Util_Log::tour()->trace(get_class($this)." calculDLA - enter -");
		$this->hobbit->duree_courant_tour_hobbit = $this->hobbit->duree_prochain_tour_hobbit;
		// Ajouter la prise en compte du niveau de sagesse
		//Duree DLA (en minutes) = 1440 â€“ 10 * Niveau SAG

		Bral_Util_Log::tour()->debug(get_class($this)." this->hobbit->duree_prochain_tour_hobbit=".$this->hobbit->duree_prochain_tour_hobbit);

		Zend_Loader::loadClass("Bral_Util_Tour");
		$tabProchainTour = Bral_Util_Tour::getTabMinutesProchainTour($this->hobbit);
		$minutesCourant = $tabProchainTour["minutesBase"];

		Bral_Util_Log::tour()->debug(get_class($this)." minutesCourant=".$minutesCourant);
		// Ajouter les blessures : pour chaque PV : Arrondi inf"rieur [duree DLA (+BM) / (4*max PV du Hobbit)].

		$minutesAAjouter = 0;
		if (($this->hobbit->pv_max_hobbit + $this->hobbit->pv_max_bm_hobbit) - $this->hobbit->pv_restant_hobbit > 0) {
			$minutesAAjouter = $tabProchainTour["minutesBlessures"];
		}

		Bral_Util_Log::tour()->debug(get_class($this)." minutesAAjouter=".$minutesAAjouter);

		$this->hobbit->duree_courant_tour_hobbit = $tabProchainTour["heureMinuteTotal"];
		Bral_Util_Log::tour()->debug(get_class($this)." this->hobbit->duree_courant_tour_hobbit=".$this->hobbit->duree_courant_tour_hobbit);

		Zend_Loader::loadClass("Bral_Util_Tour");
		$this->hobbit->duree_prochain_tour_hobbit = Bral_Util_Tour::getDureeBaseProchainTour($this->hobbit, $this->view->config);
		Bral_Util_Log::tour()->debug(get_class($this)." this->hobbit->duree_prochain_tour_hobbit=".$this->hobbit->duree_prochain_tour_hobbit);

		$this->hobbit->date_debut_tour_hobbit = $this->hobbit->date_fin_tour_hobbit;
		$this->hobbit->date_fin_tour_hobbit = Bral_Util_ConvertDate::get_date_add_time_to_date($this->hobbit->date_fin_tour_hobbit, $this->hobbit->duree_courant_tour_hobbit);

		$time_latence = Bral_Util_ConvertDate::get_divise_time_to_time($this->hobbit->duree_courant_tour_hobbit, $this->view->config->game->tour->diviseur_latence);
		$time_cumul = Bral_Util_ConvertDate::get_divise_time_to_time($this->hobbit->duree_courant_tour_hobbit, $this->view->config->game->tour->diviseur_cumul);

		$this->hobbit->date_fin_latence_hobbit =  Bral_Util_ConvertDate::get_date_add_time_to_date($this->hobbit->date_debut_tour_hobbit, $time_latence);
		$this->hobbit->date_debut_cumul_hobbit =  Bral_Util_ConvertDate::get_date_add_time_to_date($this->hobbit->date_debut_tour_hobbit, $time_cumul);

		Bral_Util_Log::tour()->debug(get_class($this)." this->hobbit->date_fin_tour_hobbit=".$this->hobbit->date_fin_tour_hobbit);
		Bral_Util_Log::tour()->trace(get_class($this)." calculDLA - exit -");
	}

	private function calculPv() {
		Bral_Util_Log::tour()->trace(get_class($this)." calculPv - enter -");
		Bral_Util_Log::tour()->trace(get_class($this)." calculPv - this->hobbit->regeneration_malus_hobbit=".$this->hobbit->regeneration_malus_hobbit);

		$this->view->jetRegeneration = 0;

		Zend_Loader::loadClass("Bral_Util_Vie");
		Bral_Util_Vie::calculRegenerationHobbit(&$this->hobbit, $this->view->jetRegeneration);

		/* Remise a  zero du malus de regeneration. */
		$this->hobbit->regeneration_malus_hobbit = 0;
		Bral_Util_Log::tour()->trace(get_class($this)." calculPv - exit -");
	}

	private function updateDb() {
		Bral_Util_Log::tour()->trace(get_class($this)." updateDb - enter -");

		// Mise a jour du joueur dans la base de donnees
		$hobbitTable = new Hobbit();
		$hobbitRowset = $hobbitTable->find($this->hobbit->id_hobbit);
		$hobbit = $hobbitRowset->current();

		$this->view->user->x_hobbit = $this->hobbit->x_hobbit;
		$this->view->user->y_hobbit  = $this->hobbit->y_hobbit;

		$this->view->user->date_debut_tour_hobbit = $this->hobbit->date_debut_tour_hobbit;
		$this->view->user->date_fin_tour_hobbit = $this->hobbit->date_fin_tour_hobbit;
		$this->view->user->date_debut_cumul_hobbit = $this->hobbit->date_debut_cumul_hobbit;
		$this->view->user->date_fin_latence_hobbit = $this->hobbit->date_fin_latence_hobbit;
		$this->view->user->duree_courant_tour_hobbit = $this->hobbit->duree_courant_tour_hobbit;
		$this->view->user->duree_prochain_tour_hobbit = $this->hobbit->duree_prochain_tour_hobbit;
		$this->view->user->duree_bm_tour_hobbit = $this->hobbit->duree_bm_tour_hobbit;
		$this->view->user->tour_position_hobbit = $this->hobbit->tour_position_hobbit;
		$this->view->user->pa_hobbit = $this->hobbit->pa_hobbit;
		$this->view->user->armure_naturelle_hobbit = $this->hobbit->armure_naturelle_hobbit;
		$this->view->user->est_ko_hobbit = $this->hobbit->est_ko_hobbit;
		$this->view->user->px_commun_hobbit = $this->hobbit->px_commun_hobbit;
		$this->view->user->px_perso_hobbit = $this->hobbit->px_perso_hobbit;
		$this->view->user->pv_max_hobbit = $this->hobbit->pv_max_hobbit;
		$this->view->user->pv_restant_hobbit = $this->hobbit->pv_restant_hobbit;
		$this->view->user->pv_max_bm_hobbit = $this->hobbit->pv_max_bm_hobbit;
		$this->view->user->balance_faim_hobbit = $this->hobbit->balance_faim_hobbit;

		$this->view->user->force_bm_hobbit = $this->hobbit->force_bm_hobbit;
		$this->view->user->agilite_bm_hobbit = $this->hobbit->agilite_bm_hobbit;
		$this->view->user->vigueur_bm_hobbit = $this->hobbit->vigueur_bm_hobbit;
		$this->view->user->sagesse_bm_hobbit = $this->hobbit->sagesse_bm_hobbit;
		$this->view->user->vue_bm_hobbit = $this->hobbit->vue_bm_hobbit;
		$this->view->user->poids_transportable_hobbit = $this->hobbit->poids_transportable_hobbit;
		$this->view->user->poids_transporte_hobbit = $this->hobbit->poids_transporte_hobbit;

		$this->view->user->bm_attaque_hobbit = $this->hobbit->bm_attaque_hobbit;
		$this->view->user->bm_degat_hobbit = $this->hobbit->bm_degat_hobbit;
		$this->view->user->bm_defense_hobbit = $this->hobbit->bm_defense_hobbit;

		$this->view->user->regeneration_malus_hobbit = $this->hobbit->regeneration_malus_hobbit;

		$this->view->user->est_engage_hobbit = $this->hobbit->est_engage_hobbit;
		$this->view->user->est_engage_next_dla_hobbit = $this->hobbit->est_engage_next_dla_hobbit;

		$this->view->user->est_intangible_hobbit = $this->hobbit->est_intangible_hobbit;
		$this->view->user->nb_dla_jouees_hobbit = $this->hobbit->nb_dla_jouees_hobbit;
		
		$data = array(
			'x_hobbit' => $this->hobbit->x_hobbit,
			'y_hobbit'  => $this->hobbit->y_hobbit,
			'z_hobbit'  => $this->hobbit->z_hobbit,
			'date_debut_tour_hobbit' => $this->hobbit->date_debut_tour_hobbit,
			'date_fin_tour_hobbit' => $this->hobbit->date_fin_tour_hobbit,
			'date_fin_latence_hobbit' => $this->hobbit->date_fin_latence_hobbit,
			'date_debut_cumul_hobbit' => $this->hobbit->date_debut_cumul_hobbit,
			'duree_courant_tour_hobbit' => $this->hobbit->duree_courant_tour_hobbit,
			'duree_prochain_tour_hobbit' => $this->hobbit->duree_prochain_tour_hobbit,
			'duree_bm_tour_hobbit' => $this->hobbit->duree_bm_tour_hobbit,
			'tour_position_hobbit' => $this->hobbit->tour_position_hobbit,
			'pa_hobbit' => $this->hobbit->pa_hobbit,
			'armure_naturelle_hobbit' => $this->hobbit->armure_naturelle_hobbit,
			'armure_equipement_hobbit' => $this->hobbit->armure_equipement_hobbit,
			'armure_bm_hobbit' => $this->hobbit->armure_bm_hobbit,
			'est_ko_hobbit' => $this->hobbit->est_ko_hobbit,
			'px_commun_hobbit' => $this->hobbit->px_commun_hobbit,
			'px_perso_hobbit' => $this->hobbit->px_perso_hobbit,
			'pv_max_hobbit' => $this->hobbit->pv_max_hobbit,
			'pv_restant_hobbit' => $this->hobbit->pv_restant_hobbit,
			'pv_max_bm_hobbit' => $this->hobbit->pv_max_bm_hobbit,
			'balance_faim_hobbit' => $this->hobbit->balance_faim_hobbit,
			'force_bm_hobbit' => $this->hobbit->force_bm_hobbit,
			'force_bbdf_hobbit' => $this->hobbit->force_bbdf_hobbit,
			'agilite_bm_hobbit' => $this->hobbit->agilite_bm_hobbit,
			'agilite_bbdf_hobbit' => $this->hobbit->agilite_bbdf_hobbit,
			'vigueur_bm_hobbit' => $this->hobbit->vigueur_bm_hobbit,
			'vigueur_bbdf_hobbit' => $this->hobbit->vigueur_bbdf_hobbit,
			'sagesse_bm_hobbit' => $this->hobbit->sagesse_bm_hobbit,
			'sagesse_bbdf_hobbit' => $this->hobbit->sagesse_bbdf_hobbit,
			'vue_bm_hobbit' => $this->hobbit->vue_bm_hobbit,
			'poids_transportable_hobbit' => $this->hobbit->poids_transportable_hobbit,
			'poids_transporte_hobbit' => $this->hobbit->poids_transporte_hobbit,
			'regeneration_hobbit' => $this->hobbit->regeneration_hobbit,
			'regeneration_malus_hobbit' => $this->hobbit->regeneration_malus_hobbit,
			'bm_attaque_hobbit' => $this->hobbit->bm_attaque_hobbit,
			'bm_degat_hobbit' => $this->hobbit->bm_degat_hobbit,
			'bm_defense_hobbit' => $this->hobbit->bm_defense_hobbit,
			'bm_marcher_hobbit' => $this->hobbit->bm_marcher_hobbit,
			'est_engage_hobbit' => $this->hobbit->est_engage_hobbit,
			'est_engage_next_dla_hobbit' => $this->hobbit->est_engage_next_dla_hobbit,
			'est_intangible_hobbit' => $this->hobbit->est_intangible_hobbit,
			'nb_dla_jouees_hobbit' => $this->hobbit->nb_dla_jouees_hobbit,
		);
		$where = "id_hobbit=".$this->hobbit->id_hobbit;
		$hobbitTable->update($data, $where);
		Bral_Util_Log::tour()->debug(get_class($this)." activer() - update hobbit ".$this->hobbit->id_hobbit." en base");
		Bral_Util_Log::tour()->trace(get_class($this)." updateDb - exit -");
	}
}

