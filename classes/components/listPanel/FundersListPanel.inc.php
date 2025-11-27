<?php

use PKP\db\DAORegistry;
use PKP\components\listPanels\ListPanel;

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

        $config = array_merge(
            $config,
            [
				'emptyLabel' => __('plugins.generic.funding.noneCreated')
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
				'title' => $funder->getFunderName(),
				'subtitle' => $funder->getFunderIdentification(),
			];
		}

        return $funders;
    }
}
