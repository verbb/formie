<?php

declare(strict_types=1);

namespace verbb\formie\factories;

use Craft;
use RuntimeException;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

final class SubmissionFactory
{
    // Properties
    // =========================================================================

    private Form $form;
    private array $values = [];
    private bool $throwOnFailure = true;


    // Public Methods
    // =========================================================================

    public function __construct(Form $form)
    {
        $this->form = $form;
    }

    public function with(array $values): self
    {
        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function allowValidationFailure(): self
    {
        $this->throwOnFailure = false;

        return $this;
    }

    public function save(): Submission
    {
        $submission = new Submission();
        $submission->setForm($this->form);
        $submission->title = 'Programmatic Submission ' . uniqid();

        foreach ($this->values as $handle => $value) {
            $submission->setFieldValueFromRequest((string)$handle, $value);
        }

        $saved = Craft::$app->elements->saveElement($submission);

        if (!$saved && $this->throwOnFailure) {
            throw new RuntimeException('Failed to save programmatic submission: ' . json_encode($submission->getErrors()));
        }

        return $submission;
    }
}
