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
        $canEditPublication = Repo::submission()->canEditPublication($this->submission->getId(), $request->getUser()->getId());

        $config = array_merge(
            $config,
            [
                'canEditPublication' => $canEditPublication,
                'emptyLabel' => __('plugins.generic.funding.noneCreated'),
				'i18nAddFunder' => __('plugins.generic.funding.addFunder'),
                'i18nDeleteFunder' => __('plugins.generic.funding.deleteFunder'),
                'i18nConfirmDeleteFunder' => __('plugins.generic.funding.deleteFunder.confirmationMessage'),
            ]
        );

        return $config;
    }

    private function getFundersItems(int $submissionId): array
    {
		$funderDao = DAORegistry::getDAO('FunderDAO');
		$funderResult = $funderDao->getBySubmissionId($submissionId);

		$funders = [];
		while ($funder = $funderResult->next()) {
			$funders[] = [
				'id' => $funder->getId(),
				'name' => $funder->getFunderName(),
				'identification' => $funder->getFunderIdentification(),
			];
		}

        return $funders;
    }
}
