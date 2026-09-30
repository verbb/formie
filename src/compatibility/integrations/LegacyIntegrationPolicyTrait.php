<?php
namespace verbb\formie\compatibility\integrations;

use verbb\formie\models\FormIntegration;

/** Stable Formie 3 property access delegates to the form binding, not connection settings. */
trait LegacyIntegrationPolicyTrait
{
    // Properties
    // =========================================================================

    protected array $_legacyPolicyOverrides = [];


    // Public Methods
    // =========================================================================

    public function attributes(): array
    {
        return array_unique([...parent::attributes(), 'optInField', 'enableConditions', 'conditions', 'trigger']);
    }

    public function getOptInField(): ?string
    {
        return $this->getFormIntegration()->optInField;
    }

    public function setOptInField(?string $value): void
    {
        $this->_setLegacyPolicy('optInField', $value);
    }

    public function getEnableConditions(): bool
    {
        return $this->getFormIntegration()->enableConditions;
    }

    public function setEnableConditions(bool $value): void
    {
        $this->_setLegacyPolicy('enableConditions', $value);
    }

    public function getConditions(): array
    {
        return $this->getFormIntegration()->conditions;
    }

    public function setConditions(?array $value): void
    {
        $this->_setLegacyPolicy('conditions', $value ?? []);
    }

    public function getTrigger(): array
    {
        return $this->getFormIntegration()->trigger;
    }

    public function setTrigger(array $value): void
    {
        $this->_setLegacyPolicy('trigger', $value);
    }


    // Private Methods
    // =========================================================================

    private function _setLegacyPolicy(string $name, mixed $value): void
    {
        if ($this->_formIntegration) {
            $binding = $this->_formIntegration;
            $this->setFormIntegration(FormIntegration::fromSettings($binding->integration, [$name => $value] + $binding->toSettings(), $binding->formId, $binding->formHandle));
        } else {
            $this->_legacyPolicyOverrides[$name] = $value;
        }
    }
}
