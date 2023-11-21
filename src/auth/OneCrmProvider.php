<?php
namespace verbb\formie\auth;

use craft\helpers\Json;

use League\OAuth2\Client\Provider\GenericProvider;

use Psr\Http\Message\RequestInterface;

class OneCrmProvider extends GenericProvider
{
    // Public Methods
    // =========================================================================

    protected function getAccessTokenRequest(array $params): RequestInterface
    {
        $method  = $this->getAccessTokenMethod();
        $url = $this->getAccessTokenUrl($params);

        // 1CRM required the access token request to use JSON, not the traditional `application/x-www-form-urlencoded`
        $options = [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'body' => Json::encode($params),
        ];

        return $this->getRequest($method, $url, $options);
    }
}