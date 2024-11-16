<?php

/**
 * @file plugins/generic/funding/controllers/grid/FunderGridHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FunderGridHandler
 * @ingroup plugins_generic_funding
 *
 * @brief Handle Funder grid requests.
 */

use APP\core\Application;
use APP\facades\Repo;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;

import('plugins.generic.funding.controllers.grid.FunderGridRow');
import('plugins.generic.funding.controllers.grid.FunderGridCellProvider');

class FunderGridHandler extends GridHandler {
	static $plugin;

	/** @var boolean */
	var $_readOnly;

	public FunderDAO $funderDao;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT, Role::ROLE_ID_AUTHOR),
			array('fetchGrid', 'fetchRow', 'addFunder', 'editFunder', 'updateFunder', 'deleteFunder')
		);
		$this->funderDao = DAORegistry::getDAO('FunderDAO');
	}

	//
	// Getters/Setters
	//
	/**
	 * Set the Funder plugin.
	 * @param $plugin FundingPlugin
	 */
	static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}

	/**
	 * Get the submission associated with this grid.
	 * @return Submission
	 */
	function getSubmission() {
		return $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
	}

	/**
	 * Get whether or not this grid should be 'read only'
	 * @return boolean
	 */
	function getReadOnly() {
		return $this->_readOnly;
	}

	/**
	 * Set the boolean for 'read only' status
	 * @param boolean
	 */
	function setReadOnly($readOnly) {
		$this->_readOnly = $readOnly;
	}


	//
	// Overridden template methods
	//

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc Gridhandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		$submission = $this->getSubmission();
		$submissionId = $submission->getId();

		// Set the grid details.
		$this->setTitle('plugins.generic.funding.fundingData');
		$this->setEmptyRowText('plugins.generic.funding.noneCreated');

		// Get the items and add the data to the grid
		$funderDao = DAORegistry::getDAO('FunderDAO');
		$funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');
		$funderIterator = $funderDao->getBySubmissionId($submissionId);

		$gridData = array();
		while ($funder = $funderIterator->next()) {
			$funderId = $funder->getId();
			$funderAwards = $funderAwardDao->getFunderAwardNumbersByFunderId($funderId);
			$gridData[$funderId] = array(
				'funderName' => $funder->getFunderName(),
				'funderIdentification' => $funder->getFunderIdentification(),
				'funderGrants' => implode(";", $funderAwards)
			);
		}

		$this->setGridDataElements($gridData);

		if ($this->canAdminister($request->getUser())) {
			$this->setReadOnly(false);
			// Add grid-level actions
			$router = $request->getRouter();
			$this->addAction(
				new LinkAction(
					'addFunder',
					new AjaxModal(
						$router->url($request, null, null, 'addFunder', null, array('submissionId' => $submissionId)),
						__('plugins.generic.funding.addFunder'),
						'modal_add_item'
					),
					__('plugins.generic.funding.addFunder'),
					'add_item'
				)
			);
		} else {
			$this->setReadOnly(true);
		}

		// Columns
		$cellProvider = new FunderGridCellProvider();
		$this->addColumn(new GridColumn(
			'funderName',
			'plugins.generic.funding.funderName',
			null,
			'controllers/grid/gridCell.tpl',
			$cellProvider
		));
		$this->addColumn(new GridColumn(
			'funderIdentification',
			'plugins.generic.funding.funderIdentification',
			null,
			'controllers/grid/gridCell.tpl',
			$cellProvider
		));
		$this->addColumn(new GridColumn(
			'funderGrants',
			'plugins.generic.funding.funderGrants',
			null,
			'controllers/grid/gridCell.tpl',
			$cellProvider
		));

	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc Gridhandler::getRowInstance()
	 */
	function getRowInstance() {
		return new FunderGridRow($this->getReadOnly());
	}

	/**
	 * @copydoc GridHandler::getJSHandler()
	 */
	public function getJSHandler() {
		return '$.pkp.plugins.generic.funding.FunderGridHandler';
	}

	//
	// Public Grid Actions
	//
	/**
	 * An action to add a new funder item
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest
	 */
	function addFunder($args, $request) {
		// Calling editFunderitem with an empty ID will add
		// a new Funder item.
		return $this->editFunder($args, $request);
	}

	/**
	 * An action to edit a funder
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editFunder($args, $request) {
		$funderId = $request->getUserVar('funderId');
		$context = $request->getContext();
		$submission = $this->getSubmission();
		$submissionId = $submission->getId();

		$this->setupTemplate($request);

		// Create and present the edit form
		import('plugins.generic.funding.controllers.grid.form.FunderForm');
		$funderForm = new FunderForm(self::$plugin, $context->getId(), $submissionId, $funderId);
		$funderForm->initData();
		$json = new JSONMessage(true, $funderForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Update a funder
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateFunder($args, $request) {
		$funderId = $request->getUserVar('funderId');
		$context = $request->getContext();
		$submission = $this->getSubmission();
		$submissionId = $submission->getId();

		$this->setupTemplate($request);

		// Create and populate the form
		import('plugins.generic.funding.controllers.grid.form.FunderForm');
		$funderForm = new FunderForm(self::$plugin, $context->getId(), $submissionId, $funderId);
		$funderForm->readInputData();
		// Validate
		if ($funderForm->validate()) {
			// Save
			$newFunderId = $funderForm->execute();
			$funder = $this->funderDao->getById($newFunderId, $submissionId);
 			$json = DAO::getDataChangedEvent($submissionId);
			if (!$funderId) {
				$json->setGlobalEvent('plugin:funding:added', self::$plugin->getFunderData($funder));
			} else {
				$json->setGlobalEvent('plugin:funding:edited', self::$plugin->getFunderData($funder));
			}
			return $json;
		} else {
			// Present any errors
			$json = new JSONMessage(true, $funderForm->fetch($request));
			return $json->getString();
		}
	}

	/**
	 * Delete a funder
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function deleteFunder($args, $request) {

		if (!$request->checkCSRF()) return new JSONMessage(false);

		$funderId = $request->getUserVar('funderId');
		$submission = $this->getSubmission();
		$submissionId = $submission->getId();

		$funderDao = DAORegistry::getDAO('FunderDAO');
		$funder = $funderDao->getById($funderId, $submissionId);

		$funderDao->deleteObject($funder);

		$json = DAO::getDataChangedEvent($submissionId);
		$json->setGlobalEvent('plugin:funding:deleted', self::$plugin->getFunderData($funder));

		return $json;
	}

	/**
	 * Determines if there should be add/edit actions on this grid.
	 * @param $user User
	 * @return boolean
	 */
	function canAdminister($user) {
		$submission = $this->getSubmission();
		$contextId = $submission->getContextId();

		// Incomplete submissions can be edited. (Presumably author.)
		$submissionProgress = $submission->getData('submissionProgress');
		if ($submissionProgress && $submissionProgress == "start") return true;

		// Managers should always have access.
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		if (array_intersect(array(Role::ROLE_ID_MANAGER), $userRoles)) return true;

		// Sub editors and assistants need to be assigned to the current stage.
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$stageAssignments = $stageAssignmentDao->getBySubmissionAndStageId($submission->getId(), $submission->getStageId(), null, $user->getId());
		while ($stageAssignment = $stageAssignments->next()) {
			$userGroup = Repo::userGroup()->get($stageAssignment->getUserGroupId(),$contextId);
			if (in_array($userGroup->getRoleId(), array(Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT))) return true;
		}

		// Default: Read-only.
		return false;
	}

}
