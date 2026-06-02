<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\ChildFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyNamePrefixOptionsEvent;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;

use yii\base\Event;

class NamePrefix extends Dropdown implements ChildFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PREFIX_OPTIONS = 'modifyPrefixOptions';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Name - Prefix');
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/dropdown';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'fields/dropdown';
    }


    // Public Methods
    // =========================================================================

    public function allowDuplicateLabels(): bool
    {
        return true;
    }

    public function getDefaultOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
            ['label' => Craft::t('formie', 'Mr.'), 'value' => 'mr'],
            ['label' => Craft::t('formie', 'Mrs.'), 'value' => 'mrs'],
            ['label' => Craft::t('formie', 'Ms.'), 'value' => 'ms'],
            ['label' => Craft::t('formie', 'Miss.'), 'value' => 'miss'],
            ['label' => Craft::t('formie', 'Mx.'), 'value' => 'mx'],
            ['label' => Craft::t('formie', 'Dr.'), 'value' => 'dr'],
            ['label' => Craft::t('formie', 'Prof.'), 'value' => 'prof'],
        ];

        $event = new ModifyNamePrefixOptionsEvent([
            'options' => $options,
        ]);

        Event::trigger(static::class, self::EVENT_MODIFY_PREFIX_OPTIONS, $event);

        return $event->options;
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Options'),
                'instructions' => Craft::t('formie', 'Define the available options for users to select from.'),
                'name' => 'options',
                'validation' => 'min:1|uniqueTableCellValue',
                'enableBulkOptions' => true,
                'predefinedOptions' => $this->getPredefinedOptions(),
                'newRowDefaults' => [
                    'default' => false,
                ],
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Option Label'),
                        'required' => true,
                    ],
                    [
                        'type' => 'value',
                        'name' => 'value',
                        'label' => Craft::t('formie', 'Value'),
                        'source' => 'label',
                    ],
                    [
                        'type' => 'radio',
                        'name' => 'default',
                        'label' => Craft::t('formie', 'Default'),
                    ],
                ],
            ]),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return $this->_getValueLabel($value);
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        $label = $this->_getValueLabel($value);

        return $label !== '' ? [$label] : [];
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->_getValueLabel($value);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        return $this->_getValueLabel($value);
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = parent::defineFieldSlotTag($key, $context);

        if ($tag && $key === 'fieldInput') {
            $tag->mergeCoreAttributes([
                'autocomplete' => 'honorific-prefix',
            ]);
        }

        return $tag;
    }


    // Private Methods
    // =========================================================================

    private function _getValueLabel(mixed $value): string
    {
        if ($value) {
            if ($prefixOption = ArrayHelper::firstWhere($this->options(), 'value', $value)) {
                return $prefixOption['label'] ?? '';
            }
        }

        return '';
    }
}
