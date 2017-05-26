<?php

/**
 * @file controllers/grid/FunderGridHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunderGridHandler
 * @ingroup controllers_grid_funder
 *
 * @brief Handle Funder grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.fundRef.controllers.grid.FunderGridRow');
import('plugins.generic.fundRef.controllers.grid.FunderGridCellProvider');

class FunderGridHandler extends GridHandler {
	static $plugin;
	
	/**
	 * Set the Funder plugin.
	 * @param $plugin FunderPlugin
	 */
	static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}
	
	/**
	 * Get the submission associated with this grid.
	 * @return Submission
	 */
	function getSubmission() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}	
	
	

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();			
		
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchRow', 'addFunder', 'editFunder', 'updateFunder', 'deleteFunder')
		);
		
						
	}
	
	
	//
	// Overridden template methods
	//
	
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
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
		$this->setTitle('plugins.generic.fundRef.fundRef');
		$this->setEmptyRowText('plugins.generic.fundRef.noneCreated');

		// Get the items and add the data to the grid
		$funderDao = DAORegistry::getDAO('FunderDAO');
		$this->setGridDataElements($funderDao->getBySubmissionId($submissionId));
		
		// Add grid-level actions
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'addFunder',
				new AjaxModal(
					$router->url($request, null, null, 'addFunder', null, array('submissionId' => $submissionId)),
					__('plugins.generic.fundRef.addFunder'),
					'modal_add_item'
				),
				__('plugins.generic.fundRef.addFunder'),
				'add_item'
			)
		);

		// Columns
		$cellProvider = new FunderGridCellProvider();
		
		$this->addColumn(new GridColumn(
			'funderId',
			'plugins.generic.fundRef.funderId',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));			
		$this->addColumn(new GridColumn(
			'funderName',
			'plugins.generic.fundRef.funderName',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));
		$this->addColumn(new GridColumn(
			'funderIdentification',
			'plugins.generic.fundRef.funderIdentification',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));		
		$this->addColumn(new GridColumn(
			'funderGrants',
			'plugins.generic.fundRef.funderGrants',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));			
		
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc Gridhandler::getRowInstance()
	 */
	 
	/**
	 * @see GridHandler::getJSHandler()
	 */
	public function getJSHandler() {
		return '$.pkp.controllers.grid.funders.FunderGridHandler';
	}
	 
	function getRowInstance() {
		return new FunderGridRow();
	}

	//
	// Public Grid Actions
	//

	/**
	 * An action to add a new custom navigationItem
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest Request object
	 */
	function addFunder($args, $request) {
		// Calling editFunderitem with an empty ID will add
		// a new Funder item.
		return $this->editFunder($args, $request);
	}

	/**
	 * An action to edit a funder
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest Request object
	 * @return string Serialized JSON object
	 */
	function editFunder($args, $request) {
		$funderId = $request->getUserVar('funderId');
		$context = $request->getContext();
		
		$submission = $this->getSubmission();
		$submissionId = $submission->getId();
		
		$this->setupTemplate($request);
		
		// Create and present the edit form
		import('plugins.generic.fundRef.controllers.grid.form.FunderForm');
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
		import('plugins.generic.fundRef.controllers.grid.form.FunderForm');
		$funderForm = new FunderForm(self::$plugin, $context->getId(), $submissionId, $funderId);
		$funderForm->readInputData();
		
		// Check the results
		if ($funderForm->validate()) {
			$funder = $funderForm->execute();
 			return DAO::getDataChangedEvent($submissionId);
			
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
		$funderId = (int) $request->getUserVar('funderId');
		$submission = $this->getSubmission();
		$submissionId = $submission->getId();
		
		$funderDao = DAORegistry::getDAO('FunderDAO');
		$funder = $funderDao->getById($funderId, $submissionId);
		
		$funderDao->deleteObject($funder);
		return DAO::getDataChangedEvent($submissionId);
		
	}
}

?>