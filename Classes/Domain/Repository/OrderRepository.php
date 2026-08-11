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

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * The repository for Orders
 */
class OrderRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
	/** Erlaubte Sortierfelder der Cockpit-Liste (FlexForm settings.sortField).
	* Schluessel = Wert aus der FlexForm, Wert = Property des Models.
	*
	* Whitelist, weil der Wert aus einer Datenstruktur kommt, die ein Redakteur
	* nicht, ein Integrator aber sehr wohl aendern kann - ungeprueft landete er in
	* setOrderings() und von dort in der ORDER-BY-Klausel.
	*/
	public const SORT_FIELDS = [
		'timestamp'   => 'timestamp',
		'ordernumber' => 'ordernumber',
		'status'      => 'status',
		'lastName'    => 'lastName',
	];

	public const SORT_FIELD_DEFAULT = 'timestamp';


	/** Orderings fuer die Cockpit-Liste aus den FlexForm-Werten bilden.
	*
	* Unbekanntes Feld -> Default (timestamp). Alles ausser "asc" -> absteigend,
	* weil die Liste im Regelfall die juengste Bestellung oben zeigen soll.
	*
	*@param string $field Wert des FlexForm-Feldes settings.sortField
	*@param string $direction Wert des FlexForm-Feldes settings.sortDirection
	*@return array<string,string> fuer QueryInterface::setOrderings()
	*/
	public function buildOrderings(string $field, string $direction): array
	{
		$property = self::SORT_FIELDS[$field] ?? self::SORT_FIELDS[self::SORT_FIELD_DEFAULT];
		$order = strtolower(trim($direction)) === 'asc'
			? QueryInterface::ORDER_ASCENDING
			: QueryInterface::ORDER_DESCENDING;

		return [$property => $order];
	}


	/** alle Bestellungen einer Apotheke - Datenquelle der Cockpit-Liste.
	*
	* Die Einschraenkung auf $phluserfid ist die Zugriffspruefung: der Controller
	* setzt hier ausschliesslich die uid des ueber den FE-Login aufgeloesten
	* Phlusers ein, nie einen Wert aus dem Request.
	*
	*@param int $phluserfid uid des Phlusers (Apotheke)
	*@param array<string,string> $orderings siehe buildOrderings()
	*@param int $limit 0 = ohne Begrenzung
	*@return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	*/
	public function getOrdersByPhluser($phluserfid, array $orderings = [], $limit = 0)
	{
		$query = $this->createQuery();
		$query->matching(
			$query->equals('phluserfid', (int)$phluserfid)
		);

		if ($orderings !== []) {
			$query->setOrderings($orderings);
		}
		// setLimit() wirft bei < 1 eine InvalidArgumentException (gleiche Falle wie
		// in phlusereditor), deshalb nur setzen, wenn wirklich begrenzt werden soll.
		if ((int)$limit > 0) {
			$query->setLimit((int)$limit);
		}

		return $query->execute();
	}


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
