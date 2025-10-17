<?php

/**
 * @file FundingSettingsForm.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FundingSettingsForm
 * @brief Form for journal managers to modify funding plugin settings
 */

namespace APP\plugins\generic\funding;

use APP\template\TemplateManager;
use PKP\form\Form;

class FundingSettingsForm extends Form
{
    public int $journalId;
    public FundingPlugin $plugin;

    /**
     * Constructor
     */
    public function __construct(FundingPlugin $plugin, int $journalId)
    {
        $this->journalId = $journalId;
        $this->plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Initialize form data.
     */
    public function initData()
    {
        $this->_data = [
            'enableGrantIdValidation' => (bool) $this->plugin->getSetting($this->journalId, 'enableGrantIdValidation'),
        ];
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(['enableGrantIdValidation']);
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $this->plugin->updateSetting($this->journalId, 'enableGrantIdValidation', (bool) $this->getData('enableGrantIdValidation'));
        parent::execute(...$functionArgs);
    }
}
