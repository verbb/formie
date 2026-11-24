<?php

declare(strict_types=1);

use craft\elements\User;
use craft\helpers\FileHelper;
use craft\web\TemplateResponseFormatter;
use Tests\Support\WebRequestTestHelper;
use verbb\base\web\twig\Extension;
use verbb\formie\Formie;
use verbb\formie\controllers\SpamProtectionController;
use verbb\formie\helpers\SpamProtectionSettings as LocalSettings;

/**
 * Renders a Spam Protection screen through its controller while `config/formie.php` holds the given settings.
 */
function renderSpamScreenWithConfigFile(string $action, array $params, array $fileSettings): string
{
    $config = Craft::$app->getConfig();
    $originalDir = $config->configDir;
    $dir = sys_get_temp_dir() . '/formie-spam-config-' . uniqid();
    FileHelper::createDirectory($dir);
    file_put_contents($dir . '/formie.php', '<?php return ' . var_export($fileSettings, true) . ';');

    try {
        $config->configDir = $dir;

        return WebRequestTestHelper::withWebRequestContext(function($request) use ($action, $params): string {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
            Craft::$app->getView()->registerTwigExtension(new Extension());
            $response = (new SpamProtectionController('spam-protection', Formie::$plugin))->runAction($action, $params);
            (new TemplateResponseFormatter())->format($response);

            return html_entity_decode(strip_tags((string)$response->content));
        });
    } finally {
        $config->configDir = $originalDir;
        FileHelper::removeDirectory($dir);
    }
}

beforeEach(function(): void {
    $this->spamConfigTransaction = Craft::$app->getDb()->beginTransaction();
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = true;
    LocalSettings::resetCaches();
});

afterEach(function(): void {
    $this->spamConfigTransaction->rollBack();
    LocalSettings::resetCaches();
});

it('says spam settings in config/formie.php have no effect instead of claiming they override the screen', function(): void {
    $text = renderSpamScreenWithConfigFile('rules', [], [
        'enableHoneypot' => true,
        'spamKeywords' => 'from-config',
        'enableIpSubmissionThrottling' => true,
    ]);

    expect($text)->not->toContain('This is being overridden')
        ->toContain('The enableHoneypot setting in config/formie.php has no effect. Change it here instead.')
        ->toContain('The spamKeywords setting in config/formie.php has no effect. Change it here instead.')
        ->toContain('The enableIpSubmissionThrottling setting in config/formie.php has no effect. Change it here instead.')
        ->not->toContain('The saveSpam setting in config/formie.php');
});
