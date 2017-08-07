<?php

/**
 * @file plugins/generic/fundRef/classes/classes/FunderDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunderDAO
 * @ingroup plugins_generic_fundRef
 *
 * Operations for retrieving and modifying Funder objects.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.fundRef.classes.Funder');

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
			'SELECT * FROM funders WHERE funder_id = ?'
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
			'INSERT INTO funders (funder_name, funder_identification, funder_grants, submission_id, context_id) VALUES (?, ?, ?, ?, ?)',
			array(
				$funder->getFunderName(),
				$funder->getFunderIdentification(),
				$funder->getFunderGrants(),
				(int) $funder->getSubmissionId(),
				(int) $funder->getContextId()
			)
		);
		$funder->setId($this->getInsertId());
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
				funder_name = ?,
				funder_identification = ?,
				funder_grants = ?
			WHERE funder_id = ?',
			array(
				(int) $funder->getContextId(),
				$funder->getFunderName(),
				$funder->getFunderIdentification(),
				$funder->getFunderGrants(),
				(int) $funder->getId()
			)
		);
	}

	/**
	 * Delete a funder by ID.
	 * @param $funderId int
	 */
	function deleteById($funderId) {
		$this->update(
			'DELETE FROM funders WHERE funder_id = ?',
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
		$funder->setFunderName($row['funder_name']);
		$funder->setFunderIdentification($row['funder_identification']);
		$funder->setFunderGrants($row['funder_grants']);
		$funder->setContextId($row['context_id']);
		return $funder;
	}

	/**
	 * Get the insert ID for the last inserted funder.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('funders', 'funder_id');
	}

}

?>
