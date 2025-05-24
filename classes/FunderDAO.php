<?php

/**
 * @file plugins/generic/funding/classes/classes/FunderDAO.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FunderDAO
 * @ingroup plugins_generic_funding
 *
 * Operations for retrieving and modifying Funder objects.
 */

namespace APP\plugins\generic\funding\classes;

use PKP\db\DAO;
use PKP\db\DAOResultFactory;
use PKP\db\DAORegistry;

class FunderDAO extends DAO {

	/**
	 * Get a funder by ID
	 * @param $funderId int Funder ID
	 * @param $submissionId int (optional) Submission ID
	 */
	function getById($funderId, $submissionId = null) {
		$params = [(int) $funderId];
		if ($submissionId) $params[] = (int) $submissionId;

		$result = $this->retrieve(
			'SELECT * FROM funders WHERE funder_id = ?'
			. ($submissionId?' AND submission_id = ?':''),
			$params
		);

		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}


	/**
	 * Get funders by submission ID.
	 * @param $submissionId int Submission ID
	 * @param $contextId int (optional) Context ID
	 * @return Funder
	 */
	function getBySubmissionId($submissionId, $contextId = null) {
		$params = [(int) $submissionId];
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM funders WHERE submission_id = ?'
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
			'INSERT INTO funders (funder_identification, submission_id, context_id) VALUES (?, ?, ?)',
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
			'UPDATE	funders
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
			'DELETE FROM funders WHERE funder_id = ?',
			[(int) $funderId]
		);

		$this->update(
			'DELETE FROM funder_settings WHERE funder_id = ?',
			[(int) $funderId]
		);

		$funderAwardDAO = DAORegistry::getDAO('FunderAwardDAO');
		$funderAwardDAO->deleteByFunderId($funderId);

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
	 * Get the additional field names.
	 * @return array
	 */
	public function getAdditionalFieldNames(): array {
		return ['funderName'];
	}

	/**
	 * Update the settings for this object
	 * @param $funder object
	 */
	function updateLocaleFields($funder) {
		$this->updateDataObjectSettings('funder_settings', $funder, array('funder_id' => (int) $funder->getId()));
	}

}
