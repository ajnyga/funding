<?php

/**
 * @file controllers/grid/form/FunderForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunderForm
 * @ingroup controllers_grid_fundRef
 *
 * Form for 
 *
 */

import('lib.pkp.classes.form.Form');

class FunderForm extends Form {
	/** @var int Context (press / journal) ID */
	var $contextId;
	var $submissionId;
	
	/** @var FundRefPlugin FundRef plugin */
	var $plugin;
	
	/**
	 * Constructor
	 * @param $fundRefPlugin FundRefPlugin The fundRef plugin
	 * @param $contextId int Context ID
	 * @param $funderId int Funder ID (if any)
	 */
	function __construct($fundRefPlugin, $contextId, $submissionId, $funderId = null) {
		parent::__construct($fundRefPlugin->getTemplatePath() . 'editFunderForm.tpl');

		$this->contextId = $contextId;
		$this->submissionId = $submissionId;
		$this->funderId = $funderId;
		$this->plugin = $fundRefPlugin;
		
		// Add form checks
		$this->addCheck(new FormValidator($this, 'funderNameIdentification', 'required', 'plugins.generic.fundRef.funderNameIdentificationRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

	}	

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$templateMgr = TemplateManager::getManager();
		
		$this->setData('submissionId', $this->submissionId);
		
		if ($this->funderId) {
			$funderDao = DAORegistry::getDAO('FunderDAO');
			$funder = $funderDao->getById($this->funderId);
			$this->setData('funderNameIdentification', $funder->getFunderNameIdentification());
			$this->setData('funderGrants', $funder->getFunderGrants());
		}

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('funderNameIdentification', 'funderGrants'));
	}

	/**
	 * @see Form::fetch
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('funderId', $this->funderId);
		$templateMgr->assign('pluginJavaScriptURL', $this->plugin->getJavaScriptURL($request));
		return parent::fetch($request);
	}

	/**
	 * Save form values into the database
	 */
	function execute() {
		$funderDao = DAORegistry::getDAO('FunderDAO');
		
		if ($this->funderId) {
			// Load and update an existing
			$funder = $funderDao->getById($this->funderId, $this->submissionId);
		} else {
			// Create a new 
			$funder = $funderDao->newDataObject();
			$funder->setContextId($this->contextId);
			$funder->setSubmissionId($this->submissionId);
		}
		
		$funderName = "";
		$funderIdentification = "";
		$funderNameIdentification = $this->getData('funderNameIdentification');
		
		if ($funderNameIdentification != ""){
			$funderName = trim(preg_replace('/\s*\[.*?\]\s*/ ', '', $funderNameIdentification));
			if (preg_match("/\[(.*?)\]/", $funderNameIdentification, $output))
				$funderIdentification = $output[1];
		}
				
		$funder->setFunderName($funderName);
		$funder->setFunderIdentification($funderIdentification);
		$funder->setFunderGrants($this->getData('funderGrants'));
		
		if ($this->funderId) {
			$funderDao->updateObject($funder);
		} else {
			$funderDao->insertObject($funder);
		}
				
	}
}

?>
