<?php
namespace verbb\formie\fields\traits;

use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\SlotTag;

use verbb\formie\theme\context\RenderContext;

use craft\base\ElementInterface;

use GraphQL\Type\Definition\Type;

trait TextLimitFieldTrait
{
    // Properties
    // =========================================================================

    public bool $limit = false;
    public ?int $min = null;
    public ?string $minType = 'characters';
    public ?int $max = null;
    public ?string $maxType = 'characters';


    // Public Methods
    // =========================================================================

    public function validateMinCharacters(ElementInterface $element): void
    {
        $min = $this->min ?? 0;

        if (!$min) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());
        $count = StringHelper::getCharacterCount($value);

        if ($count < $min) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MIN_CHARACTERS, [
                'limit' => $min,
                'min' => $min,
            ]));
        }
    }

    public function validateMaxCharacters(ElementInterface $element): void
    {
        $max = $this->max ?? 0;

        if (!$max) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());
        $count = StringHelper::getCharacterCount($value);

        if ($count > $max) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MAX_CHARACTERS, [
                'limit' => $max,
                'max' => $max,
            ]));
        }
    }

    public function validateMinWords(ElementInterface $element): void
    {
        $min = $this->min ?? 0;

        if (!$min) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());
        $count = StringHelper::getWordCount($value);

        if ($count < $min) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MIN_WORDS, [
                'limit' => $min,
                'min' => $min,
            ]));
        }
    }

    public function validateMaxWords(ElementInterface $element): void
    {
        $max = $this->max ?? 0;

        if (!$max) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());
        $count = StringHelper::getWordCount($value);

        if ($count > $max) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MAX_WORDS, [
                'limit' => $max,
                'max' => $max,
            ]));
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineTextLimitGqlType(): array
    {
        return [
            'limit' => [
                'name' => 'limit',
                'type' => Type::boolean(),
            ],
            'min' => [
                'name' => 'min',
                'type' => Type::int(),
            ],
            'minType' => [
                'name' => 'minType',
                'type' => Type::string(),
            ],
            'max' => [
                'name' => 'max',
                'type' => Type::int(),
            ],
            'maxType' => [
                'name' => 'maxType',
                'type' => Type::string(),
            ],
        ];
    }

    protected function defineTextLimitValidationSchema(): array
    {
        return [
            SchemaHelper::limitValueField(),
            SchemaHelper::textLimitMinFields(),
            SchemaHelper::minCharactersValidationMessage(),
            SchemaHelper::minWordsValidationMessage(),
            SchemaHelper::textLimitMaxFields(),
            SchemaHelper::maxCharactersValidationMessage(),
            SchemaHelper::maxWordsValidationMessage(),
        ];
    }

    protected function defineTextLimitRules(): array
    {
        return [
            [['min', 'max'], 'number', 'integerOnly' => true],
            [['minType', 'maxType'], 'in', 'range' => ['characters', 'words']],
        ];
    }

    protected function getTextLimitElementValidationRules(): array
    {
        if (!$this->limit) {
            return [];
        }

        $rules = [];

        if ($this->minType === 'characters') {
            $rules[] = [$this->handle, 'validateMinCharacters', 'skipOnEmpty' => false];
        }

        if ($this->maxType === 'characters') {
            $rules[] = [$this->handle, 'validateMaxCharacters'];
        }

        if ($this->minType === 'words') {
            $rules[] = [$this->handle, 'validateMinWords', 'skipOnEmpty' => false];
        }

        if ($this->maxType === 'words') {
            $rules[] = [$this->handle, 'validateMaxWords'];
        }

        return $rules;
    }

    protected function applyTextLimitInputAttributes(array $attributes): array
    {
        return array_merge($attributes, ValidationMessagesHelper::textLimitClientAttributes(
            $this,
            (bool)$this->limit,
            $this->min,
            $this->max,
            $this->minType,
            $this->maxType,
        ));
    }

    protected function defineTextLimitFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($key !== 'fieldLimit') {
            return null;
        }

        return SlotTag::make('div')
            ->core([
                'data-formie-field-limit' => true,
                'data-formie-limit-text' => true,
            ])
            ->theme([
                'class' => [
                    'formie-field-note',
                    'formie-field-limit',
                    'formie-limit-text',
                ],
            ]);
    }

    protected function defineTextLimitBrowserModules(): array
    {
        if (!$this->limit) {
            return [];
        }

        return [
            new BrowserModule([
                'moduleId' => 'formie:text-limit',
                'surfaces' => [BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED],
                'config' => $this->getTextLimitClientConfig(false),
            ]),
            new BrowserModule([
                'moduleId' => 'formie:text-limit',
                'surfaces' => [BrowserModule::SURFACE_CP_EDIT],
                'config' => $this->getTextLimitClientConfig(true),
            ]),
        ];
    }

    protected function getTextLimitClientConfig(bool $allowOvertype): array
    {
        return [
            'allowOvertype' => $allowOvertype,
        ];
    }

    protected function getTextLimitClientInput(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
        ];
    }
}
