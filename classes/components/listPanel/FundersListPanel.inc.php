<?php

use PKP\db\DAORegistry;
use PKP\components\listPanels\ListPanel;

import('plugins.generic.funding.classes.FunderDAO');

class FundersListPanel extends ListPanel
{
    private $submission;
    private $funders;

    public function __construct($id, $title, $submission, $args = [])
    {
        parent::__construct($id, $title, $args);
        $this->submission = $submission;
        $this->funders = $this->getFunders($submission->getId());
    }

    private function getFunders(int $submissionId): array
    {
		$funderDao = DAORegistry::getDAO('FunderDAO');
		$funderResult = $funderDao->getBySubmissionId($submissionId);

		$funders = [];
		while ($funder = $funderResult->next()) {
			$funders[] = $this->getFunderData($funder);
		}

        return $funders;
    }

    public function getFunderData(Funder $funder): array
	{
		$funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');
		$funderAwards = $funderAwardDao->getFunderAwardNumbersByFunderId($funder->getId());

		return [
			'id' => $funder->getId(),
			'name' => $funder->getFunderName(),
			'identification' => $funder->getFunderIdentification(),
			'awards' => implode(";", $funderAwards),
		];
	}
}
