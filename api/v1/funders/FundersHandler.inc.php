<?php

use PKP\handler\APIHandler;
use PKP\security\Role;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\db\DAORegistry;

class FundersHandler extends APIHandler
{
    public function __construct()
    {
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

    public function authorize($request, &$args, $roleAssignments)
    {
        $rolePolicy = new PolicySet(PolicySet::COMBINING_PERMIT_OVERRIDES);

        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function getBySubmission($slimRequest, $response, $args)
    {
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

    public function getFundersSuggestions($slimRequest, $response, $args)
    {
        $fundersSuggestions = [['label' => 'Example label', 'value' => 'Example value']];

        return $response->withJson(['items' => $fundersSuggestions], 200);
    }

    public function deleteFunder($slimRequest, $response, $args)
    {
        $funderId = $args['funderId'];
        $funderDao = DAORegistry::getDAO('FunderDAO');
        
        $funder = $funderDao->getById($funderId);
        if (!$funder) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $funderDao->deleteObject($funder);

        return $response->withStatus(200);
    }
}