<?php

use PKP\components\forms\FormComponent;
use PKP\components\forms\FieldRadioInput;

class FunderForm extends FormComponent
{
    public $id = 'funder';
    public $method = 'PUT';

    public function __construct($action, $submission)
    {
        $this->action = $action;

        $this->addField(new FieldRadioInput('funderNameIdentification', [
            'label' => __('plugins.generic.funding.funderName'),
            'description' => __('plugins.generic.funding.funderName.description'),
        ]));
    }
}
