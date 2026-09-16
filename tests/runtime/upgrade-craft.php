<?php
require __DIR__ . '/upgrade-bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
exit($app->run());
