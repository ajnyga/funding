<?php

/**
 * @file plugins/generic/funding/classes/classes/FunderDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunderDAO
 * @ingroup plugins_generic_funding
 *
 * Operations for retrieving and modifying Funder objects.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.funding.classes.Funder');

class FunderDAO extends DAO {

	/**
	 * Get a funder by ID
	 * @param $funderId int Funder ID
	 * @param $submissionId int (optional) Submission ID
	 */
	function getById($funderId, $submissionId = null) {
		$params = array((int) $funderId);
		if ($submissionId) $params[] = (int) $submissionId;

		$result = $this->retrieve(
			'SELECT * FROM funder WHERE funder_id = ?'
			. ($submissionId?' AND submission_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}


	/**
	 * Get funders by submission ID.
	 * @param $submissionId int Submission ID
	 * @param $contextId int (optional) Context ID
	 * @return Funder
	 */
	function getBySubmissionId($submissionId, $contextId = null) {
		$params = array((int) $submissionId);
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM funder WHERE submission_id = ?'
			. ($contextId?' AND context_id = ?':''),
			$params
		);
		
		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Insert a funder.
	 * @param $funder Funder
	 * @return int Inserted funder ID
	 */
	function insertObject($funder) {
		$this->update(
			'INSERT INTO funder (funder_identification, submission_id, context_id) VALUES (?, ?, ?)',
			array(
				$funder->getFunderIdentification(),
				(int) $funder->getSubmissionId(),
				(int) $funder->getContextId()
			)
		);
		$funder->setId($this->getInsertId());
		$this->updateLocaleFields($funder);
		return $funder->getId();
	}

	/**
	 * Update the database with a funder object
	 * @param $funder Funder
	 */
	function updateObject($funder) {
		$this->update(
			'UPDATE	funder
			SET	context_id = ?,
				funder_identification = ?
			WHERE funder_id = ?',
			array(
				(int) $funder->getContextId(),
				$funder->getFunderIdentification(),
				(int) $funder->getId()
			)
		);
		$this->updateLocaleFields($funder);
	}

	/**
	 * Delete a funder by ID.
	 * @param $funderId int
	 */
	function deleteById($funderId) {
		$this->update(
			'DELETE FROM funder WHERE funder_id = ?',
			(int) $funderId
		);
		$this->update(
			'DELETE FROM funder_settings WHERE funder_id = ?',
			(int) $funderId
		);
		$this->update(
			'DELETE FROM funder_award WHERE funder_id = ?',
			(int) $funderId
		);
	}

	/**
	 * Delete a funder object.
	 * @param $funder Funder
	 */
	function deleteObject($funder) {
		$this->deleteById($funder->getId());
	}

	/**
	 * Generate a new funder object.
	 * @return Funder
	 */
	function newDataObject() {
		return new Funder();
	}

	/**
	 * Return a new funder object from a given row.
	 * @return Funder
	 */
	function _fromRow($row) {
		$funder = $this->newDataObject();
		$funder->setId($row['funder_id']);
		$funder->setFunderIdentification($row['funder_identification']);
		$funder->setContextId($row['context_id']);

		$this->getDataObjectSettings('funder_settings', 'funder_id', $row['funder_id'], $funder);

		return $funder;
	}

	/**
	 * Get the insert ID for the last inserted funder.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('funder', 'funder_id');
	}

	/**
	 * Get the additional field names.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		return array('funderName');
	}

	/**
	 * Update the settings for this object
	 * @param $funder object
	 */
	function updateLocaleFields($funder) {
		error_log(print_r($funder, true)); # debug: for some reason funder_settings table is not getting updated?
		$this->updateDataObjectSettings('funder_settings', $funder, array('funder_id' => (int) $funder->getId()));
	}

}

?>
