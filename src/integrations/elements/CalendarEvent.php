<?php
namespace verbb\formie\integrations\elements;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Element;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResponse;

use Craft;
use craft\base\Element as CraftElement;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

use yii\base\Event;

use Solspace\Calendar\Calendar;
use Solspace\Calendar\Elements\Event as EventElement;

use Carbon\Carbon;
use Throwable;

class CalendarEvent extends Element
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Calendar Event');
    }

    public static function getRequiredPlugins(): array
    {
        return ['calendar'];
    }


    // Properties
    // =========================================================================

    public ?int $calendarId = null;
    public int|array|null $defaultAuthorId = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        Event::on(self::class, self::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(ModifyFieldIntegrationValueEvent $event) {
            // Calendar expects dates as Carbon object, not DateTime
            if (in_array($event->integrationField->handle, ['startDate', 'endDate', 'until'])) {
                $event->value = new Carbon($event->value->format('Y-m-d H:i:s') ?? 'now', 'utc');
            }
        });
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Map content provided by form submissions to create {name} elements.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $customFields = [];

        if (class_exists(Calendar::class)) {
            $calendars = Calendar::getInstance()->calendars->getAllAllowedCalendars();

            foreach ($calendars as $calendar) {
                $fields = $this->getFieldLayoutFields($calendar->getFieldLayout());

                $customFields[] = new IntegrationCollection([
                    'id' => $calendar->id,
                    'name' => $calendar->name,
                    'fields' => $fields,
                ]);
            }
        }

        return new IntegrationFormSettings([
            'elements' => $customFields,
            'attributes' => $this->getElementAttributes(),
        ]);
    }

    public function getElementAttributes(): array
    {
        return [
            new IntegrationField([
                'name' => Craft::t('app', 'Title'),
                'handle' => 'title',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Site ID'),
                'handle' => 'siteId',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Slug'),
                'handle' => 'slug',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Author'),
                'handle' => 'author',
                'type' => IntegrationField::TYPE_ARRAY,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Start Date'),
                'handle' => 'startDate',
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'End Date'),
                'handle' => 'endDate',
                'type' => IntegrationField::TYPE_DATECLASS,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'All Day'),
                'handle' => 'allDay',
                'type' => IntegrationField::TYPE_BOOLEAN,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Enabled'),
                'handle' => 'enabled',
                'type' => IntegrationField::TYPE_BOOLEAN,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Repeat Rule'),
                'handle' => 'rrule',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Repeat Interval'),
                'handle' => 'interval',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Repeat Frequency'),
                'handle' => 'freq',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Repeat Count'),
                'handle' => 'count',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Repeat Until'),
                'handle' => 'until',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Repeat By Month'),
                'handle' => 'byMonth',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Repeat Year Day'),
                'handle' => 'byYearDay',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Repeat By Month Day'),
                'handle' => 'byMonthDay',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Repeat By Day'),
                'handle' => 'byDay',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Select Dates'),
                'handle' => 'selectDates',
            ]),
        ];
    }

    public function getUpdateAttributes(): array
    {
        $attributes = [];

        if (class_exists(Calendar::class)) {
            $calendars = Calendar::getInstance()->calendars->getAllAllowedCalendars();

            foreach ($calendars as $calendar) {
                $attributes[$calendar->id] = [
                    new IntegrationField([
                        'name' => Craft::t('app', 'ID'),
                        'handle' => 'id',
                    ]),
                    new IntegrationField([
                        'name' => Craft::t('app', 'Title'),
                        'handle' => 'title',
                    ]),
                    new IntegrationField([
                        'name' => Craft::t('app', 'Slug'),
                        'handle' => 'slug',
                    ]),
                    new IntegrationField([
                        'name' => Craft::t('app', 'Site'),
                        'handle' => 'site',
                    ]),
                ];

                if ($fieldLayout = $calendar->getFieldLayout()) {
                    foreach ($fieldLayout->getCustomFields() as $field) {
                        if (!$this->fieldCanBeUniqueId($field)) {
                            continue;
                        }

                        $attributes[$calendar->id][] = new IntegrationField([
                            'handle' => $field->handle,
                            'name' => $field->name,
                            'type' => $this->getFieldTypeForField(get_class($field)),
                            'sourceType' => get_class($field),
                        ]);
                    }
                }
            }
        }

        return $attributes;
    }

    public function sendPayload(Submission $submission): IntegrationResponse|bool
    {
        if (!$this->calendarId) {
            Integration::error($this, Craft::t('formie', 'Unable to save element integration. No `calendarId`.'), true);

            return false;
        }

        try {
            $calendar = Calendar::getInstance()->calendars->getCalendarById($this->calendarId);

            $event = $this->getElementForPayload(EventElement::class, $this->calendarId, $submission, [
                'calendarId' => $calendar->id,
            ]);

            $event->siteId = $submission->siteId;
            $event->calendarId = $calendar->id;

            $attributeValues = $this->getFieldMappingValues($submission, $this->attributeMapping, $this->getElementAttributes());

            // Filter null values
            if (!$this->overwriteValues) {
                $attributeValues = ArrayHelper::filterNull($attributeValues);
            }

            foreach ($attributeValues as $eventFieldHandle => $fieldValue) {
                if ($eventFieldHandle === 'author') {
                    if (isset($fieldValue[0])) {
                        $event->authorId = $fieldValue[0] ?? null;
                    }
                } else if (in_array($eventFieldHandle, ['startDate', 'endDate', 'until'])) {
                    // Calendar expects dates as Carbon object, not DateTime
                    $event->{$eventFieldHandle} = new Carbon($fieldValue->format('Y-m-d H:i:s') ?? 'now', 'utc');
                } else {
                    $event->{$eventFieldHandle} = $fieldValue;
                }
            }

            $fields = $this->_getCalendarSettings()->fields ?? [];
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);

            // Filter null values
            if (!$this->overwriteValues) {
                $fieldValues = ArrayHelper::filterNull($fieldValues);
            }

            $event->setFieldValues($fieldValues);

            // Although empty, because we pass via reference, we need variables
            $endpoint = '';
            $method = '';

            // Allow events to cancel sending - return as success            
            if (!$this->beforeSendPayload($submission, $endpoint, $event, $method)) {
                return true;
            }

            if (!$event->validate()) {
                Integration::error($this, Craft::t('formie', 'Unable to validate “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($event->getErrors()),
                ]), true);

                return false;
            }

            if (!Craft::$app->getElements()->saveElement($event)) {
                Integration::error($this, Craft::t('formie', 'Unable to save “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($event->getErrors()),
                ]), true);

                return false;
            }

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, '', $event, '', [])) {
                return true;
            }
        } catch (Throwable $e) {
            $error = Craft::t('formie', 'Element integration failed for submission “{submission}”. Error: {error} {file}:{line}', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'submission' => $submission->id,
            ]);

            Formie::error($error);

            return new IntegrationResponse(false, [$error]);
        }

        return true;
    }

    public function getAuthor($form): array
    {
        $defaultAuthorId = $form->settings->integrations[$this->handle]['defaultAuthorId'] ?? '';

        if (!$defaultAuthorId) {
            $defaultAuthorId = $this->defaultAuthorId;
        }

        if ($defaultAuthorId) {
            return User::find()->id($defaultAuthorId)->all();
        }

        return [Craft::$app->getUser()->getIdentity()];
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['calendarId', 'defaultAuthorId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        $fields = $this->_getCalendarSettings()->fields ?? [];

        $rules[] = [['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
            return $model->enabled;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getCalendarSettings()
    {
        $calendars = $this->getFormSettingValue('elements');

        return ArrayHelper::firstWhere($calendars, 'id', $this->calendarId);
    }
}