<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\ElementFieldInterface;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\Table;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\fields;
use craft\fields\Assets;
use craft\fields\Categories;
use craft\fields\Checkboxes;
use craft\fields\Date;
use craft\fields\Entries;
use craft\fields\Lightswitch;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\Table as CraftTable;
use craft\fields\Tags;
use craft\fields\Users;
use craft\helpers\Json;


use DateTime;
use DateTimeZone;
use Throwable;

use putyourlightson\campaign\Campaign as CampaignPlugin;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;

class Campaign extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function supportsConnection(): bool
    {
        return false;
    }

    public static function displayName(): string
    {
        return 'Campaign';
    }

    public static function getRequiredPlugins(): array
    {
        return ['campaign'];
    }


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Integrate with the [Craft Campaign](https://plugins.craftcms.com/campaign) plugin.');
    }

    public function fetchConfig(): IntegrationConfig
    {
        $settings = [];

        $lists = MailingListElement::find()
            ->site('*')
            ->orderBy(['elements_sites.slug' => 'ASC', 'title' => 'ASC'])
            ->all();

        $isMultiSite = Craft::$app->getIsMultiSite();

        foreach ($lists as $list) {
            $name = $isMultiSite ? "({$list->site}) {$list->title}" : $list->title;

            $listFields = array_merge([
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
            ], $this->_getCustomFields($list));

            $settings['lists'][] = new IntegrationCollection([
                'id' => (string)$list->id,
                'name' => $name,
                'fields' => $listFields,
            ]);
        }

        return new IntegrationConfig($settings);
    }


    // Protected Methods
    // =========================================================================


    protected function modifyFieldMappingValue(ModifyFieldIntegrationValueEvent $event): void
    {
        // For rich-text enabled fields, retain the HTML (safely)
        if ($event->field instanceof MultiLineText) {
            $event->value = StringHelper::htmlDecode($event->value);
        }

        // For Date fields as a destination, convert to UTC from system time
        if ($event->integrationField->getType() === IntegrationField::TYPE_DATECLASS) {
            if ($event->value instanceof DateTime) {
                $timezone = new DateTimeZone(Craft::$app->getTimeZone());

                $event->value = DateTime::createFromFormat('Y-m-d H:i:s', $event->value->format('Y-m-d H:i:s'), $timezone);
            }
        }

        // Element fields should map 1-for-1, but not as arrays of titles
        if ($event->field instanceof ElementFieldInterface) {
            $event->value = $event->submission->getFieldValue($event->field->handle)->ids();
        }

        // For Table fields with Date/Time destination columns, convert to UTC from system time
        if ($event->field instanceof Table) {
            $timezone = new DateTimeZone(Craft::$app->getTimeZone());

            foreach ($event->value as $rowKey => $row) {
                foreach ($row as $colKey => $column) {
                    if (is_array($column) && isset($column['date'])) {
                        $event->value[$rowKey][$colKey] = (new DateTime($column['date'], $timezone));
                    }
                }
            }
        }
    }

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            // Get the Campaign mailing list
            $list = CampaignPlugin::$plugin->mailingLists->getMailingListById($this->listId);

            if (!$list) {
                Integration::error($this, 'Unable to find list “' . $this->listId . '”.', true);
                return $this->resultForPayload(false);
            }

            // Fetch our mapped values
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Ensure we trigger the un-before payload manually, as this isn't the typical API request
            $endpoint = '';
            $method = '';

            if (!$this->beforeSendPayload($submission, $endpoint, $fieldValues, $method)) {
                return $this->resultForPayload(true);
            }

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');

            $referrer = $this->context['referrer'] ?? null;

            $contact = CampaignPlugin::$plugin->forms->createAndSubscribeContact($email, $fieldValues, $list, 'formie', $referrer);

            if ($contact->hasErrors()) {
                Integration::error($this, Craft::t('formie', 'Unable to save contact: “{errors}”.', [
                    'errors' => Json::encode($contact->getErrors()),
                ]), true);

                return $this->resultForPayload(false);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return $this->resultForPayload(false);
        }

        return $this->resultForPayload(true);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            Assets::class => IntegrationField::TYPE_ARRAY,
            Categories::class => IntegrationField::TYPE_ARRAY,
            Checkboxes::class => IntegrationField::TYPE_ARRAY,
            Date::class => IntegrationField::TYPE_DATECLASS,
            Entries::class => IntegrationField::TYPE_ARRAY,
            Lightswitch::class => IntegrationField::TYPE_BOOLEAN,
            MultiSelect::class => IntegrationField::TYPE_ARRAY,
            Number::class => IntegrationField::TYPE_FLOAT,
            CraftTable::class => IntegrationField::TYPE_ARRAY,
            Tags::class => IntegrationField::TYPE_ARRAY,
            Users::class => IntegrationField::TYPE_ARRAY,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($list): array
    {
        $customFields = [];

        $fieldLayout = Craft::$app->getFields()->getLayoutByType(ContactElement::class);

        foreach ($fieldLayout->getCustomFields() as $field) {
            $customFields[] = new IntegrationField([
                'handle' => $field->handle,
                'name' => $field->name,
                'type' => $this->_convertFieldType(get_class($field)),
                'sourceType' => get_class($field),
                'required' => (bool)$field->required,
            ]);
        }

        return $customFields;
    }
}
