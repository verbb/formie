<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\events\TokenEvent;
use verbb\formie\models\Token;
use verbb\formie\records\Token as TokenRecord;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use yii\base\Component;

use League\OAuth2\Client\Grant\RefreshToken;
use Exception;

class Tokens extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_TOKEN = 'beforeSaveToken';
    public const EVENT_AFTER_SAVE_TOKEN = 'afterSaveToken';
    public const EVENT_BEFORE_DELETE_TOKEN = 'beforeDeleteToken';
    public const EVENT_AFTER_DELETE_TOKEN = 'afterDeleteToken';


    // Properties
    // =========================================================================

    private ?array $_tokensById = null;
    private bool $_fetchedAllTokens = false;


    // Public Methods
    // =========================================================================

    public function getAllTokens(): array
    {
        if ($this->_fetchedAllTokens) {
            return array_values($this->_tokensById);
        }

        $this->_tokensById = [];

        foreach ($this->_createTokenQuery()->all() as $result) {
            $token = $this->_createToken($result);
            $this->_tokensById[$token->id] = $token;
        }

        $this->_fetchedAllTokens = true;

        return array_values($this->_tokensById);
    }

    public function getTokenById(int $id, $refresh = true): ?Token
    {
        $result = $this->_createTokenQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? $this->_createToken($result, $refresh) : null;
    }

    public function saveToken(Token $token, bool $runValidation = true): bool
    {
        $isNewToken = !$token->id;

        // Fire a 'beforeSaveToken' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_TOKEN)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_TOKEN, new TokenEvent([
                'token' => $token,
                'isNew' => $isNewToken,
            ]));
        }

        if ($runValidation && !$token->validate()) {
            Formie::log('Token not saved due to validation error.');
            return false;
        }

        $tokenRecord = $this->_getTokenRecordById($token->id);
        $tokenRecord->type = $token->type;
        $tokenRecord->accessToken = $token->accessToken;
        $tokenRecord->secret = $token->secret;
        $tokenRecord->endOfLife = $token->endOfLife;
        $tokenRecord->refreshToken = $token->refreshToken;

        $tokenRecord->save(false);

        if (!$token->id) {
            $token->id = $tokenRecord->id;
        }

        // Fire an 'afterSaveToken' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_TOKEN)) {
            $this->trigger(self::EVENT_AFTER_SAVE_TOKEN, new TokenEvent([
                'token' => $token,
                'isNew' => $isNewToken,
            ]));
        }

        return true;
    }

    public function deleteTokenById(int $tokenId): bool
    {
        $token = $this->getTokenById($tokenId, false);

        if (!$token) {
            return false;
        }

        return $this->deleteToken($token);
    }

    public function deleteToken(Token $token): bool
    {
        // Fire a 'beforeDeleteToken' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_TOKEN)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_TOKEN, new TokenEvent([
                'token' => $token,
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->delete('{{%formie_tokens}}', ['id' => $token->id])
            ->execute();

        // Fire an 'afterDeleteToken' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_TOKEN)) {
            $this->trigger(self::EVENT_AFTER_DELETE_TOKEN, new TokenEvent([
                'token' => $token,
            ]));
        }

        return true;
    }

    public function refreshToken(Token $token, $force = false): Token
    {
        if ($this->_refreshToken($token, $force)) {
            $this->saveToken($token);
        }

        return $token;
    }


    // Private Methods
    // =========================================================================

    private function _createTokenQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'type',
                'accessToken',
                'secret',
                'endOfLife',
                'refreshToken',
                'dateCreated',
                'dateUpdated',
            ])
            ->from(['{{%formie_tokens}}']);
    }

    private function _getTokenRecordById(?int $tokenId = null): ?TokenRecord
    {
        if ($tokenId !== null) {
            $tokenRecord = TokenRecord::findOne(['id' => $tokenId]);

            if (!$tokenRecord) {
                throw new Exception(Craft::t('formie', 'No token exists with the ID “{id}”.', ['id' => $tokenId]));
            }
        } else {
            $tokenRecord = new TokenRecord();
        }

        return $tokenRecord;
    }

    private function _createToken($config, $refresh = true): Token
    {
        $token = new Token($config);

        // Check if we need to refresh the token
        if ($refresh && $this->_refreshToken($token)) {
            $this->saveToken($token);
        }

        return $token;
    }

    private function _refreshToken(Token $token, $force = false): bool
    {
        $time = time();

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByTokenId($token->id);

        // Refreshing the token only applies to OAuth 2.0 providers
        if ($integration && $integration->oauthVersion() == 2) {
            if (($token->endOfLife && $token->refreshToken) || $force) {
                // Has token expired ?
                if ($time > $token->endOfLife || $force) {
                    $refreshToken = $token->refreshToken;

                    $grant = new RefreshToken();
                    $provider = $integration->getOauthProvider();

                    // Add some logging for refresh tokens for easier debugging
                    $params = $grant->prepareRequestParameters($integration->getOauthProviderConfig(), ['refresh_token' => $refreshToken]);

                    Formie::log($integration->name . ': Refresh token with params ' . Json::encode($params));

                    $newToken = $provider->getAccessToken($grant, ['refresh_token' => $refreshToken]);

                    if ($newToken) {
                        $token->accessToken = $newToken->getToken();
                        $token->endOfLife = $newToken->getExpires();

                        $newRefreshToken = $newToken->getRefreshToken();

                        if (!empty($newRefreshToken)) {
                            $token->refreshToken = $newToken->getRefreshToken();
                        }

                        return true;
                    }
                }
            }
        }

        return false;
    }

}
