<?php

/**
 * @file plugins/generic/fundRef/classes/Funder.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Funder
 * @ingroup plugins_generic_fundRef
 *
 * Data object representing a funder.
 */

class Funder extends DataObject {

	//
	// Get/set methods
	//
	/**
	 * Get ID of journal.
	 * @return int
	 */
	function getContextId(){
		return $this->getData('contextId');
	}

	/**
	 * Set ID of journal.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setData('contextId', $contextId);
	}

	/**
	 * Get ID of article.
	 * @return int
	 */
	function getSubmissionId(){
		return $this->getData('submissionId');
	}

	/**
	 * Set ID of article.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}

	/**
	 * Get name and identification.
	 * @return string
	 */
	function getFunderNameIdentification() {
		if ($this->getData('funderIdentification')){
			return $this->getData('funderName') .' [' .$this->getData('funderIdentification') .']';
		}
		else{
			return $this->getData('funderName');
		}
	}

	/**
	 * Get name.
	 * @return string
	 */
	function getFunderName() {
		return $this->getData('funderName');
	}

	/**
	 * Set name.
	 * @param $funderName string
	 */
	function setFunderName($funderName) {
		return $this->setData('funderName', $funderName);
	}

	/**
	 * Get identification.
	 * @return string
	 */
	function getFunderIdentification() {
		return $this->getData('funderIdentification');
	}

	/**
	 * Set identification.
	 * @param $funderIdentification string
	 */
	function setFunderIdentification($funderIdentification) {
		return $this->setData('funderIdentification', $funderIdentification);
	}

	/**
	 * Get semicolon separated grant numbers.
	 * @return string
	 */
	function getFunderGrants() {
		return $this->getData('funderGrants');
	}

	/**
	 * Set semicolon separated grant numbers.
	 * @param $funderGrants string
	 */
	function setFunderGrants($funderGrants) {
		return $this->setData('funderGrants', $funderGrants);
	}
}

?>
