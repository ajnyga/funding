<?php

/**
 * @file classes/Funder.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.fundRef
 * @class Funder
 * Data object representing a static page.
 */

class Funder extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//
	
	function getContextId(){
		return $this->getData('contextId');
	}
	
	function setContextId($contextId) {
		return $this->setData('contextId', $contextId);
	}

	function getSubmissionId(){
		return $this->getData('submissionId');
	}

	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}
	
	function getFunderNameIdentification() {
		
		if ($this->getData('funderIdentification')){
			return $this->getData('funderName') . " [".$this->getData('funderIdentification')."]";
		}
		else{
			return $this->getData('funderName');
		}
	}
	
	function getFunderName() {
		return $this->getData('funderName');
	}

	function setFunderName($funderName) {
		return $this->setData('funderName', $funderName);
	}	
	
	function getFunderIdentification() {
		return $this->getData('funderIdentification');
	}

	function setFunderIdentification($funderIdentification) {
		return $this->setData('funderIdentification', $funderIdentification);
	}	
	
	
	function getFunderGrants() {
		return $this->getData('funderGrants');
	}

	function setFunderGrants($funderGrants) {
		return $this->setData('funderGrants', $funderGrants);
	}
	
	
}

?>
