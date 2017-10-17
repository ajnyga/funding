<?php

/**
 * @file plugins/generic/funding/classes/Funder.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Funder
 * @ingroup plugins_generic_funding
 *
 * Data object representing a funder.
 */

class Funder extends DataObject {

	//
	// Get/set methods
	//

	/**
	 * Get context ID.
	 * @return int
	 */
	function getContextId(){
		return $this->getData('contextId');
	}

	/**
	 * Set context ID.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setData('contextId', $contextId);
	}

	/**
	 * Get submission ID.
	 * @return int
	 */
	function getSubmissionId(){
		return $this->getData('submissionId');
	}

	/**
	 * Set submission ID.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
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
	 * Get name.
	 * @param $locale string	 
	 * @return string
	 */
	function getFunderName($locale) {
		return $this->getData('funderName', $locale);
	}	

	/**
	 * Get localized name.
	 * @return string
	 */
	function getLocalizedFunderName() {
		return $this->getLocalizedData('funderName');
	}

	/**
	 * Set name.
	 * @param $funderName string
	 * @param $locale string
	 */
	function setFunderName($funderName, $locale) {
		return $this->setData('funderName', $funderName, $locale);
	}

	/**
	 * Get name and identification.
	 * @return string
	 */
	function getFunderNameIdentification() {
		if ($this->getData('funderIdentification')){
			return $this->getLocalizedData('funderName') .' [' .$this->getData('funderIdentification') .']';
		}
		else{
			return $this->getLocalizedData('funderName');
		}
	}

}

?>
