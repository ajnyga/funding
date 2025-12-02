<?php

use PKP\handler\APIHandler;
use PKP\security\Role;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\db\DAORegistry;

class FundersHandler extends APIHandler
{
    private const CROSSREF_FUNDERS_API_URL = 'https://api.crossref.org/funders';
    
    public function __construct() {
        $this->_handlerPath = 'funders';
        $roles = [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_AUTHOR];
        $this->_endpoints = [
            'GET' => [
                [
                    'pattern' => $this->getEndpointPattern() . '/submission/{submissionId}',
                    'handler' => [$this, 'getBySubmission'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/suggestions',
                    'handler' => [$this, 'getFundersSuggestions'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/subOrganizations',
                    'handler' => [$this, 'getFundersSubOrganizations'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/vocabs',
                    'handler' => [$this, 'getGrantNumberVocab'],
                    'roles' => $roles
                ]
            ],
            'POST' => [
                [
                    'pattern' => $this->getEndpointPattern(),
                    'handler' => [$this, 'addFunder'],
                    'roles' => $roles
                ]
            ],
            'PUT' => [
                [
                    'pattern' => $this->getEndpointPattern() . '/{funderId}',
                    'handler' => [$this, 'editFunder'],
                    'roles' => $roles
                ]
            ],
            'DELETE' => [
                [
                    'pattern' => $this->getEndpointPattern() . '/{funderId}',
                    'handler' => [$this, 'deleteFunder'],
                    'roles' => $roles
                ]
            ]
        ];
        parent::__construct();
    }

    public function authorize($request, &$args, $roleAssignments) {
        $rolePolicy = new PolicySet(PolicySet::COMBINING_PERMIT_OVERRIDES);

        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function getBySubmission($slimRequest, $response, $args) {
        $submissionId = $args['submissionId'];
        $funderDao = DAORegistry::getDAO('FunderDAO');

        $funders = $funderDao->getBySubmissionId($submissionId);
        $funderItems = [];
		while ($funder = $funders->next()) {
			$funderItems[] = [
				'id' => $funder->getId(),
				'name' => $funder->getFunderName(),
				'identification' => $funder->getFunderIdentification(),
            ];
		}

        return $response->withJson(['items' => $funderItems], 200);
    }

    public function getFundersSuggestions($slimRequest, $response, $args) {
        $queryParams = $slimRequest->getQueryParams();
        $searchPhrase = $queryParams['searchPhrase'] ?? '';

        if (empty($searchPhrase)) {
            return $response->withJson(['items' => []], 200);
        }

        $queryData = [
            'query' => $searchPhrase . '*',
            'rows' => 10
        ];
        $url = self::CROSSREF_FUNDERS_API_URL . '?' . http_build_query($queryData);

        try {
            $responseData = $this->sendHttpRequest($url, 'GET');
        } catch (\Exception $e) {
            return $response->withStatus(500)->withJsonError('plugins.generic.funding.api.500.fundersSearchError');
        }

        $fundersSuggestions = [];
        if ($responseData['message']['total-results'] > 0) {
            foreach ($responseData['message']['items'] as $item) {
                $altNames = implode(', ', $item['alt-names']);
                $fundersSuggestions[] = [
                    'label' => $item['name'] . ' [' . $altNames . ']',
                    'value' => $item['name'] . ' [' . $item['uri'] . ']'
                ];
            }
        }

        return $response->withJson(['items' => $fundersSuggestions], 200);
    }

    public function getFundersSubOrganizations($slimRequest, $response, $args) {
        $queryParams = $slimRequest->getQueryParams();
        $funderName = $queryParams['funder'];

        $funderIdentification = substr($funderName, strrpos($funderName, '/') + 1, -1);
        $url = self::CROSSREF_FUNDERS_API_URL . '/' . $funderIdentification;

        try {
            $responseData = $this->sendHttpRequest($url, 'GET');
        } catch (\Exception $e) {
            return $response->withStatus(500)->withJsonError('plugins.generic.funding.api.500.fundersSearchError');
        }

        $subsidiaryOptions = [];
        if ($responseData['status'] == 'ok') {
            foreach ($responseData['message']['descendants'] as $descendant) {
                $subsidiaryOptions[] = $this->getSubsidiaryOption($descendant, $responseData['message']['hierarchy-names']);
            }
        }

        return $response->withJson(['items' => $subsidiaryOptions], 200);
    }

    /**
	 * Allows the author to insert any grant number using the FieldControlledVocab field.
	 */
    public function getGrantNumberVocab($slimRequest, $response, $args) {
        return $response->withJson('', 200);
    }

    public function addFunder($slimRequest, $response, $args) {
        $bodyParams = $slimRequest->getParsedBody();
        
        list($funderName, $funderIdentification) = $this->getFunderNameAndIdentification(
            $bodyParams['funderNameIdentification'],
            $bodyParams['funderSubOrganization']
        );
        $contextId = Application::get()->getRequest()->getContext()->getId();
        $funderDao = DAORegistry::getDAO('FunderDAO');
        $funderAwardDao = DAORegistry::getDAO('FunderAwardDAO');
        
        $funder = $funderDao->newDataObject();
        $funder->setAllData([
            'contextId' => $contextId,
            'submissionId' => $bodyParams['submissionId'],
            'funderName' => $funderName,
            'funderIdentification' => $funderIdentification
        ]);
        $funderId = $funderDao->insertObject($funder);

        $grantNumbers = $bodyParams['funderGrants'];
        foreach ($grantNumbers as $grantNumber) {
            $funderAward = $funderAwardDao->newDataObject();
			$funderAward->setAllData([
                'funderId' => $funderId,
                'funderAwardNumber' => $grantNumber
            ]);
			$funderAwardDao->insertObject($funderAward);
        }

        return $response->withStatus(200);
    }

    public function deleteFunder($slimRequest, $response, $args) {
        $funderId = $args['funderId'];
        $funderDao = DAORegistry::getDAO('FunderDAO');
        
        $funder = $funderDao->getById($funderId);
        if (!$funder) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $funderDao->deleteObject($funder);

        return $response->withStatus(200);
    }

    private function sendHttpRequest($url, $method = 'GET') {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $result = json_decode($response, true);

        if (curl_errno($ch)) {
            throw new \Exception('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }

    private function getSubsidiaryOption($subsidiaryId, $hierarchyNames) {
        $optionLabel = '';
        
        foreach ($hierarchyNames as $id => $name) {
            if ($id == $subsidiaryId) {
                $optionLabel = $name;
                break;
            }
        }

        return [
            'label' => $optionLabel,
            'value' => $optionLabel . ' [https://doi.org/10.13039/' . $subsidiaryId . ']'
        ];
    }

    private function getFunderNameAndIdentification($funderNameIdentification, $subOrganizationNameIdentification) {
        $funderName = '';
		$funderIdentification = '';
		
        if ($funderNameIdentification != ''){
			$funderName = trim(preg_replace('/\s*\[.*?\]\s*/ ', '', $funderNameIdentification));
			if (preg_match('/\[(.*?)\]/', $funderNameIdentification, $output)) {
				$funderIdentification = $output[1];
				if ($subOrganizationNameIdentification != ''){
					$funderName = trim(preg_replace('/\s*\[.*?\]\s*/ ', '', $subOrganizationNameIdentification	));
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

        return [$funderName, $funderIdentification];
    }
}