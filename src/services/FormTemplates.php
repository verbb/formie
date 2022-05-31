<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\events\FormTemplateEvent;
use verbb\formie\models\FormTemplate;
use verbb\formie\records\FormTemplate as TemplateRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

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

    private ?MemoizableArray $_templates = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns all templates.
     *
     * @return FormTemplate[]
     */
    public function getAllTemplates(bool $withTrashed = false): array
    {
        return $this->_templates()->all();
    }

    /**
     * Returns a template identified by its ID.
     *
     * @param int $id
     * @return FormTemplate|null
     */
    public function getTemplateById(int $id): ?FormTemplate
    {
        return $this->_templates()->firstWhere('id', $id);
    }

    /**
     * Returns a template identified by its handle.
     *
     * @param string $handle
     * @return FormTemplate|null
     */
    public function getTemplateByHandle(string $handle): ?FormTemplate
    {
        return $this->_templates()->firstWhere('handle', $handle, true);
    }

    /**
     * Returns a template identified by its UID.
     *
     * @param string $uid
     * @return FormTemplate|null
     */
    public function getTemplateByUid(string $uid): ?FormTemplate
    {
        return $this->_templates()->firstWhere('uid', $uid, true);
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
            Formie::log('Template not saved due to validation error.');

            return false;
        }

        if ($isNewTemplate) {
            $template->uid = StringHelper::UUID();

            $template->sortOrder = (new Query())
                ->from(['{{%formie_formtemplates}}'])
                ->max('[[sortOrder]]') + 1;
        } else if (!$template->uid) {
            $template->uid = Db::uidById('{{%formie_formtemplates}}', $template->id);
        }

        // Make sure no templates that are not archived share the handle
        $existingTemplate = $this->getTemplateByHandle($template->handle);

        if ($existingTemplate && (!$template->id || $template->id != $existingTemplate->id)) {
            $template->addError('handle', Craft::t('formie', 'That handle is already in use'));
            return false;
        }

        $configPath = self::CONFIG_TEMPLATES_KEY . '.' . $template->uid;
        Craft::$app->getProjectConfig()->set($configPath, $template->getConfig(), "Save the “{$template->handle}” form template");

        if ($isNewTemplate) {
            $template->id = Db::idByUid('{{%formie_formtemplates}}', $template->uid);
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
            $templateRecord = $this->_getTemplateRecord($templateUid, true);
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
            $templateRecord->sortOrder = $data['sortOrder'];
            $templateRecord->uid = $templateUid;

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $templateRecord->fieldLayoutId;
                $layout->type = Form::class;
                $layout->uid = key($data['fieldLayouts']);
                
                Craft::$app->getFields()->saveLayout($layout, false);
                
                $templateRecord->fieldLayoutId = $layout->id;
            } else if ($templateRecord->fieldLayoutId) {
                // Delete the main field layout
                Craft::$app->getFields()->deleteLayoutById($templateRecord->fieldLayoutId);
                $templateRecord->fieldLayoutId = null;
            }

            if ($wasTrashed = (bool)$templateRecord->dateDeleted) {
                $templateRecord->restore();
            } else {
                $templateRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_templates = null;

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

        Craft::$app->getProjectConfig()->remove(self::CONFIG_TEMPLATES_KEY . '.' . $template->uid, "Delete form template “{$template->handle}”");
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
        $templateRecord = $this->_getTemplateRecord($uid);

        if ($templateRecord->getIsNewRecord()) {
            return;
        }

        $template = $this->getTemplateById($templateRecord->id);

        // Fire a 'beforeApplyFormTemplateDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_FORM_TEMPLATE_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_FORM_TEMPLATE_DELETE, new FormTemplateEvent([
                'template' => $template,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            Craft::$app->getDb()->createCommand()
                ->softDelete('{{%formie_formtemplates}}', ['id' => $templateRecord->id])
                ->execute();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_templates = null;

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
     * Returns a memoizable array of all templates.
     *
     * @return MemoizableArray<EmailTemplate>
     */
    private function _templates(): MemoizableArray
    {
        if (!isset($this->_templates)) {
            $templates = [];

            foreach ($this->_createTemplatesQuery()->all() as $result) {
                $templates[] = new FormTemplate($result);
            }

            $this->_templates = new MemoizableArray($templates);
        }

        return $this->_templates;
    }

    /**
     * Returns a Query object prepped for retrieving templates.
     *
     * @param bool $withTrashed
     * @return Query
     */
    private function _createTemplatesQuery(): Query
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
            ->from(['{{%formie_formtemplates}}'])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);

        return $query;
    }

    /**
     * Gets a template's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed templates in search
     * @return TemplateRecord
     */
    private function _getTemplateRecord(string $uid, bool $withTrashed = false): TemplateRecord
    {
        $query = $withTrashed ? TemplateRecord::findWithTrashed() : TemplateRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new TemplateRecord();
    }
}
