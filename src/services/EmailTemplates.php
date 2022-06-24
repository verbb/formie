<?php
namespace verbb\formie\services;

use verbb\formie\events\EmailTemplateEvent;
use verbb\formie\models\EmailTemplate;
use verbb\formie\records\EmailTemplate as TemplateRecord;

use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;

use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;
use Throwable;

class EmailTemplates extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SAVE_EMAIL_TEMPLATE = 'beforeSaveEmailTemplate';
    const EVENT_AFTER_SAVE_EMAIL_TEMPLATE = 'afterSaveEmailTemplate';
    const EVENT_BEFORE_DELETE_EMAIL_TEMPLATE = 'beforeDeleteEmailTemplate';
    const EVENT_BEFORE_APPLY_EMAIL_TEMPLATE_DELETE = 'beforeApplyEmailTemplateDelete';
    const EVENT_AFTER_DELETE_EMAIL_TEMPLATE = 'afterDeleteEmailTemplate';
    const CONFIG_TEMPLATES_KEY = 'formie.emailTemplates';


    // Private Properties
    // =========================================================================

    /**
     * @var EmailTemplate[]
     */
    private $_templates;


    // Public Methods
    // =========================================================================

    /**
     * Returns all templates.
     *
     * @param bool $withTrashed
     * @return EmailTemplate[]
     */
    public function getAllTemplates($withTrashed = false): array
    {
        // Get the caches items if we have them cached, and the request is for non-trashed items
        if ($this->_templates !== null) {
            return $this->_templates;
        }

        $results = $this->_createTemplatesQuery($withTrashed)->all();
        $templates = [];

        foreach ($results as $row) {
            $templates[] = new EmailTemplate($row);
        }

        return $templates;
    }

    /**
     * Returns a template identified by it's ID.
     *
     * @param int $id
     * @return EmailTemplate|null
     */
    public function getTemplateById($id)
    {
        return ArrayHelper::firstWhere($this->getAllTemplates(), 'id', $id);
    }

    /**
     * Returns a template identified by it's handle.
     *
     * @param string $handle
     * @return EmailTemplate|null
     */
    public function getTemplateByHandle($handle)
    {
        return ArrayHelper::firstWhere($this->getAllTemplates(), 'handle', $handle, false);
    }

    /**
     * Returns a template identified by it's UID.
     *
     * @param string $uid
     * @return EmailTemplate|null
     */
    public function getTemplateByUid(string $uid)
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
            Craft::info('Template not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($isNewTemplate) {
            $templateUid = StringHelper::UUID();
        } else {
            $templateUid = Db::uidById('{{%formie_emailtemplates}}', $template->id);
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
                'sortOrder' => (int)($template->sortOrder ?? 99),
            ];
        }

        $configPath = self::CONFIG_TEMPLATES_KEY . '.' . $templateUid;
        $projectConfig->set($configPath, $configData);

        if ($isNewTemplate) {
            $template->id = Db::idByUid('{{%formie_emailtemplates}}', $templateUid);
        }

        return true;
    }

    /**
     * Handle template change.
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleChangedTemplate(ConfigEvent $event)
    {
        $templateUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $templateRecord = $this->_getTemplateRecord($templateUid);
            $isNewTemplate = $templateRecord->getIsNewRecord();

            $templateRecord->name = $data['name'];
            $templateRecord->handle = $data['handle'];
            $templateRecord->template = $data['template'];
            $templateRecord->sortOrder = $data['sortOrder'] ?? 99;
            $templateRecord->uid = $templateUid;

            // Save the volume
            $templateRecord->save(false);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterSaveEmailTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_EMAIL_TEMPLATE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_EMAIL_TEMPLATE, new EmailTemplateEvent([
                'template' => $this->getTemplateById($templateRecord->id),
                'isNew' => $isNewTemplate,
            ]));
        }
    }

    /**
     * Delete a template by it's id.
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
        if (!$template) {
            return false;
        }

        // Fire a 'beforeDeleteEmailTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_EMAIL_TEMPLATE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_EMAIL_TEMPLATE, new EmailTemplateEvent([
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
    public function handleDeletedTemplate(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];

        $template = $this->getTemplateByUid($uid);

        // Fire a 'beforeApplyEmailTemplateDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_EMAIL_TEMPLATE_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_EMAIL_TEMPLATE_DELETE, new EmailTemplateEvent([
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
     * Returns a Query object prepped for retrieving templates.
     *
     * @param bool $withTrashed
     * @return Query
     */
    private function _createTemplatesQuery($withTrashed = false): Query
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
            ->orderBy('sortOrder')
            ->from(['{{%formie_emailtemplates}}']);

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
