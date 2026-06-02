<?php
namespace verbb\formie\controllers;

use verbb\formie\helpers\CrossOriginRequestHelper;

use yii\web\Response;

trait CrossOriginRequestTrait
{
    // Protected Methods
    // =========================================================================

    protected function handleCrossOriginRequest(array|string $allowedMethods = ['POST', 'OPTIONS']): ?Response
    {
        CrossOriginRequestHelper::applyHeaders($this->request, $this->response, $allowedMethods);

        if ($this->request->getIsOptions()) {
            $this->response->format = Response::FORMAT_RAW;
            $this->response->data = '';

            return $this->response;
        }

        return null;
    }
}
