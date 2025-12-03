<?php

use PKP\components\forms\FormComponent;
use PKP\components\forms\FieldRadioInput;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FieldControlledVocab;
use APP\core\Application;

class FunderForm extends FormComponent
{
    public $id = 'funder';
    public $method = 'POST';

    public function __construct($action, $submission)
    {
        $this->action = $action;

        $this->addField(new FieldRadioInput('funderNameIdentification', [
            'label' => __('plugins.generic.funding.funderName'),
            'description' => __('plugins.generic.funding.funderName.description'),
            'isRequired' => true
        ]))
        ->addField(new FieldSelect('funderSubOrganization', [
            'label' => __('plugins.generic.funding.funderSubOrganization'),
            'description' => __('plugins.generic.funding.funderSubOrganization.select'),
            'showWhen' => ['funderNameIdentification']
        ]))
        ->addField(new FieldControlledVocab('funderGrants', [
            'label' => __('plugins.generic.funding.funderGrants'),
            'apiUrl' => $this->action . '/vocabs',
            'isMultilingual' => false,
            'value' => []
        ]));

        $this->addHiddenField('submissionId', $submission->getId());
    }
}
