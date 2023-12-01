<?php

namespace verbb\formie\elements\actions;

use verbb\formie\Formie;

use Craft;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use verbb\formie\elements\Form;

class SetFormStatus extends SetStatus
{
    // Properties
    // =========================================================================

    public ?string $formStatus = null;
    public array $statuses = [];


    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

    public function getTriggerHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('formie/_components/actions/form-set-status/trigger', [
            'statuses' => $this->statuses,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();

        $elements = $query->all();
        $failCount = 0;

        /** @var Form $element */
        foreach ($elements as $element) {
            if ($element) {
                $element->setFormStatus($this->formStatus);

                if ($elementsService->saveElement($element) === false) {
                    Formie::error('Unable to set status: {error}', ['error' => Json::encode($element->getErrors())]);

                    // Validation error
                    $failCount++;
                }
            }
        }

        // Did all of them fail?
        if ($failCount === count($elements)) {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Could not update status due to a validation error.'));
            } else {
                $this->setMessage(Craft::t('app', 'Could not update statuses due to validation errors.'));
            }

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Status updated, with some failures due to validation errors.'));
        } else if (count($elements) === 1) {
            $this->setMessage(Craft::t('app', 'Status updated.'));
        } else {
            $this->setMessage(Craft::t('app', 'Statuses updated.'));
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        // Don't include the parent rules from `SetStatus`
        $rules = [];

        $rules[] = [['formStatus'], 'required'];
        $rules[] = [['formStatus'], 'in', 'range' => $this->statuses];

        return $rules;
    }
}
