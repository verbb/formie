<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\records\EmailTemplate as EmailTemplateRecord;

use craft\helpers\UrlHelper;

class EmailTemplate extends BaseTemplate
{
    // Properties
    // =========================================================================

    public bool $hasSingleTemplate = true;


    // Public Methods
    // =========================================================================

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/settings/email-templates/edit/' . $this->id);
    }

    public function canDelete(): bool
    {
        $notifications = Formie::$plugin->getNotifications()->getAllNotifications();
        $notification = ArrayHelper::firstWhere($notifications, 'templateId', $this->id);

        return !$notification;
    }

    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'handle' => $this->handle,
            'template' => $this->template,
            'sortOrder' => $this->sortOrder,
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = ['template', 'required'];

        return $rules;
    }

    protected function getRecordClass(): string
    {
        return EmailTemplateRecord::class;
    }
}
