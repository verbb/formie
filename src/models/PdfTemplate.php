<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\records\PdfTemplate as PdfTemplateRecord;

use craft\helpers\UrlHelper;

class PdfTemplate extends BaseTemplate
{
    // Properties
    // =========================================================================

    public string $filenameFormat = 'Submission-{submission.id}';
    public bool $hasSingleTemplate = true;


    // Public Methods
    // =========================================================================

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/settings/pdf-templates/edit/' . $this->id);
    }

    public function canDelete(): bool
    {
        $notifications = Formie::$plugin->getNotifications()->getAllNotifications();
        $notification = ArrayHelper::firstWhere($notifications, 'pdfTemplateId', $this->id);

        return !$notification;
    }

    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'handle' => $this->handle,
            'template' => $this->template,
            'filenameFormat' => $this->filenameFormat,
            'sortOrder' => $this->sortOrder,
        ];
    }

    protected function getRecordClass(): string
    {
        return PdfTemplateRecord::class;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = ['template', 'required'];

        return $rules;
    }
}
