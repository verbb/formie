<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\models\Settings;

use Craft;
use craft\db\Query;
use craft\helpers\Console;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use craft\web\UploadedFile;

use yii\helpers\Markdown;
use yii\web\HttpException;
use yii\web\Response;

use stdClass;

class ImportExportController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex($importError = null, $exportError = null): ?Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/import-export', compact('settings', 'importError', 'exportError'));
    }

    public function actionImport(): ?Response
    {
        $request = Craft::$app->getRequest();
        $uploadedFile = UploadedFile::getInstanceByName('file');

        if (!$uploadedFile) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'An error occurred.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'importError' => Craft::t('formie', 'You must upload a file.'),
            ]);

            return null;
        }

        $filename = 'formie-import-' . gmdate('ymd_His') . '.json';
        $fileLocation = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;

        move_uploaded_file($uploadedFile->tempName, $fileLocation);

        $object = new stdClass();
        $object->filename = $filename;

        return $this->redirectToPostedUrl($object);
    }

    public function actionImportConfigure($filename): ?Response
    {
        $request = Craft::$app->getRequest();

        $fileLocation = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($fileLocation)) {
            throw new HttpException(404);
        }

        $json = Json::decode(file_get_contents($fileLocation));

        // Find an existing form with the same handle
        $existingForm = null;
        $formHandle = $json['handle'] ?? null;

        if ($formHandle) {
            $existingForm = Formie::$plugin->getForms()->getFormByHandle($formHandle);
        }

        ob_start();
        $this->stdout("Form: Preparing to import form “{$json['title']}”.");
        $this->stdout("    > Form title is “{$json['title']}”.", Console::FG_GREEN);
        $this->stdout("    > Form handle is “{$json['handle']}”.", ($existingForm ? Console::FG_RED : Console::FG_GREEN));

        $pageCount = Craft::t('app', '{num, number} {num, plural, =1{page} other{pages}}', ['num' => count($json['pages'])]);
        $this->stdout("    > Form contains {$pageCount}.", Console::FG_GREEN);

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
        $this->stdout("    > Form contains {$fieldCount}.", Console::FG_GREEN);

        foreach ($formFields as $field) {
            $type = explode('\\', $field['type']);
            $type = array_pop($type);

            $this->stdout("        > {$type}: “{$field['label']}” `({$field['handle']})`.", Console::FG_GREEN);
        }

        $notificationCount = Craft::t('app', '{num, number} {num, plural, =1{notification} other{notifications}}', ['num' => count($json['notifications'])]);

        if (count($json['notifications'])) {
            $this->stdout("Notifications: Preparing to import {$notificationCount}.");

            foreach ($json['notifications'] as $notification) {
                $this->stdout("    > “{$notification['name']}”.", Console::FG_GREEN);
            }
        }

        $summary = ob_get_clean();

        $variables = compact('filename', 'summary', 'json', 'existingForm');
        $variables = array_merge($variables, Craft::$app->getUrlManager()->getRouteParams());

        return $this->renderTemplate('formie/settings/import-export/import-configure', $variables);
    }

    public function actionImportComplete(): ?Response
    {
        $request = Craft::$app->getRequest();
        $filename = $request->getParam('filename');
        $formAction = $request->getParam('formAction');

        $fileLocation = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($fileLocation)) {
            throw new HttpException(404);
        }

        $json = Json::decode(file_get_contents($fileLocation));

        // Find an existing form with the same handle
        $existingForm = null;
        $formHandle = $json['handle'] ?? null;

        if ($formHandle) {
            $existingForm = Formie::$plugin->getForms()->getFormByHandle($formHandle);
        }

        // When creating a new form, change the handle
        if ($formAction === 'create') {
            $formHandles = (new Query())
                ->select(['handle'])
                ->from('{{%formie_forms}}')
                ->column();

            $json['handle'] = HandleHelper::getUniqueHandle($formHandles, $json['handle']);
        }

        if ($formAction === 'update') {
            // Update the form (force)
            $form = ImportExportHelper::createFormFromImport($json, $existingForm);
        } else {
            // Create the form element, ready to go
            $form = ImportExportHelper::createFormFromImport($json);
        }

        if (!Formie::$plugin->getForms()->saveForm($form)) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'Unable to import form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
                'errors' => $form->getErrors(),
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Form imported.'));

        return $this->redirectToPostedUrl($form);
    }

    public function actionImportCompleted($formId): Response
    {
        $form = Formie::$plugin->getForms()->getFormById($formId);

        return $this->renderTemplate('formie/settings/import-export/import-completed', compact('form'));
    }

    public function actionExport(): void
    {
        $request = Craft::$app->getRequest();
        $formId = $request->getRequiredParam('formId');

        if (!$formId) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'An error occurred.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'exportError' => Craft::t('formie', 'You must select a form.'),
            ]);

            return;
        }

        $formElement = Formie::$plugin->getForms()->getFormById($formId);

        $data = ImportExportHelper::generateFormExport($formElement);
        $json = Json::encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);

        Craft::$app->getResponse()->sendContentAsFile($json, 'formie-' . $formElement->handle . '-' . StringHelper::UUID() . '.json');

        Craft::$app->end();
    }


    // Private Methods
    // =========================================================================

    private function stdout($string, $color = ''): void
    {
        $class = '';

        if ($color) {
            $class = 'color-' . $color;
        }

        echo '<div class="log-label ' . $class . '">' . Markdown::processParagraph($string) . '</div>';
    }
}
