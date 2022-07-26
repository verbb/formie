<?php
namespace verbb\formie\elements\actions;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\models\FieldLayout;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\StringHelper;

use Throwable;

class DuplicateForm extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public ?string $successMessage = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Duplicate');
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @param ElementQueryInterface $query
     * @param ElementInterface[] $elements
     * @param int[] $duplicatedElementIds
     * @param int $successCount
     * @param int $failCount
     * @param ElementInterface|null $newParent
     */
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
                $form->setAttributes($element->getAttributes(), true);

                $form->id = null;
                $form->uid = null;
                $form->fieldLayoutId = null;
                $form->contentId = null;
                $form->canonicalId = null;
                $form->dateCreated = null;
                $form->dateUpdated = null;
                $form->title = $element->title . ' ' . Craft::t('formie', 'Copy');
                $form->handle = HandleHelper::getUniqueHandle($formHandles, $element->handle);
                $form->settings->setForm($form);

                $pagesData = $element->getFormConfig()['pages'];

                $fieldLayout = Formie::$plugin->getForms()->buildFieldLayout($pagesData, Form::class, true);
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

                $success = Formie::$plugin->getForms()->saveForm($form);

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
