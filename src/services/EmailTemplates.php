<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\events\EmailTemplateEvent;
use verbb\formie\models\EmailTemplate;
use verbb\formie\records\EmailTemplate as TemplateRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

use Throwable;

class EmailTemplates extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_EMAIL_TEMPLATE = 'beforeSaveEmailTemplate';
    public const EVENT_AFTER_SAVE_EMAIL_TEMPLATE = 'afterSaveEmailTemplate';
    public const EVENT_BEFORE_DELETE_EMAIL_TEMPLATE = 'beforeDeleteEmailTemplate';
    public const EVENT_BEFORE_APPLY_EMAIL_TEMPLATE_DELETE = 'beforeApplyEmailTemplateDelete';
    public const EVENT_AFTER_DELETE_EMAIL_TEMPLATE = 'afterDeleteEmailTemplate';
    public const CONFIG_TEMPLATES_KEY = 'formie.emailTemplates';


    // Private Properties
    // =========================================================================

    private ?MemoizableArray $_templates = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns all templates.
     *
     * @return EmailTemplate[]
     */
    public function getAllTemplates(): array
    {
        return $this->_templates()->all();
    }

    /**
     * Returns a template identified by its ID.
     *
     * @param int $id
     * @return EmailTemplate|null
     */
    public function getTemplateById(int $id): ?EmailTemplate
    {
        return $this->_templates()->firstWhere('id', $id);
    }

    /**
     * Returns a template identified by its handle.
     *
     * @param string $handle
     * @return EmailTemplate|null
     */
    public function getTemplateByHandle(string $handle): ?EmailTemplate
    {
        return $this->_templates()->firstWhere('handle', $handle, true);
    }

    /**
     * Returns a template identified by its UID.
     *
     * @param string $uid
     * @return EmailTemplate|null
     */
    public function getTemplateByUid(string $uid): ?EmailTemplate
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

        $uidsByIds = Db::uidsByIds('{{%formie_emailtemplates}}', $ids);

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
     * @param EmailTemplate $template
     * @param bool $runValidation
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function saveTemplate(EmailTemplate $template, bool $runValidation = true): bool
    {
        $isNewTemplate = !(bool)$template->id;

        // Fire a 'beforeSaveEmailTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_EMAIL_TEMPLATE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_EMAIL_TEMPLATE, new EmailTemplateEvent([
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
                ->from(['{{%formie_emailtemplates}}'])
                ->max('[[sortOrder]]') + 1;
        } else if (!$template->uid) {
            $template->uid = Db::uidById('{{%formie_emailtemplates}}', $template->id);
        }

        // Make sure no templates that are not archived share the handle
        $existingTemplate = $this->getTemplateByHandle($template->handle);

        if ($existingTemplate && (!$template->id || $template->id != $existingTemplate->id)) {
            $template->addError('handle', Craft::t('formie', 'That handle is already in use'));
            return false;
        }

        $configPath = self::CONFIG_TEMPLATES_KEY . '.' . $template->uid;
        Craft::$app->getProjectConfig()->set($configPath, $template->getConfig(), "Save the “{$template->handle}” email template");

        if ($isNewTemplate) {
            $template->id = Db::idByUid('{{%formie_emailtemplates}}', $template->uid);
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

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $templateRecord = $this->_getTemplateRecord($templateUid, true);
            $isNewTemplate = $templateRecord->getIsNewRecord();

            $templateRecord->name = $data['name'];
            $templateRecord->handle = $data['handle'];
            $templateRecord->template = $data['template'];
            $templateRecord->sortOrder = $data['sortOrder'];
            $templateRecord->uid = $templateUid;

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

        // Fire an 'afterSaveEmailTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_EMAIL_TEMPLATE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_EMAIL_TEMPLATE, new EmailTemplateEvent([
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
     * Deletes a email template.
     *
     * @param EmailTemplate $template The email template
     * @return bool Whether the email template was deleted successfully
     */
    public function deleteTemplate(EmailTemplate $template): bool
    {
        // Fire a 'beforeDeleteEmailTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_EMAIL_TEMPLATE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_EMAIL_TEMPLATE, new EmailTemplateEvent([
                'template' => $template,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_TEMPLATES_KEY . '.' . $template->uid, "Delete email template “{$template->handle}”");
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

        // Fire a 'beforeApplyEmailTemplateDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_EMAIL_TEMPLATE_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_EMAIL_TEMPLATE_DELETE, new EmailTemplateEvent([
                'template' => $template,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            Craft::$app->getDb()->createCommand()
                ->softDelete('{{%formie_emailtemplates}}', ['id' => $templateRecord->id])
                ->execute();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterDeleteEmailTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_EMAIL_TEMPLATE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_EMAIL_TEMPLATE, new EmailTemplateEvent([
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
                $templates[] = new EmailTemplate($result);
            }

            $this->_templates = new MemoizableArray($templates);
        }

        return $this->_templates;
    }

    /**
     * Returns a Query object prepped for retrieving templates.
     *
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
                'sortOrder',
                'dateDeleted',
                'uid',
            ])
            ->from(['{{%formie_emailtemplates}}'])
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
