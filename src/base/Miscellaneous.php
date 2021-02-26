<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

abstract class Miscellaneous extends Integration implements IntegrationInterface
{
    // Static Methods
    // =========================================================================
    
    /**
     * @inheritDoc
     */
    public static function typeName(): string
    {
        return Craft::t('formie', 'Miscellaneous');
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/miscellaneous/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml($form): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/miscellaneous/edit/' . $this->id);
    }

    /**
     * @inheritDoc
     */
    protected function generatePayloadValues(Submission $submission): array
    {
        $submissionContent = [];
        $submissionAttributes = $submission->getAttributes();

        $formAttributes = $submission->getForm()->getAttributes();

        // Trim the form settings a little
        $formAttributes['settings'] = $formAttributes['settings']->toArray();
        unset($formAttributes['settings']['integrations']);

        foreach ($submission->getForm()->getFields() as $field) {
            $value = $submission->getFieldValue($field->handle);
            $submissionContent[$field->handle] = $field->serializeValue($value, $submission);
        }

        return [
            'json' => [
                'submission' => array_merge($submissionAttributes, $submissionContent),
                'form' => $formAttributes,
            ],
        ];
    }
}
