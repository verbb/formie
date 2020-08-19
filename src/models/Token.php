<?php
namespace verbb\formie\models;

use verbb\formie\Formie;

use craft\base\Model;

use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth2\Client\Token\AccessToken;

class Token extends Model
{
    // Public Properties
    // =========================================================================

    public $id;
    public $integrationHandle;
    public $accessToken;
    public $secret;
    public $endOfLife;
    public $refreshToken;
    public $dateCreated;
    public $dateUpdated;


    // Public Methods
    // =========================================================================

    public function getIntegration()
    {
        return Formie::$plugin->getIntegrations()->getIntegrationByHandle($this->integrationHandle);
    }

    public function getToken()
    {
        $integration = $this->getIntegration();

        if ($integration) {
            switch ($integration->oauthVersion()) {
                case 1: {
                    $realToken = new TokenCredentials();
                    $realToken->setIdentifier($response['identifier']);
                    $realToken->setSecret($response['secret']);

                    return $realToken;
                }
                case 2: {
                    $realToken = new AccessToken([
                        'access_token' => $this->accessToken,
                        'refresh_token' => $this->refreshToken,
                        'secret' => $this->secret,
                        'expires' => $this->endOfLife,
                    ]);

                    return $realToken;
                }
            }
        }
    }
}
