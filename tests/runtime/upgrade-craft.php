<?php
require __DIR__ . '/upgrade-bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
if (($argv[1] ?? '') === 'migrate/all' && str_starts_with(\verbb\formie\Formie::$plugin->version, '4.')) {
    // Reproduce custom modules that register the supported legacy status event owner.
    \yii\base\Event::on('verbb\\formie\\services\\Statuses', 'afterSaveStatus', static function(): void {});
    $app->getDeprecator()->throwExceptions = true;
}
exit($app->run());
