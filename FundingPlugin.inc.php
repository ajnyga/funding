<?php

/**
 * @file plugins/generic/funding/FundingPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FundingPlugin
 * @ingroup plugins_generic_funding

 * @brief Add funding data to the submission metadata, consider them in the Crossref export,
 * and display them on the submission view page.
 *
 */

use APP\core\Application;
use APP\pages\submission\SubmissionHandler;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;

import('lib.pkp.classes.plugins.GenericPlugin');

class FundingPlugin extends GenericPlugin {

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'FundingPlugin';
    }

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
    function getDisplayName() {
		return __('plugins.generic.funding.displayName');
    }

	/**
	 * @copydoc Plugin::getDescription()
	 */
    function getDescription() {
		return __('plugins.generic.funding.description');
    }

	/**
	 * @copydoc Plugin::register()
	 */
    function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if ($success && $this->getEnabled($mainContextId)) {

			import('plugins.generic.funding.classes.FunderDAO');
			$funderDao = new FunderDAO();
			DAORegistry::registerDAO('FunderDAO', $funderDao);

			import('plugins.generic.funding.classes.FunderAwardDAO');
			$funderAwardDao = new FunderAwardDAO();
			DAORegistry::registerDAO('FunderAwardDAO', $funderAwardDao);

			HookRegistry::register('TemplateManager::display', array($this, 'addToSubmissionWizardSteps'));
			HookRegistry::register('Template::SubmissionWizard::Section', array($this, 'addToSubmissionWizardTemplate'));
			HookRegistry::register('Template::SubmissionWizard::Section::Review', array($this, 'addToSubmissionWizardReviewTemplate'));

			HookRegistry::register('Template::Workflow::Publication', array($this, 'addToPublicationForms'));

			HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));

			HookRegistry::register('TemplateManager::display',array($this, 'addGridhandlerJs'));

			HookRegistry::register('Templates::Article::Details', array($this, 'addSubmissionDisplay'));	//OJS
			HookRegistry::register('Templates::Catalog::Book::Details', array($this, 'addSubmissionDisplay')); //OMP
			HookRegistry::register('Templates::Preprint::Details', array($this, 'addSubmissionDisplay'));	//OPS

			HookRegistry::register('articlecrossrefxmlfilter::execute', array($this, 'addCrossrefElement'));
			HookRegistry::register('datacitexmlfilter::execute', array($this, 'addDataCiteElement'));
			HookRegistry::register('OAIMetadataFormat_OpenAIRE::findFunders', array($this, 'addOpenAIREFunderElement'));


			HookRegistry::register("Submission::getProperties::values", array($this, 'modifyObjectPropertyValues'));
			HookRegistry::register('Publication::getProperties', array($this, 'modifyObjectPropertyValues'));
		}
		return $success;
	}


	/**
	 * Permit requests to the Funder grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupGridHandler($hookName, $params) {
		$component =& $params[0];
		if ($component == 'plugins.generic.funding.controllers.grid.FunderGridHandler') {
			import($component);
			FunderGridHandler::setPlugin($this);
			return true;
		}
		return false;
	}

	/**
	 * Add funding section to the details step of the submission wizard
	 */
	function addToSubmissionWizardSteps($hookName, $params) {
		$request = Application::get()->getRequest();

		if ($request->getRequestedPage() !== 'submission') {
			return;
		}

		if ($request->getRequestedOp() === 'saved') {
			return;
		}

		$submission = $request
			->getRouter()
			->getHandler()
			->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);

		if (!$submission || !$submission->getData('submissionProgress')) {
			return;
		}

		/** @var FunderDAO $funderDao */
		$funderDao = DAORegistry::getDAO('FunderDAO');
		$funderResult = $funderDao->getBySubmissionId($submission->getId());

		$funders = [];
		while ($funder = $funderResult->next()) {
			$funders[] = $this->getFunderData($funder);
		}

		/** @var TemplateManager $templateMgr */
		$templateMgr = $params[0];

		$steps = $templateMgr->getState('steps');
		$steps = array_map(function($step) {
			if ($step['id'] === 'editors') {
				$step['sections'][] = [
					'id' => 'funding',
					'name' => __('plugins.generic.funding.submissionWizard.name'),
					'description' => __('plugins.generic.funding.submissionWizard.description'),
					'type' => SubmissionHandler::SECTION_TYPE_TEMPLATE,
				];
			}
			return $step;
		}, $steps);

		$templateMgr->setState([
			'funders' => $funders,
			'steps' => $steps,
		]);

		$templateMgr->addJavaScript(
			'plugin-funder-submission-wizard',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/SubmissionWizard.js',
            [
				'contexts' => 'backend',
				'priority' => TemplateManager::STYLE_SEQUENCE_LATE,
			]
		);

		return false;
	}

	/**
	 * Insert template to display funding grid in submission wizard
	 */
	function addToSubmissionWizardTemplate($hookName, $params) {
		$smarty = $params[1];
		$output =& $params[2];

		$output .= sprintf(
			'<template v-else-if="section.id === \'funding\'">%s</template>',
			$smarty->fetch($this->getTemplateResource('fundersGrid.tpl'))
		);

		return false;
	}

	/**
	 * Insert template to review the funding data in the submission wizard
	 * before completing the submission
	 */
	function addToSubmissionWizardReviewTemplate($hookName, $params) {
		$submission = $params[0]['submission']; /** @var Submission $submission */
		$step = $params[0]['step']; /** @var string $step */
		$templateMgr = $params[1]; /** @var TemplateManager $templateMgr */
		$output =& $params[2];

		if ($step === 'editors') {
			$output .= $templateMgr->fetch($this->getTemplateResource('reviewFunders.tpl'));
		}

		return false;
	}

	/**
	 * Insert funder grid in the publication tabs
	 */
	function addToPublicationForms($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];

		$output .= sprintf(
			'<tab id="fundingGridInWorkflow" label="%s">%s</tab>',
			__('plugins.generic.funding.fundingData'),
			$smarty->fetch($this->getTemplateResource('fundersGrid.tpl'))
		);

		return false;
	}

	/**
	 * Add custom gridhandlerJS for backend
	 */
	function addGridhandlerJs($hookName, $params) {
		$templateMgr = $params[0];
		$request = $this->getRequest();
		$gridHandlerJs = $this->getJavaScriptURL($request, false) . DIRECTORY_SEPARATOR . 'FunderGridHandler.js';
		$templateMgr->addJavaScript(
			'FunderGridHandlerJs',
			$gridHandlerJs,
			array('contexts' => 'backend')
		);
		$templateMgr->addStylesheet(
			'FunderGridHandlerStyles',
			'#fundingGridInWorkflow { margin-top: 32px; }',
			[
				'inline' => true,
				'contexts' => 'backend',
			]
		);
		return false;
	}

	/**
	 * Gets submission from template manager according to the current application
	 */
	function getSubmissionOfApplication($templateMgr) {
		$application = Application::getName();
		switch($application) {
			case 'ojs2':
				$submission = $templateMgr->getTemplateVars('article');
				break;
			case 'omp':
				$submission = $templateMgr->getTemplateVars('monograph');
				break;
			case 'ops':
				$submission = $templateMgr->getTemplateVars('preprint');
				break;
		}
		return $submission;
	}

	/**
	* Hook to Templates::Article::Details and Templates::Catalog::Book::Details and list funder information
	* @param $hookName string
	* @param $params array
	*/
	function addSubmissionDisplay($hookName, $params) {
		$templateMgr = $params[1];
		$output =& $params[2];

		$submission = $this->getSubmissionOfApplication($templateMgr);

		$funderDao = DAORegistry::getDAO('FunderDAO');
		$funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');

		$funders = $funderDao->getBySubmissionId($submission->getId());

		$funderData = array();
		while ($funder = $funders->next()) {
			$funderId = $funder->getId();
			$funderAwards = $funderAwardDao->getFunderAwardNumbersByFunderId($funderId);
			$funderData[$funderId] = array(
				'funderName' => $funder->getFunderName(),
				'funderIdentification' => $funder->getFunderIdentification(),
				'funderAwards' => implode(";", $funderAwards)
			);
		}

		if ($funderData){
			$templateMgr->assign('funderData', $funderData);
			$output .= $templateMgr->fetch($this->getTemplateResource('listFunders.tpl'));
		}

		return false;

	}

	/**
	 * Hook to articlecrossrefxmlfilter::execute and add funding data to the Crossref XML export
	 * @param $hookName string
	 * @param $params array
	 */
	function addCrossrefElement($hookName, $params) {
		$preliminaryOutput =& $params[0];
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$funderDAO = DAORegistry::getDAO('FunderDAO');
		$funderAwardDAO = DAORegistry::getDAO('FunderAwardDAO');
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');

		$crossrefFRNS = 'http://www.crossref.org/fundref.xsd';
		$rootNode=$preliminaryOutput->documentElement;
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:fr', $crossrefFRNS);
		$articleNodes = $preliminaryOutput->getElementsByTagName('journal_article');
		foreach ($articleNodes as $articleNode) {
			$doiDataNode = $articleNode->getElementsByTagName('doi_data')->item(0);
			$aiProgramDataNode = $articleNode->getElementsByTagNameNS('http://www.crossref.org/AccessIndicators.xsd', 'program')->item(0);
			$doiNode = $doiDataNode->getElementsByTagName('doi')->item(0);
			$doi = $doiNode->nodeValue;

			$programNode = $preliminaryOutput->createElementNS($crossrefFRNS, 'fr:program');
			$programNode->setAttribute('name', 'fundref');

			$publishedSubmission = $submissionDao->getByPubId('doi', $doi);
			assert($publishedSubmission);
			$funders = $funderDAO->getBySubmissionId($publishedSubmission->getId());
			while ($funder = $funders->next()) {
				$groupNode = $preliminaryOutput->createElementNS($crossrefFRNS, 'fr:assertion');
				$groupNode->setAttribute('name', 'fundgroup');
				$funderNameNode = $preliminaryOutput->createElementNS($crossrefFRNS, 'fr:assertion', htmlspecialchars($funder->getFunderName(), ENT_COMPAT, 'UTF-8'));
				$funderNameNode->setAttribute('name', 'funder_name');
				$funderIdNode = $preliminaryOutput->createElementNS($crossrefFRNS, 'fr:assertion', $funder->getFunderIdentification());
				$funderIdNode->setAttribute('name', 'funder_identifier');
				$funderNameNode->appendChild($funderIdNode);
				$groupNode->appendChild($funderNameNode);
				// Append funder awards nodes
				$funderAwards = $funderAwardDAO->getByFunderId($funder->getId());
				while ($funderAward = $funderAwards->next()) {
					$awardNumberNode = $preliminaryOutput->createElementNS($crossrefFRNS, 'fr:assertion', $funderAward->getFunderAwardNumber());
					$awardNumberNode->setAttribute('name', 'award_number');
					$groupNode->appendChild($awardNumberNode);
				}
				$programNode->appendChild($groupNode);
			}
			if ($aiProgramDataNode) {
				$articleNode->insertBefore($programNode, $aiProgramDataNode);
			} else {
				$articleNode->insertBefore($programNode, $doiDataNode);
			}
		}
		return false;
	}

	/**
	 * Hook to datacitexmlfilter::execute and add funding data to the DataCite XML export
	 * @param $hookName string
	 * @param $params array
	 */
	function addDataCiteElement($hookName, $params) {
		$preliminaryOutput =& $params[0];
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$funderDAO = DAORegistry::getDAO('FunderDAO');
		$funderAwardDAO = DAORegistry::getDAO('FunderAwardDAO');

		$dataciteFRNS = 'http://datacite.org/schema/kernel-4';
		$rootNode=$preliminaryOutput->documentElement;

		// Get the alternateIdendifier element to get the article ID
		$alternateIdentifierNodes = $preliminaryOutput->getElementsByTagName('alternateIdentifier');
		foreach ($alternateIdentifierNodes as $alternateIdentifierNode) {
			$alternateIdentifierType = $alternateIdentifierNode->getAttribute('alternateIdentifierType');
			if ($alternateIdentifierType == 'publisherId') {
				$publisherId = $alternateIdentifierNode->nodeValue;
				$idsArray = explode('-', $publisherId);
				if (count($idsArray) == 3 ) {
					$submissionId = $idsArray[2];
					// Add the parent fundingReferences element
					$fundingReferencesNode = $preliminaryOutput->createElementNS($dataciteFRNS, 'fundingReferences');
					$publishedSubmission = Services::get('submission')->get($submissionId);
					assert($publishedSubmission);
					$funders = $funderDAO->getBySubmissionId($publishedSubmission->getId());
					while ($funder = $funders->next()) {
						$funderAwards = $funderAwardDAO->getByFunderId($funder->getId());
						if ($funderAwards->wasEmpty) {
							$funderReferenceNode = $preliminaryOutput->createElementNS($dataciteFRNS, 'fundingReference');
							$funderReferenceNode->appendChild($funderNameNode = $preliminaryOutput->createElementNS($dataciteFRNS, 'funderName', htmlspecialchars($funder->getFunderName(), ENT_COMPAT, 'UTF-8')));
							$funderReferenceNode->appendChild($funderIdNode = $preliminaryOutput->createElementNS($dataciteFRNS, 'funderIdentifier', $funder->getFunderIdentification()));
							$funderIdNode->setAttribute('funderIdentifierType', 'Crossref Funder ID');
							$fundingReferencesNode->appendChild($funderReferenceNode);
						}
						while ($funderAward = $funderAwards->next()) {
							$funderReferenceNode = $preliminaryOutput->createElementNS($dataciteFRNS, 'fundingReference');
							$funderReferenceNode->appendChild($funderNameNode = $preliminaryOutput->createElementNS($dataciteFRNS, 'funderName', htmlspecialchars($funder->getFunderName(), ENT_COMPAT, 'UTF-8')));
							$funderReferenceNode->appendChild($funderIdNode = $preliminaryOutput->createElementNS($dataciteFRNS, 'funderIdentifier', $funder->getFunderIdentification()));
							$funderIdNode->setAttribute('funderIdentifierType', 'Crossref Funder ID');
							$funderReferenceNode->appendChild($awardNumberNode = $preliminaryOutput->createElementNS($dataciteFRNS, 'awardNumber', $funderAward->getFunderAwardNumber()));
							$fundingReferencesNode->appendChild($funderReferenceNode);
						}
					}
					$rootNode->appendChild($fundingReferencesNode);
				}
			}
		}
		return false;
	}

	/**
	 * Hook to OAIMetadataFormat_OpenAIRE::findFunders and add funding data to the OpenAIRE OAI
	 * @param $hookName string
	 * @param $params array
	 */
	function addOpenAIREFunderElement($hookName, $params) {
		$submissionId =& $params[0];
		$fundingReferences =& $params[1];
		$funderDAO = DAORegistry::getDAO('FunderDAO');
		$funderAwardDAO = DAORegistry::getDAO('FunderAwardDAO');
		$publishedSubmission = Services::get('submission')->get($submissionId);
		assert($publishedSubmission);
		$funders = $funderDAO->getBySubmissionId($publishedSubmission->getId());
		while ($funder = $funders->next()) {
			$fundingReferences .= "\t\t\t\t<award-group id=\"group-" . $funder->getId() . "\">\n";
			$fundingReferences .= "\t\t\t\t\t<funding-source id=\"source-" . $funder->getId() . "\">\n";
			$fundingReferences .= "\t\t\t\t\t\t<institution-wrap>\n";
			$fundingReferences .= "\t\t\t\t\t\t\t<institution>" . htmlspecialchars($funder->getFunderName(), ENT_COMPAT, 'UTF-8') . "</institution>\n";
			$fundingReferences .= "\t\t\t\t\t\t\t<institution-id institution-id-type=\"doi\" vocab=\"open-funder-registry\" vocab-identifier=\"" . $funder->getFunderIdentification() . "\">" . $funder->getFunderIdentification() . "</institution-id>\n";
			$fundingReferences .= "\t\t\t\t\t\t</institution-wrap>\n";
			$fundingReferences .= "\t\t\t\t\t</funding-source>\n";
			$funderAwards = $funderAwardDAO->getByFunderId($funder->getId());
			while ($funderAward = $funderAwards->next()) {
				$fundingReferences .= "\t\t\t\t\t<award-id>" . $funderAward->getFunderAwardNumber() . "</award-id>\n";
			}
			$fundingReferences .= "\t\t\t\t</award-group>\n";
		}
		if ($fundingReferences)
			$fundingReferences = "\t\t\t<funding-group specific-use=\"crossref\">\n" . $fundingReferences . "\t\t\t</funding-group>\n";
		return $fundingReferences;
	}

	/**
	 * @copydoc Plugin::getInstallMigration()
	 */
	function getInstallMigration() {
		$this->import('FundingSchemaMigration');
		return new FundingSchemaMigration();
	}


	/**
	 * Get the JavaScript URL for this plugin.
	 */
	function getJavaScriptURL() {
		return Application::get()->getRequest()->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'js';
	}

	public function getFunderData(Funder $funder): array
	{
		/** @var FunderAwardDAO $funderAwardDao */
		$funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');
		$funderAwards = $funderAwardDao->getFunderAwardNumbersByFunderId($funder->getId());

		return [
			'id' => $funder->getId(),
			'name' => $funder->getFunderName(),
			'identification' => $funder->getFunderIdentification(),
			'awards' => implode(";", $funderAwards),
		];
	}


	/**
	 * Add Funding submission, and publication values
	 *
	 * @param $hookName string <Object>::getProperties::values
	 * @param $args array [
	 * 		@option $values array Key/value store of property values
	 * 		@option $object Submission|Issue|Galley
	 * 		@option $props array Requested properties
	 * 		@option $args array Request args
	 * ]
	 *
	 * @return void
	 */
	public function modifyObjectPropertyValues($hookName, $args) {
		$values =& $args[0];
		$object = $args[1];
		$props = $args[2];


		if (get_class($object) === 'Publication') {
			$submissionId = $object->getData('submissionId');
		} else if (get_class($object) === 'Submission') {
			$submissionId = $object->getId();
		}

		if (!isset($submissionId))
			return;

		$funderDao = DAORegistry::getDAO('FunderDAO');
		$funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');

		$funders = $funderDao->getBySubmissionId($submissionId);
		$funderData = array();
		while ($funder = $funders->next()) {
			$funderId = $funder->getId();
			$funderAwards = $funderAwardDao->getFunderAwardNumbersByFunderId($funderId);
			$funderData[] = array(
				'funderName' => $funder->getFunderName(),
				'funderIdentification' => $funder->getFunderIdentification(),
				'funderAwards' => array_values($funderAwards)
			);
		}

		if ($funderData) {
			$values['funding'] = $funderData ? $funderData : null;
		}
	}

}
