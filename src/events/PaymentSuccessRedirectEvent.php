<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\Payment;

use yii\base\Event;

class PaymentSuccessRedirectEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Payment $payment = null;
    public ?Submission $submission = null;
    public ?Form $form = null;
    public string $redirectUrl = '';
}
