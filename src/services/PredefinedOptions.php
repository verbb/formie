<?php
namespace verbb\formie\services;

use verbb\formie\events\RegisterPredefinedOptionsEvent;
use verbb\formie\options;

use Craft;
use craft\base\Component;

class PredefinedOptions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_PREDEFINED_OPTIONS = 'registerPredefinedOptions';


    // Properties
    // =========================================================================

    private array $_options = [];


    // Public Methods
    // =========================================================================

    public function getRegisteredPredefinedOptions(): array
    {
        if (count($this->_options)) {
            return $this->_options;
        }

        $options = [
            options\Countries::class,
            options\StatesAustralia::class,
            options\StatesCanada::class,
            options\StatesUsa::class,
            options\Continents::class,

            options\Days::class,
            options\Months::class,

            options\Currencies::class,
            options\Languages::class,

            options\Industry::class,
            options\Education::class,
            options\Employment::class,
            options\MaritalStatus::class,
            options\Age::class,
            options\Gender::class,
            options\Size::class,

            options\Acceptability::class,
            options\Agreement::class,
            options\Comparison::class,
            options\Difficulty::class,
            options\HowLong::class,
            options\HowOften::class,
            options\Importance::class,
            options\Satisfaction::class,
            options\WouldYou::class,
        ];

        $event = new RegisterPredefinedOptionsEvent([
            'options' => $options,
        ]);

        $this->trigger(self::EVENT_REGISTER_PREDEFINED_OPTIONS, $event);

        foreach ($event->options as $class) {
            $this->_options[$class] = new $class;
        }

        return $this->_options;
    }

    public function getPredefinedOptions(): array
    {
        $options = [
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
        ];

        $availableOptions = $this->getRegisteredPredefinedOptions();

        foreach ($availableOptions as $availableOption) {
            $options[] = ['label' => Craft::t('formie', $availableOption::displayName()), 'value' => get_class($availableOption)];
        }

        return $options;
    }

    public function getPredefinedOptionsForType($type): array
    {
        $option = [];

        foreach ($this->getRegisteredPredefinedOptions() as $registeredOption) {
            if (get_class($registeredOption) === $type) {
                $option = $registeredOption;
            }
        }

        if (!$option) {
            return [];
        }

        return [
            'data' => $option::getDataOptions(),
            'labelOptions' => $option::getLabelOptions(),
            'valueOptions' => $option::getValueOptions(),
            'labelOption' => $option::$defaultLabelOption,
            'valueOption' => $option::$defaultValueOption,
        ];
    }
}
