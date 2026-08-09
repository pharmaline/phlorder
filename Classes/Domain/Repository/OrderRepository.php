<?php
namespace Pharmaline\Phlorder\Domain\Repository;

/***
 *
 * This file is part of the "phlorder Bestellung" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Christian Platt <christian.platt@pharmaline.de>, pharmaline
 *
 ***/

/**
 * The repository for Orders
 */
class OrderRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
	/** get an entry by source token
	*@param string $token
	*@return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	*/
	function getOrderByToken($token){
		$query = $this->createQuery();
		$query->matching(
			$query->equals('orderid', $token)
		);

		// $GLOBALS['TYPO3_DB']->debugOutput = true; entfernt - TYPO3_DB gibt es seit v9 nicht mehr.
		return $query->execute();
	}


	/** alle Bestellungen eines Phlusers
	*
	* ACHTUNG: Der einzige Aufrufer (eID-Worker, getAndSetOrdernumber4Order) bildet die
	* naechste Bestellnummer aus $result->count(). Das ist falsch, sobald je eine
	* Bestellung geloescht wurde oder zwei Bestellungen gleichzeitig eingehen -> doppelte
	* Bestellnummern. Sauber waere ein MAX(ordernumber) bzw. eine echte Sequenz.
	* Verhalten hier bewusst unveraendert gelassen, die Korrektur gehoert zum Umbau des
	* Workers in Phase 7 (siehe CLAUDE.md #13).
	*
	*@param int $phluserfid
	*@return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	*/
	function getOrdernumberlatest($phluserfid){
		$query = $this->createQuery();
		// logicalAnd() ist seit v11 variadisch und bekam hier ein Array; bei nur einer
		// Bedingung braucht es den Wrapper ohnehin nicht.
		$query->matching(
			$query->equals('phluserfid', $phluserfid)
		);

		return $query->execute();
	}
}
