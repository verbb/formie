<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\web\TemplateResponseFormatter;
use Tests\Support\PermissionTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\ImportExportController;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\services\FormImportFiles;
use verbb\formie\services\Permissions;
use yii\web\ForbiddenHttpException;

beforeEach(function(): void {
    Craft::$app->getProjectConfig()->flush();
    $this->importCompletionTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function(): void {
    $this->importCompletionTransaction->rollBack();
    Craft::$app->getInfo()->configVersion = (new Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
    Craft::$app->getProjectConfig()->reset();
    Formie::$plugin->getForms()->invalidateFormCaches();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
});

function importCompletionRequest(User $actor, callable $callback, string $method = 'POST'): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function($request, $response, $session) use ($actor, $callback): mixed {
        $general = Craft::$app->getConfig()->getGeneral();
        $allowAdminChanges = $general->allowAdminChanges;
        $devMode = $general->devMode;
        $config = Craft::$app->getProjectConfig();
        $readOnly = $config->readOnly;
        try {
            $general->allowAdminChanges = false;
            $general->devMode = false;
            $config->readOnly = true;
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->id($actor->id)->status(null)->one());
            return $callback(new ImportExportController('import-export', Formie::$plugin), $request, $session);
        } finally {
            $general->allowAdminChanges = $allowAdminChanges;
            $general->devMode = $devMode;
            $config->readOnly = $readOnly;
        }
    }, ['method' => $method, 'headers' => ['Accept' => 'text/html']]);
}

it('lands on an accessible page after a production import without widening form visibility', function(bool $canView): void {
    $source = formie()->form(['title' => 'Import completion source'])->singleLineTextField('message')->create();
    $before = (new Query())->from('{{%formie_forms}}')->where(['id' => $source->id])->one();
    $name = 'importCompletion' . bin2hex(random_bytes(5));
    $actor = new User(['username' => $name, 'email' => $name . '@example.test', 'active' => true]);
    expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
    $permissions = ['accessCp', 'accessPlugin-formie', Permissions::PERM_IMPORT_FORMS];
    if ($canView) {
        array_push($permissions, Permissions::PERM_ACCESS_FORMS, Permissions::PERM_VIEW_FORMS);
    }
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, PermissionTestHelper::withParents($permissions)))->toBeTrue();
    $files = new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, json_encode(ImportExportHelper::generateFormExport($source), JSON_THROW_ON_ERROR));
    rewind($stream);
    $filename = $files->store($stream, (int)$actor->id);
    fclose($stream);

    try {
        $beforeIds = (new Query())->select('id')->from('{{%formie_forms}}')->column();
        $result = importCompletionRequest($actor, function($controller, $request, $session) use ($filename): array {
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'filename' => $filename,
                'formAction' => 'create',
                'redirect' => Craft::$app->getSecurity()->hashData('formie/settings/import-export/import-completed/{id}'),
            ]);
            $response = $controller->runAction('import-complete');
            return ['status' => $response->statusCode, 'location' => $response->getHeaders()->get('Location'), 'flashes' => $session->getAllFlashes()];
        });
        $newIds = array_values(array_diff((new Query())->select('id')->from('{{%formie_forms}}')->column(), $beforeIds));
        expect($newIds)->toHaveCount(1);
        $imported = Form::find()->withoutCpIndexScope()->id($newIds[0])->one();
        expect($imported)->not->toBeNull()
            ->and($imported->getFieldByHandle('message'))->not->toBeNull()
            ->and($result['status'])->toBe(302)
            ->and(json_encode($result['flashes']))->toContain('Form imported.')
            ->and(Formie::$plugin->getPermissions()->canViewForm($actor, $imported))->toBe($canView);

        $expectedRoute = 'formie/settings/import-export' . ($canView ? '/import-completed/' . $imported->id : '');
        $query = [];
        parse_str(parse_url($result['location'], PHP_URL_QUERY) ?: '', $query);
        $route = trim($query['p'] ?? parse_url($result['location'], PHP_URL_PATH), '/');
        expect($route)->toBe(Craft::$app->getConfig()->getGeneral()->cpTrigger . '/' . $expectedRoute);

        $html = importCompletionRequest($actor, function($controller) use ($canView, $imported): string {
            $response = $controller->runAction($canView ? 'import-completed' : 'index', $canView ? ['formId' => $imported->id] : []);
            Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
            (new TemplateResponseFormatter())->format($response);
            expect($response->statusCode)->toBe(200);
            return $response->content;
        }, 'GET');
        expect($html)->toContain($canView ? 'Import Completed' : 'Import/Export');

        if (!$canView) {
            importCompletionRequest($actor, function($controller) use ($imported): void {
                expect(fn() => $controller->runAction('import-completed', ['formId' => $imported->id]))->toThrow(ForbiddenHttpException::class);
            }, 'GET');
        }
        expect((new Query())->from('{{%formie_forms}}')->where(['id' => $source->id])->one())->toBe($before);
    } finally {
        $files->delete($filename, (int)$actor->id);
    }
})->with([false, true]);
