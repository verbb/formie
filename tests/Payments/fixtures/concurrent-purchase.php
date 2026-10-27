<?php
require dirname(__DIR__, 2) . '/runtime/bootstrap.php';

function craft_modify_app_config(array &$config, string $appType): void
{
    global $argv;
    // Keep payment persistence shared while isolating Craft's compiled-class cleanup.
    $config['runtimePath'] = $argv[3] . '-runtime/' . getmypid();
}

$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
require_once dirname(__DIR__, 2) . '/Support/ConcurrentPaymentIntegration.php';

$integration = \verbb\formie\Formie::$plugin->getIntegrations()->getIntegrationById((int)$argv[1]);
$submission = \verbb\formie\Formie::$plugin->getSubmissions()->getSubmissionById((int)$argv[2]);
$integration->setField($submission->getForm()->getFieldByHandle('payment'));
$integration->configureCapture($argv[3], ($argv[4] ?? '') === 'interrupt');
echo $integration->processPayment($submission)->status->value;
