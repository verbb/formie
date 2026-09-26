<?php
namespace verbb\formie\errors;

use verbb\formie\models\IntegrationResult;

use Throwable;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class IntegrationStepException extends RequestException
{
    // Properties
    // =========================================================================

    public readonly IntegrationResult $result;


    // Public Methods
    // =========================================================================

    public function __construct(IntegrationResult $result, ?Throwable $previous = null, ?ResponseInterface $response = null)
    {
        $this->result = $result;
        parent::__construct('Integration operation ' . $result->status->value . ': ' . $result->code, $previous instanceof RequestException ? $previous->getRequest() : new Request('POST', 'https://delivery.invalid'), $response ?? ($previous instanceof RequestException ? $previous->getResponse() : null), $previous);
    }
}
