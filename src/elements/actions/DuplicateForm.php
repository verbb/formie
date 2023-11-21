<?php
namespace verbb\formie\elements\actions;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\models\FormFieldLayout;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\StringHelper;

use Throwable;

use craft\elements\actions\Duplicate;

class DuplicateForm extends Duplicate
{
    // Properties
    // =========================================================================

    public ?string $successMessage = null;


    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Duplicate');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $elements = $query->all();
        $successCount = 0;
        $failCount = 0;

        $this->_duplicateElements($query, $elements, $successCount, $failCount);

        // Did all of them fail?
        if ($successCount === 0) {
            $this->setMessage(Craft::t('app', 'Could not duplicate elements due to validation errors.'));
            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Could not duplicate all elements due to validation errors.'));
        } else {
            $this->setMessage(Craft::t('app', 'Elements duplicated.'));
        }

        return true;
    }

    private function _duplicateElements(ElementQueryInterface $query, array $elements, int &$successCount, int &$failCount): void
    {
        $elementsService = Craft::$app->getElements();

        $formHandles = (new Query())
            ->select(['handle'])
            ->from('{{%formie_forms}}')
            ->column();

        foreach ($elements as $element) {
            // Make sure this element wasn't already duplicated, which could
            // happen if it's the descendant of a previously duplicated element
            // and $this->deep == true.
            if (isset($duplicatedElementIds[$element->id])) {
                continue;
            }

            try {
                $form = new Form();

                // Get all public properties and apply
                Craft::configure($form, get_object_vars($element));

                $form->id = null;
                $form->uid = null;
                $form->fieldLayoutId = null;
                $form->canonicalId = null;
                $form->dateCreated = null;
                $form->dateUpdated = null;
                $form->title = Craft::t('formie', '{title} Copy', ['title' => $element->title]);
                $form->handle = HandleHelper::getUniqueHandle($formHandles, $element->handle);
                $form->settings = clone $element->settings;
                $form->settings->setForm($form);

                // Reset page data IDs
                $pagesData = $element->getFormBuilderConfig()['pages'];

                // Reset page data IDs
                foreach ($pagesData as $pageKey => &$page) {
                    unset($page['id'], $page['errors']);

                    if (isset($page['rows'])) {
                        foreach ($page['rows'] as $rowKey => &$row) {
                            unset($row['id'], $row['errors']);

                            if (isset($row['fields'])) {
                                foreach ($row['fields'] as $fieldKey => &$field) {
                                    unset($field['id'], $field['errors']);

                                    // Handle Group/Repeater to do the same, but slightly different
                                    if (isset($field['settings']['rows'])) {
                                        foreach ($field['settings']['rows'] as $nestedRowKey => &$nestedRow) {
                                            unset($nestedRow['id'], $nestedRow['errors']);

                                            if (isset($nestedRow['fields'])) {
                                                foreach ($nestedRow['fields'] as $nestedFieldKey => &$nestedField) {
                                                    unset($nestedField['id'], $nestedField['errors']);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $fieldLayout = new FormFieldLayout(['pages' => $pagesData]);
                $fieldLayout->id = null;

                $form->setFormFieldLayout($fieldLayout);

                $notifications = [];

                foreach ($element->getNotifications() as $notification) {
                    $newNotification = clone $notification;
                    $newNotification->id = null;
                    $newNotification->formId = null;
                    $newNotification->uid = null;

                    $notifications[] = $newNotification;
                }

                $form->setNotifications($notifications);

                $success = Craft::$app->getElements()->saveElement($form);

                if (!$success) {
                    $failCount++;
                }
            } catch (Throwable) {
                // Validation error
                $failCount++;
                continue;
            }

            $successCount++;
            $duplicatedElementIds[$element->id] = true;
        }
    }
}
