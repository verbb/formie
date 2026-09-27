<?php
namespace verbb\formie\events;

use verbb\formie\models\payments\PaymentWebhookCommand;
use verbb\formie\models\payments\PaymentWebhookReceipt;
use verbb\formie\models\payments\VerifiedWebhookBatch;

use Throwable;

class PaymentWebhookLifecycleEvent extends PaymentWebhookEvent
{
    // Properties
    // =========================================================================

    public ?PaymentWebhookCommand $request = null;
    public ?VerifiedWebhookBatch $verifiedWebhook = null;
    public ?PaymentWebhookReceipt $receipt = null;
    public ?Throwable $error = null;
}
