<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\models\EmailTemplate;
use verbb\formie\models\FormSettings;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;
use verbb\formie\models\Settings;
use verbb\formie\records\EmailTemplate as EmailTemplateRecord;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\records\FormTemplate as FormTemplateRecord;
use verbb\formie\records\Notification as NotificationRecord;
use verbb\formie\records\PdfTemplate as PdfTemplateRecord;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use craft\web\UploadedFile;

use yii\helpers\Markdown;
use yii\web\HttpException;
use yii\web\Response;

class ImportExportController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function actionIndex($importError = null, $exportError = null)
    {
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/import-export', compact('settings', 'importError', 'exportError'));
    }

    /**
     * @inheritdoc
     */
    public function actionImport()
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

        return $this->redirectToPostedUrl(['filename' => $filename]);
    }

    /**
     * @inheritdoc
     */
    public function actionImportConfigure($filename)
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

        $fields = [];

        foreach ($json['pages'] as $page) {
            foreach ($page['rows'] as $row) {
                foreach ($row['fields'] as $field) {
                    $fields[] = $field;
                }
            }
        }

        $fieldCount = Craft::t('app', '{num, number} {num, plural, =1{field} other{fields}}', ['num' => count($fields)]);
        $this->stdout("    > Form contains {$fieldCount}.", Console::FG_GREEN);

        foreach ($fields as $field) {
            $type = explode('\\', $field['type']);
            $type = array_pop($type);

            $this->stdout("        > {$type}: “{$field['label']}” `({$field['handle']})`.", Console::FG_GREEN);
        }

        $notificationCount = Craft::t('app', '{num, number} {num, plural, =1{notification} other{notifications}}', ['num' => count($json['notifications'])]);
        $this->stdout("Notifications: Preparing to import {$notificationCount}.");

        foreach ($json['notifications'] as $notification) {
            $this->stdout("    > “{$notification['name']}”.", Console::FG_GREEN);
        }

        $summary = ob_get_clean();

        $variables = compact('filename', 'summary', 'json', 'existingForm');
        $variables = array_merge($variables, Craft::$app->getUrlManager()->getRouteParams());

        return $this->renderTemplate('formie/settings/import-export/import-configure', $variables);
    }

    /**
     * @inheritdoc
     */
    public function actionImportComplete()
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
            $form = $this->createFormFromImport($json, $existingForm);
        } else {
            // Create the form element, ready to go
            $form = $this->createFormFromImport($json);
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

    /**
     * @inheritdoc
     */
    public function actionImportCompleted($formId)
    {
        $form = Formie::$plugin->getForms()->getFormById($formId);

        return $this->renderTemplate('formie/settings/import-export/import-completed', compact('form'));
    }

    /**
     * @inheritdoc
     */
    public function actionExport()
    {
        $request = Craft::$app->getRequest();
        $formId = $request->getRequiredParam('formId');

        if (!$formId) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'An error occurred.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'exportError' => Craft::t('formie', 'You must select a form.'),
            ]);

            return null;
        }        

        $formElement = Formie::$plugin->getForms()->getFormById($formId);

        $data = $this->generateFormExport($formElement);
        $json = Json::encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);

        Craft::$app->getResponse()->sendContentAsFile($json, 'formie-' . $formElement->handle . '-' . StringHelper::UUID() . '.json');
        
        return Craft::$app->end();
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    private function stdout($string, $color = '')
    {
        $class = '';

        if ($color) {
            $class = 'color-' . $color;
        }

        echo '<div class="log-label ' . $class . '">' . Markdown::processParagraph($string) . '</div>';
    }

    /**
     * @inheritdoc
     */
    private function generateFormExport($formElement)
    {
        $formId = $formElement->id;

        // Get form
        $data = FormRecord::find()
            ->select(['*'])
            ->where(['id' => $formId])
            ->asArray()
            ->one();

        // Remove attributes we won't need
        foreach (['id', 'fieldContentTable', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
            ArrayHelper::remove($data, $key);
        }

        // Add the title for the form
        $data['title'] = $formElement->title;

        // Get form template
        $formTemplateId = ArrayHelper::remove($data, 'templateId');

        if ($formTemplateId) {
            $data['formTemplate'] = FormTemplateRecord::find()
                ->select(['*'])
                ->where(['id' => $formTemplateId])
                ->asArray()
                ->one();

            // Remove attributes we won't need
            foreach (['id', 'fieldLayoutId', 'dateDeleted', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
                ArrayHelper::remove($data['formTemplate'], $key);
            }
        }

        // Get notifications
        $data['notifications'] = NotificationRecord::find()
            ->select(['*'])
            ->where(['formId' => $formId])
            ->asArray()
            ->all();

        // Get email + pdf templates
        foreach ($data['notifications'] as $i => $notification) {
            foreach (['id', 'formId', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
                ArrayHelper::remove($notification, $key);
            }

            // Get templates
            $emailTemplateId = ArrayHelper::remove($notification, 'templateId');
            $pdfTemplateId = ArrayHelper::remove($notification, 'pdfTemplateId');

            if ($emailTemplateId) {
                $notification['emailTemplate'] = EmailTemplateRecord::find()
                    ->select(['*'])
                    ->where(['id' => $emailTemplateId])
                    ->asArray()
                    ->one();

                // Remove attributes we won't need
                foreach (['id', 'dateDeleted', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
                    ArrayHelper::remove($notification['emailTemplate'], $key);
                }
            }

            if ($pdfTemplateId) {
                $notification['pdfTemplate'] = PdfTemplateRecord::find()
                    ->select(['*'])
                    ->where(['id' => $pdfTemplateId])
                    ->asArray()
                    ->one();

                // Remove attributes we won't need
                foreach (['id', 'dateDeleted', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
                    ArrayHelper::remove($notification['pdfTemplate'], $key);
                }
            }

            $data['notifications'][$i] = $notification;
        }

        // Get pages/rows/fields
        $pages = [];

        foreach ($formElement->getPages() as $page) {
            $pageData = $page->toArray();

            // Rename name to label
            $pageData['label'] = ArrayHelper::remove($pageData, 'name');

            // Remove some attributes
            foreach (['id', 'layoutId', 'elements', 'uid'] as $key) {
                ArrayHelper::remove($pageData, $key);
            }

            // Get all rows
            foreach ($page->rows as $rowId => $row) {
                foreach ($row['fields'] as $fieldId => $field) {
                    $settings = array_merge([
                        'instructions' => $field->instructions,
                    ], $field->settings);

                    ArrayHelper::remove($settings, 'formId');

                    $pageData['rows'][$rowId]['fields'][$fieldId] = [
                        'label' => $field->name,
                        'handle' => $field->handle,
                        'type' => $field->type,
                        'settings' => $settings,
                    ];
                }
            }

            $pages[] = $pageData;
        }

        $data['pages'] = $pages;

        return $data;
    }

    /**
     * @inheritdoc
     */
    private function createFormFromImport($data, $form = null)
    {
        if (!$form) {
            $form = new Form();
        }

        // Grab all the extra bits from the export that need to be handles separately
        $settings = ArrayHelper::remove($data, 'settings');
        $pages = ArrayHelper::remove($data, 'pages');
        $formTemplate = ArrayHelper::remove($data, 'formTemplate');
        $notifications = ArrayHelper::remove($data, 'notifications');

        // Handle base form
        $form->setAttributes($data, false);

        // Handle form settings
        $form->settings = new FormSettings();
        $form->settings->setAttributes($settings, false);

        // Handle field layout and pages
        $fieldLayout = Formie::$plugin->getForms()->buildFieldLayout($pages, Form::class);
        $form->setFieldLayout($fieldLayout);

        // Handle for template
        if ($formTemplate) {
            $template = Formie::$plugin->getFormTemplates()->getTemplateByHandle($formTemplate['handle']);

            if (!$template) {
                $template = new FormTemplate();
                $template->setAttributes($formTemplate, false);
            }

            $form->setTemplate($template);
        }

        if ($notifications) {
            $allNotifications = [];

            foreach ($notifications as $notificationData) {
                $emailTemplate = ArrayHelper::remove($notificationData, 'emailTemplate');
                $pdfTemplate = ArrayHelper::remove($notificationData, 'pdfTemplate');

                $notification = new Notification();
                $notification->setAttributes($notificationData, false);

                if ($emailTemplate) {
                    $template = Formie::$plugin->getEmailTemplates()->getTemplateByHandle($emailTemplate['handle']);

                    if (!$template) {
                        $template = new EmailTemplate();
                        $template->setAttributes($emailTemplate, false);
                    }

                    $notification->setTemplate($template);
                }

                if ($pdfTemplate) {
                    $template = Formie::$plugin->getPdfTemplates()->getTemplateByHandle($pdfTemplate['handle']);

                    if (!$template) {
                        $template = new PdfTemplate();
                        $template->setAttributes($pdfTemplate, false);
                    }

                    $notification->setPdfTemplate($template);
                }

                $allNotifications[] = $notification;
            }

            $form->setNotifications($allNotifications);
        }

        return $form;
    }
}
