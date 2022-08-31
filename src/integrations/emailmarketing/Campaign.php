<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\fields;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use putyourlightson\campaign\Campaign as CampaignPlugin;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\MailingListRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;

use Throwable;

class Campaign extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function supportsConnection(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Campaign');
    }


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Integrate with the [Craft Campaign](https://plugins.craftcms.com/campaign) plugin.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        $lists = MailingListElement::find()
            ->site('*')
            ->orderBy(['elements_sites.slug' => 'ASC', 'content.title' => 'ASC'])
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

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');

            // The `createAndSubscribeContact` method was added in Campaign v2.1.0.
            if (method_exists(CampaignPlugin::$plugin->forms, 'createAndSubscribeContact')) {
                $contact = CampaignPlugin::$plugin->forms->createAndSubscribeContact($email, $fieldValues, $list, 'formie', $this->referrer);
                
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
                    $pendingContact->source = $this->referrer;
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
                    CampaignPlugin::$plugin->forms->subscribeContact($contact, $list, 'formie', $this->referrer);
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

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            fields\Checkboxes::class => IntegrationField::TYPE_ARRAY,
            fields\Lightswitch::class => IntegrationField::TYPE_BOOLEAN,
            fields\Entries::class => IntegrationField::TYPE_ARRAY,
            fields\MultiSelect::class => IntegrationField::TYPE_ARRAY,
            fields\Number::class => IntegrationField::TYPE_FLOAT,
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
                'required' => (bool)$field->required,
            ]);
        }

        return $customFields;
    }
}
