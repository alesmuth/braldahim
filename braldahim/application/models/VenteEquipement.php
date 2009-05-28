<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3. 
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: LabanEquipement.php 1077 2009-01-26 22:01:35Z yvonnickesnault $
 * $Author: yvonnickesnault $
 * $LastChangedDate: 2009-01-26 23:01:35 +0100 (Mon, 26 Jan 2009) $
 * $LastChangedRevision: 1077 $
 * $LastChangedBy: yvonnickesnault $
 */
class VenteEquipement extends Zend_Db_Table {
	protected $_name = 'vente_equipement';
	protected $_primary = array('id_vente_equipement');

	function findByIdHobbit($idVente) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('vente_equipement', '*')
		->from('recette_equipements')
		->from('type_equipement')
		->from('type_qualite')
		->from('type_emplacement')
		->from('type_piece')
		->where('id_fk_recette_vente_equipement = id_recette_equipement')
		->where('id_fk_type_recette_equipement = id_type_equipement')
		->where('id_fk_type_qualite_recette_equipement = id_type_qualite')
		->where('id_fk_type_emplacement_recette_equipement = id_type_emplacement')
		->where('id_fk_type_piece_type_equipement = id_type_piece')
		->where('id_fk_vente_equipement = ?', intval($idVente))
		->joinLeft('mot_runique','id_fk_mot_runique_vente_equipement = id_mot_runique');
		$sql = $select->__toString();
		return $db->fetchAll($sql);
	}
}
