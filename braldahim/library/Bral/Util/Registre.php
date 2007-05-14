<?php

class Bral_Util_Registre {

	private function __construct(){}

	public static function chargement() {
		self::chargementCompetence();
		self::chargementNomTour();
	}
	
	private static function chargementCompetence() {
		$competenceTable = new Competence();
		$competences = $competenceTable->fetchall();
		$tab = null;
		$tab2 = null;
		$tabBasiques = null;
		$tabBasiquesId = null;
		foreach ($competences as $c) {
			$tab[$c->id]["nom"] = $c->nom_competence;
			$tab[$c->id]["nom_systeme"] = $c->nom_systeme_competence;
			$tab[$c->id]["description"] = $c->description_competence;
			$tab[$c->id]["niveau_requis"] = $c->niveau_requis_competence;
			$tab[$c->id]["pi_cout"] = $c->pi_cout_competence;
			$tab[$c->id]["px_gain"] = $c->px_gain_competence;
			$tab[$c->id]["pourcentage_max"] = $c->pourcentage_max_competence;
			$tab[$c->id]["pa_utilisation"] = $c->pa_utilisation_competence;
			$tab[$c->id]["type"] = $c->type_competence;
			
			$tab2[$c->nom_systeme_competence]["id"] = $c->id;
			
			if ($c->type_competence == 'basic') {
				$tabBasiques[] = array 
				( "id" => $c->id,
					"nom" => $c->nom_competence,
					"nom_systeme" => $c->nom_systeme_competence,
					"description" => $c->description_competence
				);
				$tabBasiquesId[$c->nom_systeme_competence]["id"] = $c->id;
			}
		}
		Zend_Registry::set('competences', $tab);
		Zend_Registry::set('competencesId', $tab2);
		Zend_Registry::set('competencesBasiques', $tabBasiques);
	}
	
	private static function chargementNomTour() {
		$tab[1] = "Latence";
		$tab[2] = "Milieu";
		$tab[3] = "Cumul";
		Zend_Registry::set('nomsTour', $tab);
	}
}
