<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\ElementFieldInterface;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\Table;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\fields;
use craft\helpers\Json;

use yii\base\Event;

use putyourlightson\campaign\Campaign as CampaignPlugin;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\MailingListRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;

use DateTime;
use DateTimeZone;
use Throwable;

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
        return Craft::t('formie', 'Campaign');
    }

    public static function getRequiredPlugins(): array
    {
        return ['campaign'];
    }


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        Event::on(self::class, self::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(ModifyFieldIntegrationValueEvent $event) {
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
        });
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Integrate with the [Craft Campaign](https://plugins.craftcms.com/campaign) plugin.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
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

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            // Get the Campaign mailing list
            $list = CampaignPlugin::$plugin->mailingLists->getMailingListById($this->listId);

            if (!$list) {
                Integration::error($this, 'Unable to find list “' . $this->listId . '”.', true);
                return false;
            }

            // Fetch our mapped values
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Ensure we trigger the un-before payload manually, as this isn't the typical API request
            $endpoint = '';
            $method = '';
            
            if (!$this->beforeSendPayload($submission, $endpoint, $fieldValues, $method)) {
                return true;
            }

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');

            $referrer = $this->context['referrer'] ?? null;

            // The `createAndSubscribeContact` method was added in Campaign v2.1.0.
            if (method_exists(CampaignPlugin::$plugin->forms, 'createAndSubscribeContact')) {
                $contact = CampaignPlugin::$plugin->forms->createAndSubscribeContact($email, $fieldValues, $list, 'formie', $referrer);
                
                if ($contact->hasErrors()) {
                    Integration::error($this, Craft::t('formie', 'Unable to save contact: “{errors}”.', [
                        'errors' => Json::encode($contact->getErrors()),
                    ]), true);
                    
                    return false;
                }
            } 
            // TODO: remove this in Formie v3, assuming it requires Craft 5, in which case Campaign v3 will be required.
            else {
                // Get the Campaign contact
                $contact = CampaignPlugin::$plugin->contacts->getContactByEmail($email);
    
                if ($contact === null) {
                    $contact = new ContactElement();
                    $contact->email = $email;
                }
    
                // Set field values
                $contact->setFieldValues($fieldValues);
    
                // If subscribe verification required
                if ($list->getMailingListType()->subscribeVerificationRequired) {
                    // Create a pending contact
                    $pendingContact = new PendingContactModel();
                    $pendingContact->email = $email;
                    $pendingContact->mailingListId = $list->id;
                    $pendingContact->source = $referrer;
                    $pendingContact->fieldData = $contact->getSerializedFieldValues();
    
                    if (!CampaignPlugin::$plugin->pendingContacts->savePendingContact($pendingContact)) {
                        Integration::error($this, Craft::t('formie', 'Unable to save pending contact: “{errors}”.', [
                            'errors' => Json::encode($pendingContact->getErrors()),
                        ]), true);
    
                        return false;
                    }
    
                    CampaignPlugin::$plugin->forms->sendVerifySubscribeEmail($pendingContact, $list);
                } else {
                    // Save contact
                    if (!Craft::$app->getElements()->saveElement($contact)) {
                        Integration::error($this, Craft::t('formie', 'Unable to save contact: “{errors}”.', [
                            'errors' => Json::encode($contact->getErrors()),
                        ]), true);
    
                        return false;
                    }
    
                    // Subscribe them to the mailing list
                    CampaignPlugin::$plugin->forms->subscribeContact($contact, $list, 'formie', $referrer);
                }
            } 
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            fields\Assets::class => IntegrationField::TYPE_ARRAY,
            fields\Categories::class => IntegrationField::TYPE_ARRAY,
            fields\Checkboxes::class => IntegrationField::TYPE_ARRAY,
            fields\Date::class => IntegrationField::TYPE_DATECLASS,
            fields\Entries::class => IntegrationField::TYPE_ARRAY,
            fields\Lightswitch::class => IntegrationField::TYPE_BOOLEAN,
            fields\MultiSelect::class => IntegrationField::TYPE_ARRAY,
            fields\Number::class => IntegrationField::TYPE_FLOAT,
            fields\Table::class => IntegrationField::TYPE_ARRAY,
            fields\Tags::class => IntegrationField::TYPE_ARRAY,
            fields\Users::class => IntegrationField::TYPE_ARRAY,
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
