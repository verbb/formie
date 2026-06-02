<?php
namespace verbb\formie\services;

use craft\base\Component;
use verbb\formie\elements\Form;
use verbb\formie\factories\FormFactory;
use verbb\formie\factories\SubmissionFactory;

class Factories extends Component
{
    // Public Methods
    // =========================================================================

    public function form(array $config = []): FormFactory
    {
        return new FormFactory($config);
    }

    public function submission(Form $form): SubmissionFactory
    {
        return new SubmissionFactory($form);
    }
}
