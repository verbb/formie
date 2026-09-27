<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;

use yii\base\Event;
use yii\web\Response;

/**
 * Legacy Formie 3 event payload retained for source compatibility only.
 *
 * Formie 4 payment integrations use explicit return, status, session and
 * challenge boundaries rather than a generic callback endpoint.
 *
 * @deprecated in 4.0.0.
 */
class PaymentCallbackEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Integration $integration = null;
    public ?Response $response = null;
}
