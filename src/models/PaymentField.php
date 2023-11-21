<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;

use craft\base\ElementInterface;
use craft\helpers\Json;

class PaymentField extends DynamicModel
{
    // Properties
    // =========================================================================

    private ?ElementInterface $_element = null;


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return Json::encode($this->getAttributes());
    }

    public function getElement(): ?ElementInterface
    {
        return $this->_element;
    }

    public function setElement($value): void
    {
        $this->_element = $value;
    }

    public function getPayment()
    {
        if ($submission = $this->getElement()) {
            if ($submission instanceof Submission) {
                if ($payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission)) {
                    $lastPayment = $payments[count($payments) - 1];

                    return $lastPayment->toArray();
                }
            }
        }

        return null;
    }

}
