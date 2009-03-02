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
class SouleMatch extends Zend_Db_Table {
	protected $_name = 'soule_match';
	protected $_primary = 'id_soule_match';

	public function findEnCoursByIdTerrain($idTerrain) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('soule_match', '*');
		$select->where('id_fk_terrain_soule_match = ?', (int)$idTerrain);
		$select->where('date_debut_soule_match is not null and date_fin_soule_match is null');
		$sql = $select->__toString();
		$result = $db->fetchAll($sql);
		return $result;
	}
	
	public function findNonDebuteByIdTerrain($idTerrain) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('soule_match', '*');
		$select->where('id_fk_terrain_soule_match = ?', (int)$idTerrain);
		$select->where('date_debut_soule_match is null and date_fin_soule_match is null');
		$sql = $select->__toString();
		$result = $db->fetchAll($sql);
		return $result;
	}
	
	public function findNonDebutes() {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('soule_match', '*');
		$select->from('soule_terrain', '*');
		$select->from('zone', '*');
		$select->where('id_fk_terrain_soule_match = id_soule_terrain');
		$select->where('id_fk_zone_soule_terrain = id_zone');
		$select->where('date_debut_soule_match is null and date_fin_soule_match is null');
		$sql = $select->__toString();
		$result = $db->fetchAll($sql);
		return $result;
	}
}