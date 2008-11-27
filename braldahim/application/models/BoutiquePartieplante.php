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
class BoutiquePartieplante extends Zend_Db_Table {
	protected $_name = 'boutique_partieplante';
	protected $_primary = array('id_boutique_partieplante');
	
    function findByIdLieu($id_lieu) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('boutique_partieplante', '*')
		->from('type_partieplante', '*')
		->from('type_plante', '*')
		->where('id_fk_lieu_boutique_partieplante = '.intval($id_lieu))
		->where('boutique_partieplante.id_fk_type_boutique_partieplante = type_partieplante.id_type_partieplante')
		->where('boutique_partieplante.id_fk_type_plante_boutique_partieplante = type_plante.id_type_plante')
		->order(array('nom_type_plante', 'nom_type_partieplante'));
		$sql = $select->__toString();

		return $db->fetchAll($sql);
    }
    
	function insertOrUpdate($data) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('boutique_partieplante', 'count(*) as nombre, quantite_boutique_partieplante as quantiteBrute')
		->where('id_fk_type_boutique_partieplante = ?',$data["id_fk_type_boutique_partieplante"])
		->where('id_fk_lieu_boutique_partieplante = ?',$data["id_fk_lieu_boutique_partieplante"])
		->where('id_fk_type_plante_boutique_partieplante = ?',$data["id_fk_type_plante_boutique_partieplante"])
		->group(array('quantiteBrute', 'quantitePreparee'));
		$sql = $select->__toString();
		$resultat = $db->fetchAll($sql);

		if (count($resultat) == 0) { // insert
			$this->insert($data);
		} else { // update
			$nombre = $resultat[0]["nombre"];
			$quantiteBrute = $resultat[0]["quantiteBrute"];
			$dataUpdate['quantite_boutique_partieplante']  = $quantiteBrute;
			
			if (isset($data["quantite_boutique_partieplante"])) {
				$quantiteBrute += $data["quantite_boutique_partieplante"];
			};
			
			$dataUpdate = array(
					'quantite_boutique_partieplante' => $quantiteBrute,
			);
			
			$where = ' id_fk_type_boutique_partieplante = '.$data["id_fk_type_boutique_partieplante"];
			$where .= ' AND id_fk_lieu_boutique_partieplante = '.$data["id_fk_lieu_boutique_partieplante"];
			$where .= ' AND id_fk_type_plante_boutique_partieplante = '.$data["id_fk_type_plante_boutique_partieplante"];
			$this->update($dataUpdate, $where);
		}
	}
	
	function countVenteByDateAndRegion($dateDebut, $dateFin, $idRegion, $idTypePartiePlante, $idTypePlante) {
		return $this->countByDateAndRegion($dateDebut, $dateFin, $idRegion, $idTypePartiePlante, $idTypePlante, "vente");
	}
	
	function countAchatByDateAndRegion($dateDebut, $dateFin, $idRegion, $idTypePartiePlante, $idTypePlante) {
		return $this->countByDateAndRegion($dateDebut, $dateFin, $idRegion, $idTypePartiePlante, $idTypePlante, "achat");
	}
	
	private function countByDateAndRegion($dateDebut, $dateFin, $idRegion, $idTypePartiePlante, $idTypePlante, $type) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('boutique_partieplante', 'count(*) as nombre')
		->where('id_fk_region_boutique_partieplante = ?', $idRegion)
		->where('date_achat_boutique_partieplante >= ?', $dateDebut)
		->where('date_achat_boutique_partieplante <= ?', $dateFin)
		->where('action_hobbit_boutique_partieplante = ?', $type)
		->where('id_fk_type_boutique_partieplante = ?', $idTypePartiePlante)
		->where('id_fk_type_plante_boutique_partieplante = ?', $idTypePlante);
		$sql = $select->__toString();
		$resultat =  $db->fetchAll($sql);
		return $resultat[0]["nombre"];
	}
}
