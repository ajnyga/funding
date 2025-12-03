<?php

use PKP\components\listPanels\ListPanel;
use PKP\db\DAORegistry;
use APP\core\Application;
use APP\facades\Repo;

import('plugins.generic.funding.classes.FunderDAO');

class FundersListPanel extends ListPanel
{
    private $submission;

    public function __construct($id, $title, $submission, $args = [])
    {
        parent::__construct($id, $title, $args);
        $this->submission = $submission;
        $this->items = $this->getFundersItems($submission->getId());
    }

    public function getConfig()
    {
        $config = parent::getConfig();

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        
        $fundersApiUrl = $request->getDispatcher()->url($request, Application::ROUTE_API, $context->getPath(), 'funders');
        $canEditPublication = Repo::submission()->canEditPublication($this->submission->getId(), $request->getUser()->getId());
        $form = $this->getForm($fundersApiUrl, $this->submission);

        $config = array_merge(
            $config,
            [
                'form' => $form->getConfig(),
                'submissionId' => $this->submission->getId(),
                'submissionStatus' => $this->submission->getStatus(),
                'canEditPublication' => $canEditPublication,
                'emptyLabel' => __('plugins.generic.funding.noneCreated'),
                'fundersApiUrl' => $fundersApiUrl,
				'i18nAddFunder' => __('plugins.generic.funding.addFunder'),
				'i18nEditFunder' => __('plugins.generic.funding.editFunder'),
                'i18nDeleteFunder' => __('plugins.generic.funding.deleteFunder'),
                'i18nConfirmDeleteFunder' => __('plugins.generic.funding.deleteFunder.confirmationMessage'),
                'i18nSearchFunder' => __('plugins.generic.funding.searchFunders'),
            ]
        );

        return $config;
    }

    private function getForm($fundersApiUrl, $submission)
    {
        import('plugins.generic.funding.classes.components.forms.FunderForm');
        return new FunderForm(
            $fundersApiUrl,
            $submission      
        );
    }
    
    private function getFundersItems(int $submissionId): array
    {
		$funderDao = DAORegistry::getDAO('FunderDAO');
        $funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');
		$funderResult = $funderDao->getBySubmissionId($submissionId);

		$funders = [];
		while ($funder = $funderResult->next()) {
			$funderAwardNumbers = $funderAwardDao->getFunderAwardNumbersByFunderId($funder->getId());
            $funders[] = [
				'id' => $funder->getId(),
				'name' => $funder->getFunderName(),
				'identification' => $funder->getFunderIdentification(),
                'awards' => array_values($funderAwardNumbers)
			];
		}

        return $funders;
    }
}
