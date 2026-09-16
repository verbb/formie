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
        ? ['id' => $row->id, 'name' => $row->getFieldValue('visitorName'), 'items' => $row->getFieldValueAsArray('items'), 'files' => array_map(fn($asset) => ['filename' => $asset->filename, 'contents' => $asset->getContents()], $row->getFieldValue('attachment')->all())]
        : ['id' => $row->id, 'name' => $row->getFieldValue('visitorName'), 'email' => $row->getFieldValue('visitorEmail')], $rows));
    exit;
}
$app->run();
