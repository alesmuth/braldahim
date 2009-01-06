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
class Bral_Batchs_CreationMinerais extends Bral_Batchs_Batch {
	
	public function calculBatchImpl() {
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - calculBatchImpl - enter -");
		
		Zend_Loader::loadClass('CreationMinerais');
		Zend_Loader::loadClass('Filon');
		Zend_Loader::loadClass('TypeMinerai');
		Zend_Loader::loadClass('Zone');
		
		$retour = null;
		
		$retour .= $this->calculCreation();
		
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - calculBatchImpl - exit -");
		return $retour;
	}
	
	private function calculCreation() {
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - calculCreation - enter -");
		$retour = "";
		
		$zoneTable = new Zone();
		
		$creationMineraisTable = new CreationMinerais();
		$creationMinerais = $creationMineraisTable->fetchAll(null, "id_fk_type_minerai_creation_minerais");
		$nbCreationMinerais = count($creationMinerais);
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - nbCreationMinerais=" .$nbCreationMinerais);

		$typeMineraiTable = new TypeMinerai();
		$typeMinerais = $typeMineraiTable->fetchAll();
		$nbTypeMinerais = count($typeMinerais);
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - nbTypeMinerais=" .$nbTypeMinerais);

		// selection des environnements / zones concernes
		$environnementIds = $this->getEnvironnementsConcernes($creationMinerais);
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - nb environnement concernes=" .count($environnementIds));
		$zones = $zoneTable->findByIdEnvironnementList($environnementIds, false);
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - nb zones concernees=" .count($zones));
		
		$envNbZones = array();
		// pour chaque type d'environnement
		// on compte le nombre de zone concernees
		foreach($zones as $z) {
			if (array_key_exists($z["id_fk_environnement_zone"], $envNbZones)) {
				$envNbZones[$z["id_fk_environnement_zone"]] = $envNbZones[$z["id_fk_environnement_zone"]] + 1;
			} else {
				$envNbZones[$z["id_fk_environnement_zone"]] = 1;
			}
		}
		
		// Pour chaque zone et chaque type de minerai, on insert
		
		$filonTable = new Filon();
		$tmp = "";
		
		foreach($creationMinerais as $c) {
			$t = null;
			foreach($typeMinerais as $type) {
				if ($c["id_fk_type_minerai_creation_minerais"] == $type["id_type_minerai"]) {
					$t = $type;
					break;
				}
			}
			
			if ($t != null) {
				Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - traitement du minerai ".$t["id_type_minerai"]. " nbMaxMonde(".$t["nb_creation_type_minerai"].")");
				foreach($zones as $z) {
					if ($z["id_fk_environnement_zone"] == $c["id_fk_environnement_creation_minerais"]) {
						$tmp = "";
						$nbCreation = ceil($t["nb_creation_type_minerai"] / $envNbZones[$z["id_fk_environnement_zone"]]);
						$nbActuel = $filonTable->countVue($z["x_min_zone"], $z["y_min_zone"], $z["x_max_zone"], $z["y_max_zone"]);
						
						$aCreer = $nbCreation - $nbActuel;
						if ($aCreer <= 0) { 
							$tmp = " deja pleine";
						}
						Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - zone(".$z["id_zone"].") nbActuel:".$nbActuel. " max:".$nbCreation.$tmp);
						if ($aCreer > 0) { 
							$retour .= $this->insert($t["id_type_minerai"], $z, $aCreer, $filonTable);
						} else {
							$retour .= "zone(".$z["id_zone"].") pleine de minerai(".$t["id_type_minerai"].") nbActuel(".$nbActuel.") max(".$nbCreation."). ";
						}
					}
				}
			}
		}
		
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - calculCreation - exit -");
		
		return $retour;
	}
	
	private function getEnvironnementsConcernes($creationMinerais) {
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - getEnvironnementsConcernes - enter -");
		$environnementIds = null;
		foreach($creationMinerais as $n) {
			$environnementIds[$n["id_fk_environnement_creation_minerais"]] = $n["id_fk_environnement_creation_minerais"];
		}
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - getEnvironnementsConcernes - exit -");
		return $environnementIds;
	}
	
	private function insert($idTypeMinerai, $zone, $aCreer, $filonTable) {
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - insert - enter - idtype(".$idTypeMinerai.") idzone(".$zone['id_zone'].") nbACreer(".$aCreer.")");
		$retour = "minerai(".$idTypeMinerai.") idzone(".$zone['id_zone'].") aCreer(".$aCreer."). ";
		
		for($i = 1; $i <= $aCreer; $i++) {
			$x = Bral_Util_De::get_de_specifique($zone["x_min_zone"], $zone["x_max_zone"]);
			$y = Bral_Util_De::get_de_specifique($zone["y_min_zone"], $zone["y_max_zone"]);
			
			$quantite = Bral_Util_De::get_de_specifique(10, 20);
			
			$data = array(
				'id_fk_type_minerai_filon' => $idTypeMinerai, 
				'x_filon' => $x, 
				'y_filon' => $y, 
				'quantite_restante_filon' => $quantite, 
				'quantite_max_filon' => $quantite
			);
			$filonTable->insert($data);
		}
		Bral_Util_Log::batchs()->trace("Bral_Batchs_CreationMinerais - insert - exit -");
		return $retour;
	}
}