<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;

use GuzzleHttp\Client;

use Throwable;

class Mercury extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Mercury');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public ?string $apiToken = null;
    public ?string $uatKey = null;
    public ?string $uatToken = null;
    public bool|string $useUat = false;
    public bool $mapToContact = false;
    public bool $mapToOpportunity = false;
    public ?array $contactFieldMapping = null;
    public ?array $opportunityFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Mercury customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function getUseUat(): string
    {
        return App::parseBooleanEnv($this->useUat);
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiToken'], 'required'];

        // Require URLs for public Volumes.
        if ($this->getUseUat()) {
            $rules[] = [['uatKey', 'uatToken'], 'required'];
        }

        $contact = $this->getFormSettingValue('contact');
        $opportunity = $this->getFormSettingValue('opportunity');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['opportunityFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
                return $model->enabled && $model->mapToOpportunity;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $contactFields = [
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'firstName',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'middleName',
                    'name' => Craft::t('formie', 'Middle Name'),
                ]),
                new IntegrationField([
                    'handle' => 'lastName',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'salutation',
                    'name' => Craft::t('formie', 'Salutation'),
                ]),
                new IntegrationField([
                    'handle' => 'title',
                    'name' => Craft::t('formie', 'Title'),
                ]),
                new IntegrationField([
                    'handle' => 'occupation',
                    'name' => Craft::t('formie', 'Occupation'),
                ]),
                new IntegrationField([
                    'handle' => 'employer',
                    'name' => Craft::t('formie', 'Employer'),
                ]),
                new IntegrationField([
                    'handle' => 'jobTitle',
                    'name' => Craft::t('formie', 'Job Title'),
                ]),
                new IntegrationField([
                    'handle' => 'maritalStatus',
                    'name' => Craft::t('formie', 'Marital Status'),
                ]),
                new IntegrationField([
                    'handle' => 'driversLicenceNumber',
                    'name' => Craft::t('formie', 'Drivers Licence Number'),
                ]),
                new IntegrationField([
                    'handle' => 'driversLicenceExpiry',
                    'name' => Craft::t('formie', 'Drivers Licence Expiry'),
                ]),
                new IntegrationField([
                    'handle' => 'driversLicenceState',
                    'name' => Craft::t('formie', 'Drivers Licence State'),
                ]),
                new IntegrationField([
                    'handle' => 'gender',
                    'name' => Craft::t('formie', 'Gender'),
                ]),
                new IntegrationField([
                    'handle' => 'dateOfBirth',
                    'name' => Craft::t('formie', 'Date of Birth'),
                ]),
                new IntegrationField([
                    'handle' => 'employmentStatus',
                    'name' => Craft::t('formie', 'Employment Status'),
                ]),
                new IntegrationField([
                    'handle' => 'employmentCommenced',
                    'name' => Craft::t('formie', 'Employment Commenced'),
                ]),
                new IntegrationField([
                    'handle' => 'phoneDisplayType1',
                    'name' => Craft::t('formie', 'Phone Display Type 1'),
                ]),
                new IntegrationField([
                    'handle' => 'phoneDisplayType2',
                    'name' => Craft::t('formie', 'Phone Display Type 2'),
                ]),
                new IntegrationField([
                    'handle' => 'addressDisplay',
                    'name' => Craft::t('formie', 'Address Display'),
                ]),
                new IntegrationField([
                    'handle' => 'homePhone',
                    'name' => Craft::t('formie', 'Home Phone Number'),
                ]),
                new IntegrationField([
                    'handle' => 'businessPhone',
                    'name' => Craft::t('formie', 'Business Phone Number'),
                ]),
                new IntegrationField([
                    'handle' => 'mobile_phone_number',
                    'name' => Craft::t('formie', 'Mobile Phone Number'),
                ]),
                new IntegrationField([
                    'handle' => 'personDataType',
                    'name' => Craft::t('formie', 'Person Data Type'),
                ]),
                new IntegrationField([
                    'handle' => 'notes',
                    'name' => Craft::t('formie', 'Notes'),
                ]),
                new IntegrationField([
                    'handle' => 'relationshipManager',
                    'name' => Craft::t('formie', 'Relationship Manager'),
                ]),
                new IntegrationField([
                    'handle' => 'annualSalary',
                    'name' => Craft::t('formie', 'Annual Salary'),
                ]),
                new IntegrationField([
                    'handle' => 'contactType',
                    'name' => Craft::t('formie', 'Contact Type'),
                ]),
                new IntegrationField([
                    'handle' => 'abn',
                    'name' => Craft::t('formie', 'ABN'),
                ]),
                new IntegrationField([
                    'handle' => 'acn',
                    'name' => Craft::t('formie', 'ACN'),
                ]),
                new IntegrationField([
                    'handle' => 'homeSuburb',
                    'name' => Craft::t('formie', 'Home Suburb'),
                ]),
                new IntegrationField([
                    'handle' => 'partnerName',
                    'name' => Craft::t('formie', 'Partner Name'),
                ]),
                new IntegrationField([
                    'handle' => 'leadSourceId',
                    'name' => Craft::t('formie', 'Lead Source ID'),
                ]),
                new IntegrationField([
                    'handle' => 'leadSourceName',
                    'name' => Craft::t('formie', 'Lead Source Name'),
                ]),
            ];

            $opportunityFields = [
                new IntegrationField([
                    'handle' => 'company',
                    'name' => Craft::t('formie', 'Company'),
                ]),
                new IntegrationField([
                    'handle' => 'opportunityName',
                    'name' => Craft::t('formie', 'Name'),
                ]),
                new IntegrationField([
                    'handle' => 'amount',
                    'name' => Craft::t('formie', 'Amount'),
                ]),
                new IntegrationField([
                    'handle' => 'lender',
                    'name' => Craft::t('formie', 'Lender'),
                ]),
                new IntegrationField([
                    'handle' => 'lenderNameShort',
                    'name' => Craft::t('formie', 'Lender Name Short'),
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                ]),
                new IntegrationField([
                    'handle' => 'agent',
                    'name' => Craft::t('formie', 'Agent'),
                ]),
                new IntegrationField([
                    'handle' => 'personActing',
                    'name' => Craft::t('formie', 'Person Acting'),
                ]),
                new IntegrationField([
                    'handle' => 'personResponsible',
                    'name' => Craft::t('formie', 'Person Responsible'),
                ]),
                new IntegrationField([
                    'handle' => 'personResponsible',
                    'name' => Craft::t('formie', 'Person Responsible'),
                ]),
                new IntegrationField([
                    'handle' => 'lenderReference',
                    'name' => Craft::t('formie', 'Lender Reference'),
                ]),
                new IntegrationField([
                    'handle' => 'financeDate',
                    'name' => Craft::t('formie', 'Finance Date'),
                ]),
                new IntegrationField([
                    'handle' => 'expectedSettlementDate',
                    'name' => Craft::t('formie', 'Expected Settlement Date'),
                ]),
                new IntegrationField([
                    'handle' => 'confirmedSettlementDate',
                    'name' => Craft::t('formie', 'Confirmed Settlement Date'),
                ]),
                new IntegrationField([
                    'handle' => 'leadSourceId',
                    'name' => Craft::t('formie', 'Lead Source ID'),
                ]),
                new IntegrationField([
                    'handle' => 'leadSourceDisplay',
                    'name' => Craft::t('formie', 'Lead Source Display'),
                ]),
                new IntegrationField([
                    'handle' => 'discount',
                    'name' => Craft::t('formie', 'Discount'),
                ]),
                new IntegrationField([
                    'handle' => 'existingAmount',
                    'name' => Craft::t('formie', 'Existing Amount'),
                ]),
                new IntegrationField([
                    'handle' => 'lmi',
                    'name' => Craft::t('formie', 'LMI'),
                ]),
                new IntegrationField([
                    'handle' => 'settlementDateConfirmed',
                    'name' => Craft::t('formie', 'Settlement Date Confirmed'),
                ]),
                new IntegrationField([
                    'handle' => 'discountType',
                    'name' => Craft::t('formie', 'Discount Type'),
                ]),
                new IntegrationField([
                    'handle' => 'discountType',
                    'name' => Craft::t('formie', 'Discount Type'),
                ]),
                new IntegrationField([
                    'handle' => 'loanPersonRelationship',
                    'name' => Craft::t('formie', 'Loan Person Relationship'),
                ]),
                new IntegrationField([
                    'handle' => 'transactionType',
                    'name' => Craft::t('formie', 'Transaction Type'),
                ]),
                new IntegrationField([
                    'handle' => 'notePadText',
                    'name' => Craft::t('formie', 'Note Pad Text'),
                ]),
                new IntegrationField([
                    'handle' => 'partnerReference',
                    'name' => Craft::t('formie', 'Partner Reference'),
                ]),
                new IntegrationField([
                    'handle' => 'nextGenId',
                    'name' => Craft::t('formie', 'Next Gen ID'),
                ]),
                new IntegrationField([
                    'handle' => 'parentId',
                    'name' => Craft::t('formie', 'Parent ID'),
                ]),
                new IntegrationField([
                    'handle' => 'workspaceUsers',
                    'name' => Craft::t('formie', 'Workspace Users'),
                ]),
                new IntegrationField([
                    'handle' => 'agentName',
                    'name' => Craft::t('formie', 'Agent Name'),
                ]),
                new IntegrationField([
                    'handle' => 'personActingName',
                    'name' => Craft::t('formie', 'Person Acting Name'),
                ]),
                new IntegrationField([
                    'handle' => 'personResponsibleName',
                    'name' => Craft::t('formie', 'Person Responsible Name'),
                ]),
            ];

            $settings = [
                'contact' => $contactFields,
                'opportunity' => $opportunityFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');

            $contactPayload = $this->_prepCustomFields($contactValues);
            $opportunityPayload = $this->_prepCustomFields($opportunityValues);

            $contactId = null;

            if ($this->mapToContact) {
                $response = $this->deliverPayload($submission, 'contacts', $contactPayload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['uniqueId'] ?? '';
            }

            $opportunityId = null;

            if ($this->mapToOpportunity) {
                if ($contactId) {
                    $opportunityPayload['personID'] = $contactId;
                }

                $response = $this->deliverPayload($submission, 'opportunities', $opportunityPayload);

                if ($response === false) {
                    return true;
                }

                $opportunityId = $response['uniqueId'] ?? '';

                // Relate the opportunity and contact
                if ($contactId && $opportunityId) {
                    $payload = [
                        'personID' => $contactId,
                    ];

                    $response = $this->deliverPayload($submission, "opportunities/{$opportunityId}/relatedParties", $payload);

                    if ($response === false) {
                        return true;
                    }
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'contacts', [
                'query' => [
                    'search' => true,
                ],
            ]);
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        $url = 'https://apis.connective.com.au/mercury/v1';
        $apiToken = App::parseEnv($this->apiToken);
        $apiKey = App::parseEnv($this->apiKey);

        if ($this->getUseUat()) {
            $url = 'https://uatapis.connective.com.au/mercury-v1';
            $apiToken = App::parseEnv($this->uatToken);
            $apiKey = App::parseEnv($this->uatKey);
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => "$url/$apiToken/",
            'headers' => [
                'x-api-key' => $apiKey,
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _prepCustomFields($fields)
    {
        // Emails need to be handled specifically.
        if (isset($fields['email'])) {
            $contactMethods = $fields['contactMethods'] ?? [];

            $fields['contactMethods'] = array_merge($contactMethods, [
                [
                    'contactMethod' => 'Email 1',
                    'content' => $fields['email'],
                ],
            ]);
        }

        if (isset($fields['mobile_phone_number'])) {
            $contactMethods = $fields['contactMethods'] ?? [];

            $fields['contactMethods'] = array_merge($contactMethods, [
                [
                    'contactMethod' => 'Mobile',
                    'content' => $fields['mobile_phone_number'],
                ],
            ]);
        }

        return $fields;
    }
}
