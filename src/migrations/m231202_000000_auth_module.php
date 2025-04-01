<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

use verbb\auth\Auth;

class m231202_000000_auth_module extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        Auth::getInstance()->migrator->up();

        // Migrate Formie tokens to Auth tokens
        $tokens = (new Query())
            ->select(['*'])
            ->from(['{{%formie_tokens}}'])
            ->all();
        
        $integrationIdMap = (new Query())
            ->select(['tokenId', 'id'])
            ->from([Table::FORMIE_INTEGRATIONS])
            ->where(['not', ['tokenId' => null]])
            ->pairs();

        foreach ($tokens as $token) {
            // Get the integration's ID from the token ID
            $tokenId = $integrationIdMap[$token['id']] ?? null;

            if ($tokenId) {
                $newToken = [
                    'ownerHandle' => 'formie',
                    'providerType' => $token['type'],
                    'tokenType' => 'oauth2',
                    'reference' => (string)$tokenId,
                    'accessToken' => $token['accessToken'],
                    'secret' => $token['secret'],
                    'expires' => $token['endOfLife'],
                    'refreshToken' => $token['refreshToken'],
                    'dateCreated' => $token['dateCreated'],
                    'dateUpdated' => $token['dateUpdated'],
                    'uid' => $token['uid'],
                ];

                $existingToken = (new Query())
                    ->select(['*'])
                    ->from(['{{%auth_oauth_tokens}}'])
                    ->where($newToken)
                    ->all();

                if (!$existingToken) {
                    $this->insert('{{%auth_oauth_tokens}}', $newToken);
                }
            }
        }

        $this->dropTableIfExists('{{%formie_tokens}}');

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231202_000000_auth_module cannot be reverted.\n";

        return false;
    }
}
