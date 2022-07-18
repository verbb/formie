<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields\formfields;
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

use craft\elements\Entry;
use craft\helpers\ArrayHelper;
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

            // Get all field settings for all pages/rows (supports nested fields)
            self::getFieldInfoForExport($page->rows, $pageData);

            $pages[] = $pageData;
        }

        $data['pages'] = $pages;

        // Also save any custom fields' content
        if ($fieldLayout = $formElement->getFieldLayout()) {
            foreach ($formElement->getFieldLayout()->getCustomFields() as $customField) {
                $fieldValue = $formElement->getFieldValue($customField->handle);
                $data['customFields'][$customField->handle] = $customField->serializeValue($fieldValue, $formElement);
            }
        }

        return $data;
    }

    public static function createFormFromImport($data, $form = null): Form
    {
        $existingForm = $form;
        $existingFormFields = [];

        // Store the fields on an existing form, so we can retain their IDs later
        if ($existingForm) {
            $existingFormFields = ArrayHelper::index($existingForm->getCustomFields(), 'handle');

            // Handle any nested fields from Group/Repeater. Save them as `repeaterHandle_fields`.
            foreach ($existingFormFields as $existingFormField) {
                if ($existingFormField instanceof NestedFieldInterface) {
                    $existingFormFields[$existingFormField->handle . '_fields'] = ArrayHelper::index($existingFormField->getCustomFields(), 'handle');
                }
            }
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

        // Check if this is updating an existing form. We want to try and find existing fields
        // and attach the IDs of them to page data, so new fields aren't created (and their submission data lost)
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($existingFormFields as $existingFormField) {
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
                        // Handle Group/Repeater to do the same, but slightly different
                        $nestedRows = $field['rows'] ?? [];

                        foreach ($nestedRows as $nestedRowKey => $nestedRow) {
                            $nestedFields = $nestedRow['fields'] ?? [];

                            foreach ($nestedFields as $nestedFieldKey => $nestedField) {
                                $existingNestedField = $existingFormFields[$field['handle'] . '_fields'][$nestedField['handle']] ?? null;

                                if ($existingNestedField) {
                                    $pages[$pageKey]['rows'][$rowKey]['fields'][$fieldKey]['rows'][$nestedRowKey]['fields'][$nestedFieldKey]['id'] = $existingNestedField->id;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Ensure we're not adding a field type that doesn't exist or isn't supported here
        self::filterUnsupportedFields($pages);

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


    // Private Methods
    // =========================================================================

    private static function getFieldInfoForExport($rows, &$pageData): void
    {
        foreach ($rows as $rowId => $row) {
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

                // Handle nested fields
                if ($field instanceof NestedFieldInterface) {
                    self::getFieldInfoForExport($field->getNestedRows(), $pageData['rows'][$rowId]['fields'][$fieldId]);
                }
            }
        }
    }

    private static function filterUnsupportedFields(&$pages): void
    {
        foreach ($pages as $pageKey => $page) {
            $rows = $page['rows'] ?? [];

            foreach ($rows as $rowKey => $row) {
                $fields = $row['fields'] ?? [];

                foreach ($fields as $fieldKey => $field) {
                    $type = $field['type'] ?? '';

                    // This will throw an error for Commerce, where the extended class doesn't exist.
                    // Which unfortunately means we can't use `class_exists()` because it's the extended
                    // class that doesn't exist, and that can't be caught for some reason.
                    if (in_array($type, [formfields\Products::class, formfields\Variants::class]) && !Formie::$plugin->getService()->isPluginInstalledAndEnabled('commerce')) {
                        unset($pages[$pageKey]['rows'][$rowKey]['fields'][$fieldKey]);
                    } else if (!class_exists($type)) {
                        // Check if the class doesn't exist
                        unset($pages[$pageKey]['rows'][$rowKey]['fields'][$fieldKey]);
                    }

                    // Check for nested fields
                    $nestedRows = $field['rows'] ?? [];

                    if ($nestedRows) {
                        // Create a new variable, so we can use our recursive function
                        $nestedPages = [$nestedRows];

                        self::filterUnsupportedFields($nestedPages);

                        $pages[$pageKey]['rows'][$rowKey]['fields'][$fieldKey]['rows'] = $nestedPages[0];
                    }
                }

                // Cleanup any isolated fields
                $pages[$pageKey]['rows'][$rowKey] = array_filter($pages[$pageKey]['rows'][$rowKey]);
                $pages[$pageKey]['rows'] = array_filter($pages[$pageKey]['rows']);
            }
        }
    }
}
