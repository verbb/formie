<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\Assets;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Settings;

use Craft;
use craft\db\Query;
use craft\helpers\Console;
use craft\helpers\Html;
use craft\helpers\Json;
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

    public function actionIndex(?string $importError = null, ?string $exportError = null): ?Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/import-export', compact('settings', 'importError', 'exportError'));
    }

    public function actionImport(): ?Response
    {
        $request = $this->request;
        $uploadedFile = UploadedFile::getInstanceByName('file');

        if (!$uploadedFile) {
            $this->setFailFlash(Craft::t('formie', 'An error occurred.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'importError' => Craft::t('formie', 'You must upload a file.'),
            ]);

            return null;
        }

        $filename = 'formie-import-' . gmdate('ymd_His') . '.json';
        $fileLocation = Assets::getTempPath($filename);

        move_uploaded_file($uploadedFile->tempName, $fileLocation);

        $object = new stdClass();
        $object->filename = $filename;

        return $this->redirectToPostedUrl($object);
    }

    public function actionImportConfigure(string $filename): ?Response
    {
        $request = $this->request;

        $fileLocation = Assets::getTempPath($filename);

        if (!file_exists($fileLocation)) {
            throw new HttpException(404);
        }

        $json = Json::decode(file_get_contents($fileLocation));

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

            // Handle Formie v2 exports
            $label = Html::encode($field['label'] ?? $field['settings']['label'] ?? '');
            $handle = Html::encode($field['handle'] ?? $field['settings']['handle'] ?? '');

            $this->stdout("        > {$type}: “{$label}” `({$handle})`.", Console::FG_GREEN);
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
        $request = $this->request;
        $filename = $request->getParam('filename');
        $formAction = $request->getParam('formAction');

        $fileLocation = Assets::getTempPath($filename);

        if (!file_exists($fileLocation)) {
            throw new HttpException(404);
        }

        $json = Json::decode(file_get_contents($fileLocation));
   
        $form = ImportExportHelper::importFormFromJson($json, $formAction);

        // check for errors
        if( $form->getConsolidatedErrors() ){

            $this->setFailFlash(Craft::t('formie', 'Unable to import form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
                'errors' => $form->getConsolidatedErrors(),
            ]);

            return null;
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

    private function stdout(string $string, string $color = ''): void
    {
        $class = '';

        if ($color) {
            $class = 'color-' . $color;
        }

        echo '<div class="log-label ' . $class . '">' . Markdown::processParagraph($string) . '</div>';
    }
}
