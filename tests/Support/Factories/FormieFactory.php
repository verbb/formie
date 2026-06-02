<?php

declare(strict_types=1);

namespace Tests\Support\Factories;

use verbb\formie\factories\FormFactory as PluginFormFactory;
use verbb\formie\factories\SubmissionFactory as PluginSubmissionFactory;
use verbb\formie\Formie;
use verbb\formie\elements\Form;

final class FormieFactory
{
    public static function make(): self
    {
        return new self();
    }

    public function form(array $config = []): PluginFormFactory
    {
        return Formie::$plugin->getFactories()->form($config);
    }

    public function conditionForms(): ConditionFormFactory
    {
        return ConditionFormFactory::make();
    }

    public function submission(Form $form): PluginSubmissionFactory
    {
        return Formie::$plugin->getFactories()->submission($form);
    }
}
