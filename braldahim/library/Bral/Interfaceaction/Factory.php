<?php

/**
 * This file is part of Braldahim, under Gnu Public Licence v3.
 * See licence.txt or http://www.gnu.org/licenses/gpl-3.0.html
 * Copyright: see http://www.braldahim.com/sources
 */
class Bral_Interfaceaction_Factory
{

	static function getAction($request, $view)
	{

		Zend_Loader::loadClass("Bral_Interfaceaction_Interfaceaction");

		$matches = null;
		preg_match('/(.*)_interfaceaction_(.*)/', $request->get("caction"), $matches);
		$action = $matches[1]; // "do" ou "ask"
		$nomSystemeAction = $matches[2];
		$construct = null;

		$construct = "Bral_Interfaceaction_" . Bral_Util_String::firstToUpper($nomSystemeAction);
		try {
			Zend_Loader::loadClass($construct);
		} catch (Exception $e) {
			throw new Zend_Exception("Bral_Adminstrationsajax_Factory construct invalide (classe): " . $nomSystemeAction);
		}

		// verification que la classe de l'action existe.
		if (($construct != null) && (class_exists($construct))) {
			return new $construct ($nomSystemeAction, $request, $view, $action);
		} else {
			throw new Zend_Exception("Bral_Adminstrationsajax_Factory action invalide: " . $nomSystemeAction);
		}
	}
}