<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\models\EmailTemplate;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FormSettings;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;
use verbb\formie\records\EmailTemplate as EmailTemplateRecord;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\records\FormTemplate as FormTemplateRecord;
use verbb\formie\records\Notification as NotificationRecord;
use verbb\formie\records\PdfTemplate as PdfTemplateRecord;

use craft\elements\Entry;
use craft\helpers\Json;

class ImportExportHelper
{
    // Static Methods
    // =========================================================================

    public static function generateFormExport(Form $formElement): array
    {
        $formId = $formElement->id;

        // Get form
        $data = FormRecord::find()
            ->select(['*'])
            ->where(['id' => $formId])
            ->asArray()
            ->one();

        // Remove attributes we won't need
        foreach (['id', 'formFieldLayout', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
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
            foreach (['id', 'dateDeleted', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
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
            foreach (['id', 'formId', 'dateCreated', 'dateUpdated'] as $key) {
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
                foreach (['id', 'dateDeleted', 'dateCreated', 'dateUpdated'] as $key) {
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
                foreach (['id', 'dateDeleted', 'dateCreated', 'dateUpdated'] as $key) {
                    ArrayHelper::remove($notification['pdfTemplate'], $key);
                }
            }

            $data['notifications'][$i] = $notification;
        }

        // Get pages/rows/fields
        $pages = [];

        foreach ($formElement->getPages() as $page) {
            $pageData = $page->toArray();
            $pageData['settings'] = $page->getSettings();

            // Remove some attributes
            foreach (['id', 'formId', 'layoutId', 'sortOrder', 'dateCreated', 'dateUpdated', 'uid'] as $key) {
                ArrayHelper::remove($pageData, $key);
            }

            // Get all field settings for all pages/rows (supports nested fields)
            self::getFieldInfoForExport($page->getRows(), $pageData);

            $pages[] = $pageData;
        }

        $data['pages'] = $pages;

        // Also save any custom fields' content
        if ($fieldLayout = $formElement->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $customField) {
                $fieldValue = $formElement->getFieldValue($customField->handle);
                $data['customFields'][$customField->handle] = $customField->serializeValue($fieldValue, $formElement);
            }
        }

        // Handy to keep track of which version of export logic this is, for importing between systems
        $data['exportVersion'] = 'v3';

        return $data;
    }

    public static function createFormFromImport(array $data, ?Form $form = null): Form
    {
        $existingForm = $form;
        $existingFields = [];

        // Store the fields on an existing form, so we can retain their IDs later
        if ($existingForm) {
            $existingFields = ArrayHelper::index($existingForm->getFields(), 'handle');

            // Handle any nested fields from Group/Repeater. Save them as `repeaterHandle_fields`.
            foreach ($existingFields as $existingField) {
                if ($existingField instanceof NestedFieldInterface) {
                    $existingFields[$existingField->handle . '_fields'] = ArrayHelper::index($existingField->getFields(), 'handle');
                }
            }
        }

        if (!$form) {
            $form = new Form();
        }

        // Grab all the extra bits from the export that need to be handles separately
        $exportVersion = ArrayHelper::remove($data, 'exportVersion');
        $settings = Json::decodeIfJson(ArrayHelper::remove($data, 'settings'));
        $pages = ArrayHelper::remove($data, 'pages');
        $formTemplate = ArrayHelper::remove($data, 'formTemplate');
        $notifications = ArrayHelper::remove($data, 'notifications');

        // Handle Formie v2 exports
        unset($data['fieldLayoutId']);

        // Handle base form
        $form->setAttributes($data, false);

        // Handle any custom field
        $customFields = $data['customFields'] ?? [];

        $form->setFieldValues($customFields);

        // Handle form settings
        $form->settings = new FormSettings();
        $form->settings->setAttributes($settings, false);

        // Check if there is an entry selected as the redirect action. If not found, will cause a fatal error
        if ($form->submitActionEntryId) {
            $entry = Entry::find()->id($form->submitActionEntryId)->one();

            if (!$entry) {
                $form->submitActionEntryId = null;
            }
        }

        // Ensure that the default status exists, just in case there's a project config mismatch
        if ($form->defaultStatusId) {
            $status = Formie::$plugin->getStatuses()->getStatusById($form->defaultStatusId);

            if (!$status) {
                $form->defaultStatusId = Formie::$plugin->getStatuses()->getDefaultStatus()->id;
            }
        }

        // Check if this is updating an existing form. We want to try and find existing fields
        // and attach the IDs of them to page data, so new fields aren't created (and their submission data lost)
        foreach ($existingFields as $existingField) {
            // Try to find the field data in the import, and attach the ID
            foreach ($pages as $pageKey => &$page) {
                if (isset($page['rows'])) {
                    foreach ($page['rows'] as $rowKey => &$row) {
                        if (isset($row['fields'])) {
                            foreach ($row['fields'] as $fieldKey => &$field) {
                                $existingField = $existingFields[$field['handle']] ?? null;

                                if ($existingField) {
                                    $field['id'] = $existingField->id;
                                }
                                
                                // Handle Group/Repeater to do the same, but slightly different
                                if (isset($field['rows'])) {
                                    foreach ($field['rows'] as $nestedRowKey => &$nestedRow) {
                                        if (isset($nestedRow['fields'])) {
                                            foreach ($nestedRow['fields'] as $nestedFieldKey => &$nestedField) {
                                                $existingNestedField = $existingFields[$field['handle'] . '_fields'][$nestedField['handle']] ?? null;

                                                if ($existingNestedField) {
                                                    $nestedField['id'] = $existingNestedField->id;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Ensure the pages/rows/fields are prepped properly
        self::prepFieldsForImport($pages);

        // Handle field layout and pages
        $form->getFormLayout()->setPages($pages);

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


    // Private Methods
    // =========================================================================

    private static function getFieldInfoForExport(array $rows, array &$pageData): void
    {
        foreach ($rows as $rowId => $row) {
            foreach ($row['fields'] as $fieldId => $field) {
                $settings = array_merge([
                    'label' => $field->name,
                    'handle' => $field->handle,
                    'instructions' => $field->instructions,
                    'required' => $field->required,
                ], $field->settings);

                ArrayHelper::remove($settings, 'formId');

                $pageData['rows'][$rowId]['fields'][$fieldId] = [
                    'type' => get_class($field),
                    'settings' => $settings,
                ];

                // Handle nested fields
                if ($field instanceof NestedFieldInterface) {
                    self::getFieldInfoForExport($field->getRows(), $pageData['rows'][$rowId]['fields'][$fieldId]);

                    // Rename `rows` to `rowsConfig`
                    $pageData['rows'][$rowId]['fields'][$fieldId]['settings']['rowsConfig'] = ArrayHelper::remove($pageData['rows'][$rowId]['fields'][$fieldId], 'rows');
                }
            }
        }
    }

    private static function prepFieldsForImport(array &$pages): void
    {
        foreach ($pages as $pageKey => &$page) {
            // Handle Formie v2 exports
            unset($page['userCondition'], $page['elementCondition']);

            if (isset($page['rows'])) {
                foreach ($page['rows'] as $rowKey => &$row) {
                    if (isset($row['fields'])) {
                        foreach ($row['fields'] as $fieldKey => &$field) {
                            $type = $field['type'] ?? '';

                            // Handle Formie v2 exports
                            unset($field['settings']['isNested']);

                            if (isset($field['label'])) {
                                $field['settings']['label'] = $field['label'];
                            }

                            if (isset($field['handle'])) {
                                $field['settings']['handle'] = $field['handle'];
                            }

                            // This will throw an error for Commerce, where the extended class doesn't exist.
                            // Which unfortunately means we can't use `class_exists()` because it's the extended
                            // class that doesn't exist, and that can't be caught for some reason.
                            if (in_array($type, [formfields\Products::class, formfields\Variants::class]) && !Plugin::isPluginInstalledAndEnabled('commerce')) {
                                unset($row['fields'][$fieldKey]);
                            } else if (!class_exists($type)) {
                                // Check if the class doesn't exist
                                unset($row['fields'][$fieldKey]);
                            }

                            // Check for nested fields
                            // Handle Formie v2 exports
                            $nestedRows = $field['rows'] ?? $field['settings']['rows'] ?? [];

                            if ($nestedRows) {
                                // Create a new variable, so we can use our recursive function
                                $nestedPages = [['rows' => $nestedRows]];

                                self::prepFieldsForImport($nestedPages);

                                $field['settings']['rows'] = $nestedPages[0]['rows'];
                            }
                        }
                    }
                }

                // Cleanup any isolated fields
                $page['rows'] = array_filter($page['rows']);
            }
        }
    }
}
