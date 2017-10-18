<?php

/**
 * @file plugins/generic/funding/classes/FunderAwardDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunderAwardDAO
 * @ingroup plugins_generic_funding
 *
 * Operations for retrieving and modifying FunderAward objects.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.funding.classes.FunderAward');

class FunderAwardDAO extends DAO {

	/**
	 * Get a funderAward by Funder ID
	 * @param $funderId int Funder ID
	 * @return Array
	 */
	function getFunderAwardNumbersByFunderId($funderId) {
		$result = $this->retrieve(
			'SELECT * FROM funder_award WHERE funder_id = ?',
			(int) $funderId
		);

		$awards = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$awards[$row['funder_award_id']] = $row['funder_award_number'];
			$result->MoveNext();
		}
		$result->Close();
		return $awards;

	}
	
	/**
	 * Delete funderAwards by Funder ID
	 * @param $funderId int Funder ID
	 * @return Array
	 */
	function deleteByFunderId($funderId) {		
		$this->update(
			'DELETE FROM funder_award WHERE funder_id = ?',
			(int) $funderId
		);
	}

	/**
	 * Insert a funder award.
	 * @param $funderAward FunderAward
	 * @return int Inserted funder award ID
	 */
	function insertObject($funderAward) {
		$this->update(
			'INSERT INTO funder_award (funder_id, funder_award_number) VALUES (?, ?)',
			array(
				$funderAward->getFunderId(),
				$funderAward->getFunderAwardNumber()
			)
		);
		$funderAward->setId($this->getInsertId());
		return $funderAward->getId();
	}

	/**
	 * Delete a funder award by ID.
	 * @param $funderAwardId int
	 */
	function deleteById($funderAwardId) {
		$this->update(
			'DELETE FROM funder_award WHERE funder_award_id = ?',
			(int) $funderAwardId
		);
	}

	/**
	 * Delete a funder award object.
	 * @param $funder Funder
	 */
	function deleteObject($funderAward) {
		$this->deleteById($funderAward->getId());
	}

	/**
	 * Generate a new funder award object.
	 * @return Funder
	 */
	function newDataObject() {
		return new FunderAward();
	}

	/**
	 * Return a new funder award object from a given row.
	 * @return Funder
	 */
	function _fromRow($row) {
		$funderAward = $this->newDataObject();
		$funderAward->setId($row['funder_award_id']);
		$funderAward->setFunderId($row['funder_id']);
		$funderAward->setFunderAwardNumber($row['funder_award_number']);

		return $funderAward;
	}

	/**
	 * Get the insert ID for the last inserted funder.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('funder_award', 'funder_award_id');
	}

	/**
	 * Get the additional field names.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		return array();
	}

	/**
	 * Update the settings for this object
	 * @param $funder object
	 */
	function updateLocaleFields($funderAward) {
		$this->updateDataObjectSettings('funder_award_settings', $funderAward, array('funder_award_id' => (int) $funderAward->getId()));
	}	

}

?>
