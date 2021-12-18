<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\EmailTemplate;
use verbb\formie\models\FormSettings;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;
use verbb\formie\records\EmailTemplate as EmailTemplateRecord;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\records\FormTemplate as FormTemplateRecord;
use verbb\formie\records\Notification as NotificationRecord;
use verbb\formie\records\PdfTemplate as PdfTemplateRecord;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

class ImportExportHelper
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function generateFormExport($formElement)
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
                        'required' => $field->required,
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
    public static function createFormFromImport($data, $form = null)
    {
        $existingForm = $form;
        $existingFormFields = [];

        // Store the fields on an existing form so we can retain their IDs later
        if ($existingForm) {
            $existingFormFields = ArrayHelper::index($existingForm->getFields(), 'handle');
        }

        if (!$form) {
            $form = new Form();
        }

        // Grab all the extra bits from the export that need to be handles separately
        $settings = Json::decodeIfJson(ArrayHelper::remove($data, 'settings'));
        $pages = ArrayHelper::remove($data, 'pages');
        $formTemplate = ArrayHelper::remove($data, 'formTemplate');
        $notifications = ArrayHelper::remove($data, 'notifications');

        // Handle base form
        $form->setAttributes($data, false);

        // Handle form settings
        $form->settings = new FormSettings();
        $form->settings->setAttributes($settings, false);

        // Check if this is updating an existing form. We want to try and find existing fields
        // and attach the IDs of them to page data, so new fields aren't created (and their submission data lost)
        foreach ($existingFormFields as $field) {
            // Try to find the field data in the import, and attach the ID
            foreach ($pages as $pageKey => $page) {
                $rows = $page['rows'] ?? [];

                foreach ($rows as $rowKey => $row) {
                    $fields = $row['fields'] ?? [];

                    foreach ($fields as $fieldKey => $field) {
                        $existingField = $existingFormFields[$field['handle']] ?? null;

                        if ($existingField) {
                            $pages[$pageKey]['rows'][$rowKey]['fields'][$fieldKey]['id'] = $existingField->id;
                        }
                    }
                }
            }
        }

        // Handle field layout and pages
        $fieldLayout = Formie::$plugin->getForms()->buildFieldLayout($pages, Form::class);
        $form->setFormFieldLayout($fieldLayout);

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