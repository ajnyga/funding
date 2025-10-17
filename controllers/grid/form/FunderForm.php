<?php

/**
 * @file plugins/generic/funding/controllers/grid/form/FunderForm.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FunderForm
 * @ingroup controllers_grid_funding
 *
 * Form for adding/editing a funder
 *
 */

namespace APP\plugins\generic\funding\controllers\grid\form;

use APP\template\TemplateManager;
use APP\core\Application;
use PKP\db\DAORegistry;
use PKP\form\Form;
use PKP\form\validation\FormValidator;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorCustom;

class FunderForm extends Form {
    /** @var int Context ID */
    var $contextId;

    /** @var int Submission ID */
    var $submissionId;

    /** @var int|null Funder ID */
    var $funderId;

    /** @var FundingPlugin */
    var $plugin;

    /** A mapping of funder DOIs to RORs for funders that support grant ID validation */
    const array AWARD_FUNDERS = [
        'doi.org/10.13039/501100002341' => '05k73zm37', // Research Council of Finland
        'doi.org/10.13039/501100001665' => '00rbzpz17', // French National Research Agency
        'doi.org/10.13039/501100000923' => '05mmh0f86', // Australian Research Council
        'doi.org/10.13039/100018231' => '03zj4c476', // Aligning Science Across Parkinson's
        'doi.org/10.13039/501100000024' => '01gavpb45', // Canadian Institutes of Health Research
        'doi.org/10.13039/501100000780' => '00k4n6c32', // European Commission
        'doi.org/10.13039/501100000806' => '02k4b9v70', // European Environment Agency
        'doi.org/10.13039/501100001871' => '00snfqn58', // Portuguese Science and Technology Foundation
        'doi.org/10.13039/501100002428' => '013tf3c58', // Austrian Science Fund
        'doi.org/10.13039/501100006364' => '03m8vkq32', // The French National Cancer Institute
        'doi.org/10.13039/501100004488' => '03n51vw80', // Croatian Science Foundation
        'doi.org/10.13039/501100005375' => '02ar66p97', // Latvian Council of Science
        'doi.org/10.13039/501100004564' => '01znas443', // Ministry of Education, Science and Technological Development of the Republic of Serbia
        'doi.org/10.13039/501100000925' => '011kf5r70', // National Health and Medical Research Council
        'doi.org/10.13039/100000002' => '01cwqze88', // National Institutes of Health
        'doi.org/10.13039/501100000038' => '01h531d29', // Natural Sciences and Engineering Research Council of Canada
        'doi.org/10.13039/100000001' => '021nxhr62', // National Science Foundation
        'doi.org/10.13039/501100003246' => '04jsz6e67', // Dutch Research Council
        'doi.org/10.13039/501100000690' => '00dq2kk65', // Research Councils UK
        'doi.org/10.13039/501100001602' => '0271asj38', // Science Foundation Ireland
        'doi.org/10.13039/501100001711' => '00yjd3n13', // Swiss National Science Foundation
        'doi.org/10.13039/100001345' => '006cvnv84', // Social Science Research Council
        'doi.org/10.13039/501100004410' => '04w9kkr77', // Scientific and Technological Research Council of Turkey
        'doi.org/10.13039/501100011730' => '00x0z1472', // Templeton World Charity Foundation
        'doi.org/10.13039/100014013' => '001aqnf71', // UK Research and Innovation
        'doi.org/10.13039/100010269' => '029chgv08', // Wellcome Trust
    ];

    /**
     * Constructor
     * @param $fundingPlugin FundingPlugin
     * @param $contextId int Context ID
     * @param $submissionId int Submission ID
     * @param $funderId int (optional) Funder ID
     */
    function __construct($fundingPlugin, $contextId, $submissionId, $funderId = null) {
        parent::__construct($fundingPlugin->getTemplateResource('editFunderForm.tpl'));

        $this->contextId = $contextId;
        $this->submissionId = $submissionId;
        $this->funderId = $funderId;
        $this->plugin = $fundingPlugin;

        // Add form checks
        $this->addCheck(new FormValidator($this, 'funderNameIdentification', 'required', 'plugins.generic.funding.funderNameIdentificationRequired'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
        if ($this->plugin->getSetting($contextId, 'enableGrantIdValidation')) {
            $this->addCheck(new FormValidatorCustom($this, 'grantId', 'required', 'plugins.generic.funding.fundIdInvalid', function () {
                $funderNameIdentification = $this->getData('funderNameIdentification');
                $ror = array_reduce(array_keys(self::AWARD_FUNDERS), function($carry, $item) use ($funderNameIdentification) {
                    return strpos($funderNameIdentification, $item) !== false ? self::AWARD_FUNDERS[$item] : $carry;
                });
                // If no matching ROR was found, the grant ID cannot be validated for this agency. Allow anything.
                if (!$ror) return true;

                foreach (explode(';', $this->getData('funderAwards')) as $grantId) {
                    $httpClient = Application::get()->getHttpClient();
                    $awardResponse = $httpClient->request('GET', "https://zenodo.org/api/awards?funders={$ror}&q=" . urlencode($grantId));
                    $body = json_decode($awardResponse->getBody(), true);
                    $success = array_reduce($body['hits']['hits'] ?? [], function($carry, $item) use ($grantId) {
                        return $carry || $item['id'] === $grantId || ($item['number']  ?? null) === $grantId);
                    }, false);
                    if (!$success) return false;
                }
                return true; // All matched
            }));
        }

    }

    /**
     * @copydoc Form::initData()
     */
    function initData() {
        $this->setData('submissionId', $this->submissionId);
        if ($this->funderId) {
            $funderDao = DAORegistry::getDAO('FunderDAO');
            $funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');

            $funder = $funderDao->getById($this->funderId);
            $this->setData('funderNameIdentification', $funder->getFunderNameIdentification());

            $funderAwards = $funderAwardDao->getFunderAwardNumbersByFunderId($this->funderId);
            $this->setData('funderAwards', implode(';', $funderAwards));
        }
    }

    /**
     * @copydoc Form::readInputData()
     */
    function readInputData() {
        $this->readUserVars(['funderNameIdentification', 'funderAwards', 'subsidiaryOption']);
    }

    /**
     * @copydoc Form::fetch
     */
    function fetch($request, $template = null, $display = false) {
        $templateMgr = TemplateManager::getManager();
        $templateMgr->assign('funderId', $this->funderId);
        $templateMgr->assign('submissionId', $this->submissionId);
        $subsidiaryOptions = ['' => __('plugins.generic.funding.funderSubOrganization.select')];
        $templateMgr->assign('subsidiaryOptions', $subsidiaryOptions);

        return parent::fetch($request);
    }

    /**
     * Save form values into the database
     */
    function execute(...$functionArgs) {
        $funderId = $this->funderId;
        $funderDao = DAORegistry::getDAO('FunderDAO');
        $funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');

        if ($funderId) {
            // Load and update an existing funder
            $funder = $funderDao->getById($this->funderId, $this->submissionId);
        } else {
            // Create a new funder
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
                    $funderName = trim(preg_replace('/\s*\[.*?\]\s*/ ', '', $subOrganizationNameIdentification));
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

        if ($funderId) {
            $funderDao->updateObject($funder);
            $funderAwardDao->deleteByFunderId($funderId);
        } else {
            $funderId = $funderDao->insertObject($funder);
        }

        $funderAwards = [];
        if (!empty($this->getData('funderAwards'))) {
            $funderAwards = explode(';', $this->getData('funderAwards'));
        }
        foreach ($funderAwards as $funderAwardNumber){
            $funderAward = $funderAwardDao->newDataObject();
            $funderAward->setFunderId($funderId);
            $funderAward->setFunderAwardNumber($funderAwardNumber);
            $funderAwardDao->insertObject($funderAward);
        }
        return $funderId;
    }
}
