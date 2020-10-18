<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

use putyourlightson\campaign\Campaign as CampaignPlugin;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\MailingListRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;

class Campaign extends EmailMarketing
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Integrate with the [Craft Campaign](https://plugins.craftcms.com/campaign) plugin.');
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
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

    /**
     * @inheritDoc
     */
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

            // Get the Campaign contact
            $contact = CampaignPlugin::$plugin->contacts->getContactByEmail($email);

            if ($contact === null) {
                $contact = new ContactElement();
            }

            // Set field values
            $contact->email = $email;
            $contact->setFieldValues($fieldValues);

            // Save contact
            if (!Craft::$app->getElements()->saveElement($contact)) {
                Integration::error($this, Craft::t('formie', 'Unable to save contact: “{errors}”.', [
                    'errors' => Json::encode($contact->getErrors()),
                ]), true);

                return false;
            }

            // Subscribe them to the mailing list
            CampaignPlugin::$plugin->forms->subscribeContact($contact, $list, 'formie', $this->referrer, true);
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            Checkboxes::class => IntegrationField::TYPE_ARRAY,
            Lightswitch::class => IntegrationField::TYPE_BOOLEAN,
            Entries::class => IntegrationField::TYPE_ARRAY,
            MultiSelect::class => IntegrationField::TYPE_ARRAY,
            Number::class => IntegrationField::TYPE_NUMBER,
            Tags::class => IntegrationField::TYPE_ARRAY,
            Users::class => IntegrationField::TYPE_ARRAY,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($list)
    {
        $customFields = [];

        $fieldLayout = Craft::$app->fields->getLayoutByType(ContactElement::class);

        foreach ($fieldLayout->getFields() as $field) {
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