<?php

if ($path === '/browser-upload-reset' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $form = \verbb\formie\elements\Form::find()->handle(($_GET['mode'] ?? '') === 'simple' ? 'uploadResetSimple' : 'uploadResetAdvanced')->one();
    $view = $app->getView();
    $view->setTemplateMode(\craft\web\View::TEMPLATE_MODE_SITE);
    $html = \verbb\formie\Formie::$plugin->getBrowserAssets()->withPublishedBrowserAssets(function () use ($view, $form) {
        ob_start();
        $view->beginPage();
        echo '<!doctype html><html lang="en"><head><title>Upload reset</title>';
        $view->head();
        echo '</head><body>';
        $view->beginBody();
        echo $view->renderString('{{ craft.formie.renderForm(form) }}', ['form' => $form]);
        $view->endBody();
        echo '</body></html>';
        $view->endPage();
        return ob_get_clean();
    });
    $app->getResponse()->content = $html;
    $app->getResponse()->send();
    exit;
}

if ($path === '/browser-upload-saved' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $formIds = \verbb\formie\elements\Form::find()->handle(['uploadResetAdvanced', 'uploadResetSimple'])->ids();
    $rows = \verbb\formie\elements\Submission::find()->formId($formIds)->status(null)->isIncomplete(false)->isSpam(false)->all();
    $app->getResponse()->format = \yii\web\Response::FORMAT_JSON;
    $app->getResponse()->data = array_map(fn($row) => [
        'id' => $row->id,
        'name' => $row->getFieldValue('visitorName'),
        'confirmation' => $row->getFieldValue('confirmation'),
        'files' => array_map(fn($asset) => ['id' => $asset->id, 'filename' => $asset->filename, 'contents' => $asset->getContents()], $row->getFieldValue('attachment')->all()),
    ], $rows);
    $app->getResponse()->send();
    exit;
}
