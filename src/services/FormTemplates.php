<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\events\FormTemplateEvent;
use verbb\formie\models\FormTemplate;
use verbb\formie\records\FormTemplate as TemplateRecord;

use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;
use Throwable;

class FormTemplates extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_FORM_TEMPLATE = 'beforeSaveFormTemplate';
    public const EVENT_AFTER_SAVE_FORM_TEMPLATE = 'afterSaveFormTemplate';
    public const EVENT_BEFORE_DELETE_FORM_TEMPLATE = 'beforeDeleteFormTemplate';
    public const EVENT_BEFORE_APPLY_FORM_TEMPLATE_DELETE = 'beforeApplyFormTemplateDelete';
    public const EVENT_AFTER_DELETE_FORM_TEMPLATE = 'afterDeleteFormTemplate';
    public const CONFIG_TEMPLATES_KEY = 'formie.formTemplates';


    // Private Properties
    // =========================================================================

    /**
     * @var FormTemplate[]
     */
    private ?array $_templates = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns all templates.
     *
     * @param bool $withTrashed
     * @return FormTemplate[]
     */
    public function getAllTemplates(bool $withTrashed = false): array
    {
        // Get the caches items if we have them cached, and the request is for non-trashed items
        if ($this->_templates !== null) {
            return $this->_templates;
        }

        $results = $this->_createTemplatesQuery($withTrashed)->all();
        $templates = [];

        foreach ($results as $row) {
            $templates[] = new FormTemplate($row);
        }

        return $templates;
    }

    /**
     * Returns a template identified by its ID.
     *
     * @param int $id
     * @return FormTemplate|null
     */
    public function getTemplateById(int $id): ?FormTemplate
    {
        return ArrayHelper::firstWhere($this->getAllTemplates(), 'id', $id);
    }

    /**
     * Returns a template identified by its handle.
     *
     * @param string $handle
     * @return FormTemplate|null
     */
    public function getTemplateByHandle(string $handle): ?FormTemplate
    {
        return ArrayHelper::firstWhere($this->getAllTemplates(), 'handle', $handle, false);
    }

    /**
     * Returns a template identified by its UID.
     *
     * @param string $uid
     * @return FormTemplate|null
     */
    public function getTemplateByUid(string $uid): ?FormTemplate
    {
        return ArrayHelper::firstWhere($this->getAllTemplates(), 'uid', $uid, false);
    }

    /**
     * Saves templates in a new order by the list of template IDs.
     *
     * @param int[] $ids
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function reorderTemplates(array $ids): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds('{{%formie_formtemplates}}', $ids);

        foreach ($ids as $template => $templateId) {
            if (!empty($uidsByIds[$templateId])) {
                $templateUid = $uidsByIds[$templateId];
                $projectConfig->set(self::CONFIG_TEMPLATES_KEY . '.' . $templateUid . '.sortOrder', $template + 1);
            }
        }

        return true;
    }

    /**
     * Saves the template.
     *
     * @param FormTemplate $template
     * @param bool $runValidation
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function saveTemplate(FormTemplate $template, bool $runValidation = true): bool
    {
        $isNewTemplate = !(bool)$template->id;

        // Fire a 'beforeSaveFormTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FORM_TEMPLATE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_FORM_TEMPLATE, new FormTemplateEvent([
                'template' => $template,
                'isNew' => $isNewTemplate,
            ]));
        }

        if ($runValidation && !$template->validate()) {
            Craft::info('Template not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($isNewTemplate) {
            $templateUid = StringHelper::UUID();
        } else {
            $templateUid = Db::uidById('{{%formie_formtemplates}}', $template->id);
        }

        // Make sure no templates that are not archived share the handle
        $existingTemplate = $this->getTemplateByHandle($template->handle);

        if ($existingTemplate && (!$template->id || $template->id != $existingTemplate->id)) {
            $template->addError('handle', Craft::t('formie', 'That handle is already in use'));
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        if ($template->dateDeleted) {
            $configData = null;
        } else {
            $configData = [
                'name' => $template->name,
                'handle' => $template->handle,
                'template' => $template->template,
                'useCustomTemplates' => $template->useCustomTemplates,
                'outputCssTheme' => $template->outputCssTheme,
                'outputCssLayout' => $template->outputCssLayout,
                'outputJsBase' => $template->outputJsBase,
                'outputJsTheme' => $template->outputJsTheme,
                'outputCssLocation' => $template->outputCssLocation,
                'outputJsLocation' => $template->outputJsLocation,
                'sortOrder' => $template->sortOrder ?? 99,
            ];

            $fieldLayout = $template->getFieldLayout();
            $fieldLayoutConfig = $fieldLayout->getConfig();

            if ($fieldLayoutConfig) {
                if (empty($fieldLayout->id)) {
                    $layoutUid = StringHelper::UUID();
                    $fieldLayout->uid = $layoutUid;
                } else {
                    $layoutUid = Db::uidById('{{%fieldlayouts}}', $fieldLayout->id);
                }

                $configData['fieldLayouts'] = [$layoutUid => $fieldLayoutConfig];
            } else {
                $configData['fieldLayouts'] = [];
            }
        }

        $configPath = self::CONFIG_TEMPLATES_KEY . '.' . $templateUid;
        $projectConfig->set($configPath, $configData);

        if ($isNewTemplate) {
            $template->id = Db::idByUid('{{%formie_formtemplates}}', $templateUid);
        }

        return true;
    }

    /**
     * Handle template change.
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleChangedTemplate(ConfigEvent $event): void
    {
        $templateUid = $event->tokenMatches[0];
        $data = $event->newValue;

        if (!$data) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $templateRecord = $this->_getTemplateRecord($templateUid);
            $isNewTemplate = $templateRecord->getIsNewRecord();

            $templateRecord->name = $data['name'];
            $templateRecord->handle = $data['handle'];
            $templateRecord->template = $data['template'];
            $templateRecord->useCustomTemplates = $data['useCustomTemplates'];
            $templateRecord->outputCssLayout = $data['outputCssLayout'];
            $templateRecord->outputCssTheme = $data['outputCssTheme'];
            $templateRecord->outputJsBase = $data['outputJsBase'];
            $templateRecord->outputJsTheme = $data['outputJsTheme'];
            $templateRecord->outputCssLocation = $data['outputCssLocation'];
            $templateRecord->outputJsLocation = $data['outputJsLocation'];
            $templateRecord->sortOrder = $data['sortOrder'] ?? 99;
            $templateRecord->uid = $templateUid;

            $fieldsService = Craft::$app->getFields();

            if (!empty($data['fieldLayouts']) && !empty($config = reset($data['fieldLayouts']))) {
                // Save the main field layout
                $layout = FieldLayout::createFromConfig($config);
                $layout->id = $templateRecord->fieldLayoutId;
                $layout->type = Form::class;
                $layout->uid = key($data['fieldLayouts']);
                $fieldsService->saveLayout($layout);
                $templateRecord->fieldLayoutId = $layout->id;
            } else if ($templateRecord->fieldLayoutId) {
                // Delete the main field layout
                $fieldsService->deleteLayoutById($templateRecord->fieldLayoutId);
                $templateRecord->fieldLayoutId = null;
            }

            // Save the volume
            $templateRecord->save(false);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterSaveFormTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FORM_TEMPLATE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_FORM_TEMPLATE, new FormTemplateEvent([
                'template' => $this->getTemplateById($templateRecord->id),
                'isNew' => $isNewTemplate,
            ]));
        }
    }

    /**
     * Delete a template by its id.
     *
     * @param int $id
     * @return bool
     * @throws Throwable
     */
    public function deleteTemplateById(int $id): bool
    {
        $template = $this->getTemplateById($id);

        if (!$template) {
            return false;
        }

        return $this->deleteTemplate($template);
    }

    /**
     * Deletes a form template.
     *
     * @param FormTemplate $template The form template
     * @return bool Whether the form template was deleted successfully
     */
    public function deleteTemplate(FormTemplate $template): bool
    {
        // Fire a 'beforeDeleteFormTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_FORM_TEMPLATE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_FORM_TEMPLATE, new FormTemplateEvent([
                'template' => $template,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_TEMPLATES_KEY . '.' . $template->uid);
        return true;
    }

    /**
     * Handle template being deleted
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleDeletedTemplate(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];

        $template = $this->getTemplateByUid($uid);

        // Fire a 'beforeApplyFormTemplateDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_FORM_TEMPLATE_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_FORM_TEMPLATE_DELETE, new FormTemplateEvent([
                'template' => $template,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $templateRecord = $this->_getTemplateRecord($uid);

            // Save the template
            $templateRecord->softDelete();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterDeleteFormTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_FORM_TEMPLATE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_FORM_TEMPLATE, new FormTemplateEvent([
                'template' => $template,
            ]));
        }
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving templates.
     *
     * @param bool $withTrashed
     * @return Query
     */
    private function _createTemplatesQuery(bool $withTrashed = false): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'template',
                'useCustomTemplates',
                'outputCssLayout',
                'outputCssTheme',
                'outputJsBase',
                'outputJsTheme',
                'outputCssLocation',
                'outputJsLocation',
                'sortOrder',
                'fieldLayoutId',
                'dateDeleted',
                'uid',
            ])
            ->orderBy('sortOrder')
            ->from(['{{%formie_formtemplates}}']);

        if (!$withTrashed) {
            $query->where(['dateDeleted' => null]);
        }

        return $query;
    }

    /**
     * Gets a template record by uid.
     *
     * @param string $uid
     * @return TemplateRecord
     */
    private function _getTemplateRecord(string $uid): TemplateRecord
    {
        /** @var TemplateRecord $template */
        if ($template = TemplateRecord::findWithTrashed()->where(['uid' => $uid])->one()) {
            return $template;
        }

        return new TemplateRecord();
    }
}
