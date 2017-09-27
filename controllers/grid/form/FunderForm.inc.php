<?php

/**
 * @file plugins/generic/funding/controllers/grid/form/FunderForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunderForm
 * @ingroup controllers_grid_funding
 *
 * Form for adding/editing a funder
 *
 */

import('lib.pkp.classes.form.Form');

class FunderForm extends Form {
	/** @var int Context ID */
	var $contextId;

	/** @var int Submission ID */
	var $submissionId;

	/** @var FundingPlugin */
	var $plugin;

	/**
	 * Constructor
	 * @param $fundingPlugin FundingPlugin
	 * @param $contextId int Context ID
	 * @param $submissionId int Submission ID
	 * @param $funderId int (optional) Funder ID
	 */
	function __construct($fundingPlugin, $contextId, $submissionId, $funderId = null) {
		parent::__construct($fundingPlugin->getTemplatePath() . 'editFunderForm.tpl');

		$this->contextId = $contextId;
		$this->submissionId = $submissionId;
		$this->funderId = $funderId;
		$this->plugin = $fundingPlugin;

		// Add form checks
		$this->addCheck(new FormValidator($this, 'funderNameIdentification', 'required', 'plugins.generic.funding.funderNameIdentificationRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData() {
		$this->setData('submissionId', $this->submissionId);
		if ($this->funderId) {
			$funderDao = DAORegistry::getDAO('FunderDAO');
			$funder = $funderDao->getById($this->funderId);
			$this->setData('funderNameIdentification', $funder->getFunderNameIdentification());
			$this->setData('funderGrants', $funder->getFunderGrants());
		}
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('funderNameIdentification', 'funderGrants', 'subsidiaryOption'));
	}

	/**
	 * @copydoc Form::fetch
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('funderId', $this->funderId);
		$subsidiaryOptions = array('' => __('plugins.generic.funding.funderSubOrganization.select'));
		$templateMgr->assign('subsidiaryOptions', $subsidiaryOptions);
		return parent::fetch($request);
	}

	/**
	 * Save form values into the database
	 */
	function execute() {
		$funderDao = DAORegistry::getDAO('FunderDAO');

		if ($this->funderId) {
			// Load and update an existing funder
			$funder = $funderDao->getById($this->funderId, $this->submissionId);
		} else {
			// Create a new
			$funder = $funderDao->newDataObject();
			$funder->setContextId($this->contextId);
			$funder->setSubmissionId($this->submissionId);
		}

		$funderName = '';
		$funderIdentification = '';
		$funderNameIdentification = $this->getData('funderNameIdentification');
		$subOrganizationNameIdentification = $this->getData('subsidiaryOption');
		if ($funderNameIdentification != ''){
			$funderName = trim(preg_replace('/\s*\[.*?\]\s*/ ', '', $funderNameIdentification));
			if (preg_match('/\[(.*?)\]/', $funderNameIdentification, $output)) {
				$funderIdentification = $output[1];
				if ($subOrganizationNameIdentification != ''){
					$funderName = trim(preg_replace('/\s*\[.*?\]\s*/ ', '', $subOrganizationNameIdentification	));
					$doiPrefix = '';
					if (preg_match('/(http:\/\/dx\.doi\.org\/10\.\d{5}\/)(.+)/', $funderIdentification, $output)) {
						$doiPrefix = $output[1];
					}
					if (preg_match('/\[(.*?)\]/', $subOrganizationNameIdentification, $output)) {
						$funderIdentification = $doiPrefix . $output[1];
					}
				}
			}
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
