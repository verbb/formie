<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

use Closure;
use Throwable;

class SpamProtectionSettings
{
    // Static Methods
    // =========================================================================

    // Serialize settings saves and defer completed events until a whole legacy batch succeeds.
    public static function atomic(Closure $operation): mixed
    {
        if (self::$_depth > 0) {
            return $operation();
        }

        $mutex = Craft::$app->getMutex();
        $name = 'formie:spam-protection';

        if (!$mutex->acquire($name, 10)) {
            throw new BadRequestHttpException(Craft::t('formie', 'Spam protection is being updated. Please try again.'));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        self::$_depth++;

        try {
            $result = $operation();

            if ($result === false) {
                $transaction->rollBack();
                return false;
            }

            $transaction->commit();
            $callbacks = self::$_afterCommit;
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        } finally {
            self::$_depth--;
            self::$_afterCommit = [];
            self::resetCaches();
            $mutex->release($name);
        }

        foreach ($callbacks as $callback) {
            $callback();
        }

        return $result;
    }

    public static function afterCommit(Closure $callback): void
    {
        if (self::$_depth) {
            self::$_afterCommit[] = $callback;
        } else {
            $callback();
        }
    }

    public static function resetCaches(): void
    {
        Formie::$plugin->getCaptchaProviders()->resetCache();
        Formie::$plugin->getSpamProtection()->resetCache();
        Formie::$plugin->getIntegrations()->resetCaptchaCaches();
    }

    public static function record(string $subject): ?array
    {
        [$table, $condition] = self::_location($subject);

        return (new Query())->from($table)->where($condition)->orderBy(['id' => SORT_ASC])->one() ?: null;
    }

    public static function token(string $subject): string
    {
        return self::_hash([$subject, self::record($subject)]);
    }

    public static function assertCurrent(string $subject, string $token): void
    {
        if (!$token || !hash_equals(self::token($subject), $token)) {
            throw new BadRequestHttpException(Craft::t('formie', 'These settings have changed. Reload the page before saving.'));
        }
    }

    public static function requireManager(string $subject): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();
        $permissions = Formie::$plugin->getPermissions();
        $allowed = $subject === self::RULES ? $permissions->canManageSpamRules($user) : $permissions->canManageCaptchas($user);

        if (!$allowed || !$user?->can('accessCp') || !$user?->can('accessPlugin-formie')) {
            throw new ForbiddenHttpException('User is not permitted to manage these spam protection settings.');
        }
    }

    private static function _location(string $subject): array
    {
        if ($subject === self::RULES) {
            return [Table::FORMIE_SPAM_SETTINGS, []];
        }

        if (preg_match('/^captcha:([a-zA-Z0-9_-]+)$/D', $subject, $matches)) {
            return [Table::FORMIE_CAPTCHA_PROVIDERS, ['handle' => $matches[1]]];
        }

        throw new BadRequestHttpException('Invalid spam protection subject.');
    }

    private static function _canonical(mixed $value): mixed
    {
        if (is_array($value)) {
            if (!array_is_list($value)) {
                ksort($value);
            }
            $value = array_map(self::_canonical(...), $value);
        }
        return $value;
    }

    private static function _hash(mixed $value): string
    {
        return hash_hmac('sha256', Json::encode(self::_canonical($value)), Craft::$app->getConfig()->getGeneral()->securityKey);
    }


    // Constants
    // =========================================================================

    public const RULES = 'spam-rules';


    // Properties
    // =========================================================================

    private static int $_depth = 0;
    private static array $_afterCommit = [];
}
