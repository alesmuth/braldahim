<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3. 
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 *
 * $Id: CoffrePotion.php 595 2008-11-09 11:21:27Z yvonnickesnault $
 * $Author: yvonnickesnault $
 * $LastChangedDate: 2008-11-09 12:21:27 +0100 (Sun, 09 Nov 2008) $
 * $LastChangedRevision: 595 $
 * $LastChangedBy: yvonnickesnault $
 */
class CoffrePotion extends Zend_Db_Table {
	protected $_name = 'laban_potion';
	protected $_primary = array('id_coffre_potion');

	function findByIdHobbit($idHobbit) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('coffre_potion', '*')
		->from('type_potion')
		->from('type_qualite')
		->where('id_fk_type_coffre_potion = id_type_potion')
		->where('id_fk_type_qualite_coffre_potion = id_type_qualite')
		->where('id_fk_hobbit_coffre_potion = ?', intval($idHobbit));
		$sql = $select->__toString();
		return $db->fetchAll($sql);
	}
	
    function countByIdHobbit($idHobbit) {
		$db = $this->getAdapter();
		$select = $db->select();
		$select->from('coffre_potion', 'count(*) as nombre')
		->where('id_fk_hobbit_coffre_potion = '.intval($idHobbit));
		$sql = $select->__toString();
		$resultat = $db->fetchAll($sql);

		$nombre = $resultat[0]["nombre"];
		return $nombre;
    }
}
