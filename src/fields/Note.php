<?php
namespace verbb\formie\fields;

use verbb\formie\base\BuilderField;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;

use GraphQL\Type\Definition\Type;

class Note extends BuilderField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Note');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/note/icon.svg';
    }


    // Properties
    // =========================================================================

    public ?string $noteText = null;
    public string $noteStyle = 'tip';


    // Public Methods
    // =========================================================================

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewNote(),
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'noteText' => [
                'name' => 'noteText',
                'type' => Type::string(),
            ],
            'noteStyle' => [
                'name' => 'noteStyle',
                'type' => Type::string(),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::textareaField([
                'label' => Craft::t('formie', 'Note'),
                'instructions' => Craft::t('formie', 'The text shown in the form builder. Notes are not visible on the front-end form.'),
                'name' => 'noteText',
                'validation' => 'required',
                'required' => true,
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Style'),
                'instructions' => Craft::t('formie', 'Choose how the note should appear in the form builder.'),
                'name' => 'noteStyle',
                'options' => [
                    ['label' => Craft::t('formie', 'Tip'), 'value' => 'tip'],
                    ['label' => Craft::t('formie', 'Warning'), 'value' => 'warning'],
                    ['label' => Craft::t('formie', 'Info'), 'value' => 'info'],
                    ['label' => Craft::t('formie', 'Error'), 'value' => 'error'],
                ],
            ]),
        ];
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        return trim((string)$this->noteText) === '';
    }

    public function modifyFieldSettings(array $settings): array
    {
        $settings = parent::modifyFieldSettings($settings);
        $settings['noteStyle'] = $settings['noteStyle'] ?: 'tip';

        return $settings;
    }

    public function afterCreateField(array $data): void
    {
        $this->noteStyle = $this->noteStyle ?: 'tip';

        parent::afterCreateField($data);
    }


    // Protected Methods
    // =========================================================================

    protected function getBuilderIdentityHandlePrefix(): string
    {
        return 'note';
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            ['noteStyle'], 'in', 'range' => [
                'tip',
                'warning',
                'info',
                'error',
            ],
        ];

        return $rules;
    }
}
