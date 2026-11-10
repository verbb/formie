<?php

if (in_array($path, ['/browser-craft-login', '/browser-craft-login-done'], true)) {
    $app->getView()->setTemplatesPath(__DIR__ . '/templates');
    \verbb\formie\Formie::$plugin->getBrowserAssets()->withPublishedBrowserAssets(fn() => $app->run());
    exit;
}
