<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Settings;
use verbb\formie\services\FormImportFiles;
use verbb\formie\services\Permissions;

use Craft;
use craft\helpers\Console;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\web\UploadedFile;

use yii\helpers\Markdown;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\Response;

use InvalidArgumentException;
use stdClass;
use Throwable;

class ImportExportController extends SettingsAccessController
{
    // Properties
    // =========================================================================

    protected ?string $settingsPage = 'import-export';


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        match ($action->id) {
            'import', 'import-configure', 'import-complete' => $this->requirePermission(Permissions::PERM_IMPORT_FORMS),
            'export' => $this->requirePermission(Permissions::PERM_EXPORT_FORMS),
            default => null,
        };

        return true;
    }

    public function actionIndex(?string $importError = null, ?string $exportError = null): ?Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/import-export', compact('settings', 'importError', 'exportError'));
    }

    public function actionImport(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;
        $uploadedFile = UploadedFile::getInstanceByName('file');

        if (!$uploadedFile) {
            $this->setFailFlash(Craft::t('formie', 'An error occurred.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'importError' => Craft::t('formie', 'You must upload a file.'),
            ]);

            return null;
        }

        $stream = fopen($uploadedFile->tempName, 'rb');

        if ($stream === false) {
            throw new HttpException(500, Craft::t('formie', 'Unable to read the uploaded form import file.'));
        }

        try {
            $filename = $this->_getImportFiles()->store($stream, $this->_getCurrentUserId());
        } finally {
            fclose($stream);
        }

        $object = new stdClass();
        $object->filename = $filename;

        return $this->redirectToPostedUrl($object);
    }

    public function actionImportConfigure(string $filename): ?Response
    {
        $request = $this->request;

        $json = $this->_readImport($filename);

        // Check if this is multiple forms exports (from Forms index) - just use the one
        if (isset($json[0])) {
            $json = $json[0];
        }

        // Find an existing form with the same handle
        $existingForm = null;
        $formHandle = $json['handle'] ?? null;

        if ($formHandle) {
            $existingForm = Formie::$plugin->getForms()->getFormByHandle($formHandle);
        }

        ob_start();
        $title = Html::encode($json['title'] ?? '');
        $handle = Html::encode($json['handle'] ?? '');
        $this->_stdout("Form: Preparing to import form “{$title}”.");
        $this->_stdout("    > Form title is “{$title}”.", Console::FG_GREEN);
        $this->_stdout("    > Form handle is “{$handle}”.", ($existingForm ? Console::FG_RED : Console::FG_GREEN));

        $pageCount = Craft::t('app', '{num, number} {num, plural, =1{page} other{pages}}', ['num' => count($json['pages'])]);
        $this->_stdout("    > Form contains {$pageCount}.", Console::FG_GREEN);

        $formFields = [];

        $pages = $json['pages'] ?? [];

        foreach ($pages as $page) {
            $rows = $page['rows'] ?? [];

            foreach ($rows as $row) {
                $fields = $row['fields'] ?? [];

                foreach ($fields as $field) {
                    $formFields[] = $field;
                }
            }
        }

        $fieldCount = Craft::t('app', '{num, number} {num, plural, =1{field} other{fields}}', ['num' => count($formFields)]);
        $this->_stdout("    > Form contains {$fieldCount}.", Console::FG_GREEN);

        foreach ($formFields as $field) {
            $type = explode('\\', $field['type']);
            $type = array_pop($type);

            // Handle Formie v2 exports
            $label = Html::encode($field['label'] ?? $field['settings']['label'] ?? '');
            $handle = Html::encode($field['handle'] ?? $field['settings']['handle'] ?? '');

            $this->_stdout("        > {$type}: “{$label}” `({$handle})`.", Console::FG_GREEN);
        }

        $notificationCount = Craft::t('app', '{num, number} {num, plural, =1{notification} other{notifications}}', ['num' => count($json['notifications'])]);

        if (count($json['notifications'])) {
            $this->_stdout("Notifications: Preparing to import {$notificationCount}.");

            foreach ($json['notifications'] as $notification) {
                $name = Html::encode($notification['name'] ?? '');
                $this->_stdout("    > “{$name}”.", Console::FG_GREEN);
            }
        }

        $plan = ImportExportHelper::planImport($json, $existingForm);

        foreach ($plan['warnings'] as $warning) {
            $this->_stdout(Html::encode($warning), Console::FG_YELLOW);
        }

        foreach ($plan['dependencies'] as $dependency) {
            $this->_stdout(Html::encode($dependency['action'] . ': ' . $dependency['kind'] . ' resource ' . $dependency['handle']));
        }

        foreach ($plan['changes'] as $action => $references) {
            $this->_stdout(Html::encode(ucfirst($action) . ': ' . implode(', ', $references)));
        }
        $summary = ob_get_clean();

        $variables = compact('filename', 'summary', 'json', 'existingForm');
        $variables = array_merge($variables, Craft::$app->getUrlManager()->getRouteParams());

        return $this->renderTemplate('formie/settings/import-export/import-configure', $variables);
    }

    public function actionImportComplete(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;
        $filename = $request->getParam('filename');
        $formAction = $request->getParam('formAction');

        $json = $this->_readImport($filename);

        $form = ImportExportHelper::importFormFromJson($json, $formAction);

        // check for errors
        if ($form->getErrors()) {
            $this->setFailFlash(Craft::t('formie', 'Unable to import form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
                'errors' => $form->getErrors(),
            ]);

            return null;
        }

        try {
            $this->_getImportFiles()->delete($filename, $this->_getCurrentUserId());
        } catch (Throwable $e) {
            // The form is already imported, so failed temporary-file cleanup is non-fatal.
            Formie::warning('Unable to delete completed form import file: {message}', [
                'message' => $e->getMessage(),
            ]);
        }

        $this->setSuccessFlash(Craft::t('formie', 'Form imported.'));

        return $this->redirectToPostedUrl($form);
    }

    public function actionImportCompleted(?int $formId): Response
    {
        $form = Formie::$plugin->getForms()->getFormById($formId);

        return $this->renderTemplate('formie/settings/import-export/import-completed', compact('form'));
    }

    public function actionExport(): void
    {
        $request = $this->request;
        $formId = $request->getRequiredParam('formId');

        if (!$formId) {
            $this->setFailFlash(Craft::t('formie', 'An error occurred.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'exportError' => Craft::t('formie', 'You must select a form.'),
            ]);

            return;
        }

        $formElement = Formie::$plugin->getForms()->getFormById($formId);

        $data = ImportExportHelper::generateFormExport($formElement);
        $json = Json::encode($data, JSON_PRETTY_PRINT);

        Craft::$app->getResponse()->sendContentAsFile($json, 'formie-' . $formElement->handle . '-' . StringHelper::UUID() . '.json');

        Craft::$app->end();
    }


    // Private Methods
    // =========================================================================

    private function _stdout(string $string, string $color = ''): void
    {
        $class = '';

        if ($color) {
            $class = 'color-' . $color;
        }

        echo '<div class="log-label ' . $class . '">' . Markdown::processParagraph($string) . '</div>';
    }

    private function _getImportFiles(): FormImportFiles
    {
        return new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    }

    private function _getCurrentUserId(): int
    {
        return (int)Craft::$app->getUser()->getId();
    }

    private function _readImport(mixed $filename): mixed
    {
        try {
            $contents = $this->_getImportFiles()->read($filename, $this->_getCurrentUserId());
        } catch (InvalidArgumentException) {
            throw new BadRequestHttpException('Invalid import filename.');
        }

        if ($contents === null) {
            throw new HttpException(404, Craft::t('formie', 'This form import has expired or is no longer available. Upload the JSON file again.'));
        }

        return Json::decode($contents);
    }
}
