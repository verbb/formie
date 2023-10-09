<?php
namespace verbb\formie\migrations;

use Craft;
use craft\db\Migration;

use Throwable;

class m231010_000000_security_key extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $configService = Craft::$app->getConfig();

        try {
            // Create a new security key for Formie, indepedant of Craft's for encrypted field values
            $configService->setDotEnvVar('FORMIE_SECURITY_KEY', $configService->getGeneral()->securityKey);
        } catch (Throwable $e) {
            // Ignoring for now is fine
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m231010_000000_security_key cannot be reverted.\n";
        return false;
    }
}
