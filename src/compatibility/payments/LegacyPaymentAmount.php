<?php
namespace verbb\formie\compatibility\payments;

use verbb\formie\models\PaymentMoney;

/** Formie 3 major-unit access is a projection, never the stored amount. */
trait LegacyPaymentAmount
{
    // Properties
    // =========================================================================

    private ?string $_legacyAmount = null;


    // Public Methods
    // =========================================================================

    public function getAmount(): string
    {
        if (!$this->currency && ($this->_legacyAmount !== null || $this->amountMinor === '0')) {
            return $this->_legacyAmount ?? '0';
        }
        return $this->getMoney()->decimal();
    }

    public function setAmount(string|int|float $amount): void
    {
        if (!$this->currency) {
            $this->_legacyAmount = (string)$amount;
            return;
        }
        $this->amountMinor = PaymentMoney::fromDecimal((string)$amount, $this->currency)->minor;
        $this->_legacyAmount = null;
    }

    public function getMoney(): PaymentMoney
    {
        if ($this->_legacyAmount !== null) {
            $this->setAmount($this->_legacyAmount);
        }
        return PaymentMoney::fromMinor($this->amountMinor, (string)$this->currency);
    }
}
