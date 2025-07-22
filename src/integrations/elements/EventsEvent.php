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

use verbb\events\Events;
use verbb\events\elements\Event as EventElement;

use Throwable;

class EventsEvent extends Element
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Events Event');
    }

    public static function getRequiredPlugins(): array
    {
        return ['events'];
    }


    // Properties
    // =========================================================================

    public ?int $eventTypeId = null;
    public int|array|null $defaultAuthorId = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Map content provided by form submissions to create {name} elements.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $customFields = [];

        $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();

        foreach ($eventTypes as $eventType) {
            $fields = $this->getFieldLayoutFields($eventType->getFieldLayout());

            $customFields[] = new IntegrationCollection([
                'id' => $eventType->id,
                'name' => $eventType->name,
                'fields' => $fields,
            ]);
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
        ];
    }

    public function getUpdateAttributes(): array
    {
        $attributes = [];

        $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();

        foreach ($eventTypes as $eventType) {
            $attributes[$eventType->id] = [
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

            if ($fieldLayout = $eventType->getFieldLayout()) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    if (!$this->fieldCanBeUniqueId($field)) {
                        continue;
                    }

                    $attributes[$eventType->id][] = new IntegrationField([
                        'handle' => $field->handle,
                        'name' => $field->name,
                        'type' => $this->getFieldTypeForField(get_class($field)),
                        'sourceType' => get_class($field),
                    ]);
                }
            }
        }

        return $attributes;
    }

    public function sendPayload(Submission $submission): IntegrationResponse|bool
    {
        if (!$this->eventTypeId) {
            Integration::error($this, Craft::t('formie', 'Unable to save element integration. No `eventTypeId`.'), true);

            return false;
        }

        try {
            $eventType = Events::$plugin->getEventTypes()->getEventTypeById($this->eventTypeId);

            $event = $this->getElementForPayload(EventElement::class, $this->eventTypeId, $submission, [
                'typeId' => $eventType->id,
            ]);

            $event->siteId = $submission->siteId;
            $event->typeId = $eventType->id;

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

            $fields = $this->_getEventTypeSettings()->fields ?? [];
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
        $rules[] = [['eventTypeId', 'defaultAuthorId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        $fields = $this->_getEventTypeSettings()->fields ?? [];

        $rules[] = [['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
            return $model->enabled;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getEventTypeSettings()
    {
        $eventTypes = $this->getFormSettingValue('elements');

        return ArrayHelper::firstWhere($eventTypes, 'id', $this->eventTypeId);
    }
}