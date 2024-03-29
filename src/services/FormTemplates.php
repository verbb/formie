<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\events\FormTemplateEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormTemplate;
use verbb\formie\records\FormTemplate as TemplateRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
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


    // Properties
    // =========================================================================

    private ?MemoizableArray $_templates = null;


    // Public Methods
    // =========================================================================

    public function getAllTemplates(bool $withTrashed = false): array
    {
        return $this->_templates()->all();
    }

    public function getTemplateById(int $id): ?FormTemplate
    {
        return $this->_templates()->firstWhere('id', $id);
    }

    public function getTemplateByHandle(string $handle): ?FormTemplate
    {
        return $this->_templates()->firstWhere('handle', $handle, true);
    }

    public function getTemplateByUid(string $uid): ?FormTemplate
    {
        return $this->_templates()->firstWhere('uid', $uid, true);
    }

    public function reorderTemplates(array $ids): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds(Table::FORMIE_FORM_TEMPLATES, $ids);

        foreach ($ids as $template => $templateId) {
            if (!empty($uidsByIds[$templateId])) {
                $templateUid = $uidsByIds[$templateId];
                $projectConfig->set(self::CONFIG_TEMPLATES_KEY . '.' . $templateUid . '.sortOrder', $template + 1);
            }
        }

        return true;
    }

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
            Formie::info('Template not saved due to validation error.');

            return false;
        }

        if ($isNewTemplate) {
            $template->uid = StringHelper::UUID();

            $template->sortOrder = (new Query())
                ->from([Table::FORMIE_FORM_TEMPLATES])
                ->max('[[sortOrder]]') + 1;
        } else if (!$template->uid) {
            $template->uid = Db::uidById(Table::FORMIE_FORM_TEMPLATES, $template->id);
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
            $template->id = Db::idByUid(Table::FORMIE_FORM_TEMPLATES, $template->uid);
        }

        return true;
    }

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

    public function deleteTemplateById(int $id): bool
    {
        $template = $this->getTemplateById($id);

        if (!$template) {
            return false;
        }

        return $this->deleteTemplate($template);
    }

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
                ->softDelete(Table::FORMIE_FORM_TEMPLATES, ['id' => $templateRecord->id])
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

    public function getTemplateSuggestions(): array
    {
        $data = [];

        $path = Craft::$app->getPath()->getSiteTemplatesPath();
        $directories = $this->_getRelativeDirectories($path, $path);
        sort($directories);

        foreach ($directories as $directory) {
            $data[] = ['name' => $directory];
        }

        return [['label' => Craft::t('formie', 'Directories'), 'data' => $data]];
    }


    // Private Methods
    // =========================================================================
    
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
            ->from([Table::FORMIE_FORM_TEMPLATES])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);

        return $query;
    }

    private function _getTemplateRecord(string $uid, bool $withTrashed = false): TemplateRecord
    {
        $query = $withTrashed ? TemplateRecord::findWithTrashed() : TemplateRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new TemplateRecord();
    }

    private function _getRelativeDirectories(string $basePath, string $path): array
    {
        $directories = [];

        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry == "." || $entry == "..") {
                    continue;
                }

                $fullPath = $path . DIRECTORY_SEPARATOR . $entry;

                if (is_dir($fullPath)) {
                    $directories[] = substr($fullPath, strlen($basePath) + 1);
                    $subDirectories = $this->_getRelativeDirectories($basePath, $fullPath);
                    $directories = array_merge($directories, $subDirectories);
                }
            }

            closedir($handle);
        }

        return $directories;
    }
}
