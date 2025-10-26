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

namespace APP\plugins\generic\funding;

use APP\core\Application;
use APP\pages\submission\SubmissionHandler;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use APP\facades\Repo;
use APP\plugins\generic\funding\classes\migration\install\SchemaMigration;
use APP\plugins\generic\funding\classes\Funder;
use APP\plugins\generic\funding\classes\FunderDAO;
use APP\plugins\generic\funding\classes\FunderAward;
use APP\plugins\generic\funding\classes\FunderAwardDAO;
use APP\plugins\generic\funding\controllers\grid\FunderGridHandler;
use PKP\db\DAORegistry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

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

            $funderDao = new FunderDAO();
            DAORegistry::registerDAO('FunderDAO', $funderDao);

            $funderAwardDao = new FunderAwardDAO();
            DAORegistry::registerDAO('FunderAwardDAO', $funderAwardDao);
    
            Hook::add('LoadComponentHandler', function (string $hookName, array $args): bool {
                $component = $args[0];
                $componentInstance = & $args[2];
                if ($component !== 'plugins.generic.funding.controllers.grid.FunderGridHandler') {
                    return false;
                }

                $componentInstance = new controllers\grid\FunderGridHandler($this);
                return true;
            });

            $this->extendMaps();

            Hook::add('TemplateManager::display', $this->addToSubmissionWizardSteps(...));
            Hook::add('Template::SubmissionWizard::Section', $this->addToSubmissionWizardTemplate(...));
            Hook::add('Template::SubmissionWizard::Section::Review', $this->addToSubmissionWizardReviewTemplate(...));

            Hook::add('TemplateManager::display', $this->addGridhandlerJs(...));

            Hook::add('Templates::Article::Details', $this->addSubmissionDisplay(...));    //OJS
            Hook::add('Templates::Catalog::Book::Details', $this->addSubmissionDisplay(...)); //OMP
            Hook::add('Templates::Preprint::Details', $this->addSubmissionDisplay(...));    //OPS

            Hook::add('articlecrossrefxmlfilter::execute', $this->addCrossrefElement(...));
            Hook::add('datacitexmlfilter::execute', $this->addDataCiteElement(...));
            Hook::add('OAIMetadataFormat_OpenAIRE::findFunders', $this->addOpenAIREFunderElement(...));

            // Registering build file for JS and CSS to be loaded
            $request = Application::get()->getRequest();
            $templateMgr = TemplateManager::getManager($request);
            $this->addJavaScript($request, $templateMgr);
            $templateMgr->addStyleSheet('backendUiExampleStyle',"{$request->getBaseUrl()}/{$this->getPluginPath()}/public/build/build.css", [
                'contexts' => ['backend']
            ] );

        }
        return $success;
    }

    /**
     * Add a JavaScript file to the backend interface.
     *
     * @param \PKP\core\Request $request The current request
     * @param TemplateManager $templateMgr Template manager instance
     *
     * @return void
     */
    public function addJavaScript($request, $templateMgr)
    {
        $templateMgr->addJavaScript(
            'funding',
            "{$request->getBaseUrl()}/{$this->getPluginPath()}/public/build/build.iife.js",
            [
                'inline' => false,
                'contexts' => ['backend'],
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST
            ]
        );
    }

    /**
     * Extend the submission and publication maps with funding metadata.
     *
     * @return void
     */
    public function extendMaps() {
        app('maps')->extend(\PKP\submission\maps\Schema::class, function($output, \APP\submission\Submission $item, \PKP\submission\maps\Schema $map) {
            $submissionId = $item->getId();
            $output['funding'] = $this->getFundingDataForMap($submissionId);
            return $output;
        });

        app('maps')->extend(\PKP\publication\maps\Schema::class, function($output, \APP\publication\Publication $item, \PKP\publication\maps\Schema $map) {
            $submissionId = $item->getData('submissionId');
            $output['funding'] = $this->getFundingDataForMap($submissionId);
            return $output;
        });
    }

    /**
     * Retrieve funding data for a given submission in an array format suitable for map output.
     *
     * @param int $submissionId The ID of the submission to retrieve funder data for
     *
     * @return array|null An array of funder data or null if no funders are found
     */
    protected function getFundingDataForMap($submissionId) {
        if (!$submissionId) return null;

        $funderDao = DAORegistry::getDAO('FunderDAO');
        $funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');

        $funders = $funderDao->getBySubmissionId($submissionId);
        $funderData = [];

        while ($funder = $funders->next()) {
            $funderId = $funder->getId();
            $funderAwards = $funderAwardDao->getFunderAwardNumbersByFunderId($funderId);
            $funderData[] = [
                'funderName' => $funder->getFunderName(),
                'funderIdentification' => $funder->getFunderIdentification(),
                'funderAwards' => array_values($funderAwards),
            ];
        }

        return !empty($funderData) ? $funderData : null;
    }

    /**
     * Register the FunderGridHandler component to be loaded via LoadComponentHandler.
     *
     * @return void
     */
    public function setupGridHandler(): void
    {
    }


    /**
     * Inject a funding section into the submission wizard steps UI.
     *
     * @param string $hookName
     * @param array $params
     *
     * @return bool Hook return value
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
     * Render the funding section content inside the submission wizard.
     *
     * @param string $hookName
     * @param array $params
     *
     * @return bool Hook return value
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
     * Add a review panel for the funding data in the final step of the wizard.
     *
     * @param string $hookName
     * @param array $params
     *
     * @return bool Hook return value
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
     * Add a tab component to render the funder grid in publication workflow.
     *
     * @param string $hookName
     * @param array $params
     *
     * @return bool Hook return value
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
     * Inject JS and CSS needed for the custom grid UI.
     *
     * @param string $hookName
     * @param array $params
     *
     * @return bool Hook return value
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
            '#fundingGridInWorkflow { margin-top: 32px; }.ui-helper-hidden-accessible{border:0;clip:rect(0 0 0 0);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute;width:1px}',
            [
                'inline' => true,
                'contexts' => 'backend',
            ]
        );
        return false;
    }

    /**
     * Determine the submission object depending on the current application (OJS/OMP/OPS).
     *
     * @param TemplateManager $templateMgr
     *
     * @return Submission|null
     */
    function getSubmissionOfApplication($templateMgr) {
        $application = Application::getName();
        switch($application) {
            case 'ojs2':
                $submission = $templateMgr->getTemplateVars('article');
                break;
            case 'omp':
                $submission = $templateMgr->getTemplateVars('publishedSubmission');
                break;
            case 'ops':
                $submission = $templateMgr->getTemplateVars('preprint');
                break;
        }
        return $submission;
    }


    /**
     * Add funder data block to the public-facing article/book/preprint display.
     *
     * @param string $hookName
     * @param array $params
     *
     * @return bool Hook return value
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
     * Add <fr:program> XML node to Crossref export with funding info.
     *
     * @param string $hookName
     * @param array $params
     *
     * @return bool Hook return value
     */
    function addCrossrefElement($hookName, $params) {
        $preliminaryOutput =& $params[0];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $funderDAO = DAORegistry::getDAO('FunderDAO');
        $funderAwardDAO = DAORegistry::getDAO('FunderAwardDAO');

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

            $publishedSubmission = Repo::submission()->getByDoi($doi, $context->getId());
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
     * Add <fundingReference> XML nodes to DataCite export for submission.
     *
     * @param string $hookName
     * @param array $params
     *
     * @return bool Hook return value
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
                    $publishedSubmission = Repo::submission()->get($submissionId);
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
     * Add <funding-group> XML block to OpenAIRE OAI metadata export.
     *
     * @param string $hookName
     * @param array $params
     *
     * @return string Modified XML snippet with funding info
     */
    function addOpenAIREFunderElement($hookName, $params) {
        $submissionId =& $params[0];
        $fundingReferences =& $params[1];
        $funderDAO = DAORegistry::getDAO('FunderDAO');
        $funderAwardDAO = DAORegistry::getDAO('FunderAwardDAO');
        $publishedSubmission = Repo::submission()->get($submissionId);
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
        return new SchemaMigration();
    }

    /**
     * Return the full JS URL path for this plugin.
     *
     * @return string
     */
    function getJavaScriptURL() {
        return Application::get()->getRequest()->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'js';
    }

    /**
     * Format funder entity as an associative array for frontend or API use.
     *
     * @param Funder $funder
     *
     * @return array
     */
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
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $verb)
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ] : [],
            parent::getActions($request, $verb)
        );
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->registerPlugin('function', 'plugin_url', $this->smartyPluginUrl(...));

                $form = new FundingSettingsForm($this, $context->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\funding\FundingPlugin', '\FundingPlugin');
}
