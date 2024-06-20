<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\base\SubFieldInnerFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyNamePrefixOptionsEvent;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;

use yii\base\Event;

class NamePrefix extends Dropdown implements SubFieldInnerFieldInterface
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

    public static function getFrontEndInputTemplatePath(): string
    {
        return 'fields/dropdown';
    }

    public static function getEmailTemplatePath(): string
    {
        return 'fields/dropdown';
    }


    // Public Methods
    // =========================================================================

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

    public function getValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
    {
        // Craft::dd([$value, $this->_getValueLabel($value)]);
        return $this->_getValueLabel($value);
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return $this->_getValueLabel($value);
    }

    protected function defineValueAsJson(mixed $value, ElementInterface $element = null): string
    {
        return $this->_getValueLabel($value);
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->_getValueLabel($value);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        return $this->_getValueLabel($value);
    }


    // Private Methods
    // =========================================================================

    private function _getValueLabel(mixed $value): string
    {
        if ($value) {
            if ($prefixOption = ArrayHelper::firstWhere($this->getDefaultOptions(), 'value', $value)) {
                return $prefixOption['label'] ?? '';
            }
        }

        return '';
    }
}
