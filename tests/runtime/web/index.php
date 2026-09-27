<?php
// Only the explicitly provisioned, owned disposable application serves browser tests.
$runtime = dirname(__DIR__, 3) . '/.cache/verbb-tests';
if (!is_file($runtime . '/browser-enabled.json')) {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/bootstrap.php';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/index.php' && isset($_GET['p'])) { $path = '/' . ltrim((string)$_GET['p'], '/'); }
if (str_starts_with($path, '/cpresources/')) {
    $resources = realpath(CRAFT_WEB_ROOT . '/cpresources');
    $file = realpath(CRAFT_WEB_ROOT . $path);
    if (!$resources || !$file || !str_starts_with($file, $resources . '/') || !is_file($file)) {
        http_response_code(404);
        exit;
    }
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    header('Content-Type: ' . (['js' => 'text/javascript', 'css' => 'text/css', 'svg' => 'image/svg+xml'][$extension] ?? mime_content_type($file)));
    readfile($file);
    exit;
}
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';
$app->edition = \craft\enums\CmsEdition::Pro;
\verbb\formie\Formie::$plugin->getSettings()->allowedOrigins = ['http://localhost:4179'];
if ($path === '/browser-completion-payments') {
    $app->getResponse()->format = \yii\web\Response::FORMAT_JSON;
    $app->getResponse()->data = json_decode(file_get_contents($runtime . '/completion-payments.json'), true);
    $app->getResponse()->send(); exit;
}
// Fixed completion fixtures exercise trusted authoring independently of request data.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && str_starts_with($path, '/browser-completion-native-')) {
    $behavior = substr($path, strlen('/browser-completion-native-'));
    if (!in_array($behavior, ['message', 'redirect', 'reload', 'reset', 'newtab', 'malicious'], true)) { http_response_code(404); exit; }
    $form = \verbb\formie\elements\Form::find()->handle('completionMessage')->one();
    $form->setSettings(['submitMethod' => 'page-reload', 'completionBehavior' => in_array($behavior, ['newtab', 'malicious'], true) ? 'redirect' : $behavior, 'submitActionUrl' => '/browser-completion-done', 'submitActionTab' => $behavior === 'newtab' ? 'new-tab' : 'same-tab']);
    if ($behavior === 'malicious') $form->setRedirectUrl("https://evil.test/%0d%0aInjected");
    $view = $app->getView(); $view->setTemplateMode(\craft\web\View::TEMPLATE_MODE_SITE);
    $html = \verbb\formie\Formie::$plugin->getFrontendAssets()->withPublishedBrowserAssets(function () use ($view, $form) {
        ob_start(); $view->beginPage(); echo '<!doctype html><html><head><title>Native completion</title>'; $view->head();
        echo '</head><body>'; $view->beginBody(); echo $view->renderString('{{ craft.formie.renderForm(form) }}', ['form' => $form]);
        $view->endBody(); echo '</body></html>'; $view->endPage(); return ob_get_clean();
    });
    $app->getResponse()->content = $html; $app->getResponse()->send(); exit;
}
if ($path === '/browser-completion-done') {
    $app->getResponse()->content = '<!doctype html><title>Completed</title><p>Completed</p>';
    $app->getResponse()->send();
    exit;
}
if ($path === '/browser-completion-saved') {
    $ids = \verbb\formie\elements\Form::find()->handle(['completionMessage', 'completionRedirect', 'completionReload', 'completionReset'])->ids();
    $rows = \verbb\formie\elements\Submission::find()->formId($ids)->status(null)->isIncomplete(false)->isSpam(false)->all();
    $app->getResponse()->format = \yii\web\Response::FORMAT_JSON;
    $app->getResponse()->data = array_map(fn($row) => ['name' => $row->getFieldValue('visitorName'), 'note' => $row->getFieldValue('note'), 'date' => $row->getFieldValue('serverDate')], $rows);
    $app->getResponse()->send();
    exit;
}
if ($path === '/browser-completion-instances' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $base = \verbb\formie\elements\Form::find()->handle('completionMessage')->one();
    $view = $app->getView();
    $view->setTemplateMode(\craft\web\View::TEMPLATE_MODE_SITE);
    $html = \verbb\formie\Formie::$plugin->getFrontendAssets()->withPublishedBrowserAssets(function () use ($view, $base) {
        ob_start(); $view->beginPage();
        echo '<!doctype html><html lang="en"><head><title>Two instances</title>'; $view->head();
        echo '</head><body>'; $view->beginBody();
        foreach (['A', 'B'] as $label) {
            $form = clone $base;
            $form->setPageSettings(0, ['showSaveButton' => true]);
            $form->setFieldSettings('visitorName', ['label' => 'Visitor ' . $label]);
            \verbb\formie\Formie::$plugin->getRendering()->populateFormValues($form, ['note' => 'Server ' . $label], true);
            echo $view->renderString('{{ craft.formie.renderForm(form, options) }}', ['form' => $form, 'options' => ['sessionKey' => $label]]);
        }
        $view->endBody(); echo '</body></html>'; $view->endPage(); return ob_get_clean();
    });
    $app->getResponse()->content = $html; $app->getResponse()->send(); exit;
}
// Fixed, read-only parity fixture; never accepts a caller-selected form or setting.
if ($path === '/browser-module-parity') {
    $fixture = json_decode(file_get_contents($runtime . '/browser-enabled.json'), true);
    $form = \verbb\formie\elements\Form::find()->id($fixture['journeyId'])->one();
    $manifest = \verbb\formie\Formie::$plugin->getBrowserModuleManifestBuilder()->buildCanonical($form);
    $bootstrap = \verbb\formie\Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new \verbb\formie\client\models\LoadContext())->toArrayRecursive();
    $app->getResponse()->format = \yii\web\Response::FORMAT_JSON;
    $graphql = $app->getGql()->executeQuery($app->getGql()->getPublicSchema(), '{ formieClientForm(handle: "browserJourney") { contractVersion definition } }');
    $app->getResponse()->data = ['server' => $manifest, 'bootstrap' => $bootstrap['definition']['modules'], 'graphql' => $graphql];
    $app->getResponse()->send();
    exit;
}
if ($path === '/browser-rendered' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $fixture = json_decode(file_get_contents($runtime . '/browser-enabled.json'), true);
    $form = \verbb\formie\elements\Form::find()->id($fixture[($_GET['method'] ?? '') === 'native' ? 'renderedNativeId' : 'renderedId'])->one();
    $cached = isset($_GET['cached']);
    \verbb\formie\Formie::$plugin->getSettings()->staticCacheRefreshOnLoad = $cached;
    $view = $app->getView();
    $view->setTemplateMode(\craft\web\View::TEMPLATE_MODE_SITE);
    // Exercise the assets and registration that a Composer installation ships.
    // Source aliases in the adapter fixture cannot detect stale production bundles.
    $html = \verbb\formie\Formie::$plugin->getFrontendAssets()->withPublishedBrowserAssets(function () use ($view, $form, $cached) {
        ob_start();
        $view->beginPage();
        echo '<!doctype html><html lang="en"><head><title>Rendered form contract</title>';
        $view->head();
        echo '</head><body>';
        $view->beginBody();
        echo $view->renderString('{{ craft.formie.renderForm(form, options) }}', ['form' => $form, 'options' => ['csrfInput' => !$cached]]);
        $view->endBody();
        echo '</body></html>';
        $view->endPage();
        return ob_get_clean();
    });
    // Use Craft's response so the CSRF/session cookies generated while rendering are sent.
    $app->getResponse()->content = $html;
    $app->getResponse()->send();
    exit;
}
if ($path === '/browser-rendered-saved') {
    $fixture = json_decode(file_get_contents($runtime . '/browser-enabled.json'), true);
    $rows = \verbb\formie\elements\Submission::find()->formId([$fixture['renderedId'], $fixture['renderedNativeId']])->status(null)->isIncomplete(false)->isSpam(false)->all();
    header('Content-Type: application/json');
    echo json_encode(array_map(fn($row) => ['name' => $row->getFieldValue('visitorName'), 'details' => (string)$row->getFieldValue('details'), 'total' => (string)$row->getFieldValue('total')], $rows));
    exit;
}
if ($path === '/browser-fixture') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><title>Formie browser contract</title><button id="unmount">Unmount</button><button id="mount">Mount</button><main id="host"></main><script src="/browser-bundle"></script></html>';
    exit;
}
if ($path === '/browser-bundle') {
    header('Content-Type: text/javascript');
    readfile($runtime . '/browser/fixture.js');
    exit;
}
if ($path === '/browser-saved') {
    $fixture = json_decode(file_get_contents($runtime . '/browser-enabled.json'), true);
    $journey = isset($_GET['journey']);
    $rows = \verbb\formie\elements\Submission::find()->formId($fixture[$journey ? 'journeyId' : 'formId'])->status(null)->isIncomplete(false)->isSpam(false)->all();
    header('Content-Type: application/json');
    echo json_encode(array_map(fn($row) => $journey
        ? ['id' => $row->id, 'name' => $row->getFieldValue('visitorName'), 'items' => $row->getFieldValueAsData('items'), 'files' => array_map(fn($asset) => ['filename' => $asset->filename, 'contents' => $asset->getContents()], $row->getFieldValue('attachment')->all())]
        : ['id' => $row->id, 'name' => $row->getFieldValue('visitorName'), 'email' => $row->getFieldValue('visitorEmail')], $rows));
    exit;
}
$app->run();
