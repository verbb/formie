<?php
namespace verbb\formie\integrations\miscellaneous;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

use League\OAuth2\Client\Provider\Google as GoogleProvider;

class GoogleSheets extends Miscellaneous
{
    // Properties
    // =========================================================================

    public $clientId;
    public $clientSecret;
    public $proxyRedirect;
    public $spreadsheetId;
    public $sheetId;
    public $fieldMapping;


    // OAuth Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizeUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        return 'https://www.googleapis.com/oauth2/v4/token';
    }

    /**
     * @inheritDoc
     */
    public function getResourceOwner(): string
    {
        return 'https://openidconnect.googleapis.com/v1/userinfo';
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return Craft::parseEnv($this->clientId);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return Craft::parseEnv($this->clientSecret);
    }

    /**
     * @inheritDoc
     */
    public function getOauthScope(): array
    {
        return [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/spreadsheets',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUri()
    {
        $uri = parent::getRedirectUri();

        // Allow a proxy to our server to forward on the request - just for local dev ease
        if ($this->proxyRedirect) {
            return "https://formie.verbb.io?return=$uri";
        }

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function getOauthProviderConfig()
    {
        return array_merge(parent::getOauthProviderConfig(), [
            'accessType' => 'offline',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getOauthProvider()
    {
        return new GoogleProvider($this->getOauthProviderConfig());
    }
    

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        // Allow an .env var to override the proxy state. Due to it being a lightswitch
        // we can't set an override any other way.
        $proxyRedirect = Craft::parseEnv('$FORMIE_INTEGRATION_PROXY_REDIRECT');

        if (!is_null($proxyRedirect)) {
            $this->proxyRedirect = $proxyRedirect;
        }

        return parent::init();
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Google Sheets');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Google Sheets.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret', 'spreadsheetId'], 'required'];

        // Validate the following when saving form settings
        $rules[] = [['sheetId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            $spreadsheet = $this->request('GET', '');
            $allSheets = $spreadsheet['sheets'] ?? [];
            $columns = [];
            $savedColumns = [];

            foreach ($allSheets as $key => $sheet) {
                $response = $this->request('GET', "values/'{$sheet['properties']['title']}'!A1:ZZZ1", [
                    'query' => ['majorDimension' => 'ROWS'],
                ]);

                $allColumns = $response['values'][0] ?? [];

                // Save this for later, we need to when sending
                $savedColumns[$sheet['properties']['title']] = $allColumns;

                // But we want to only show columns with a header to map
                $allColumns = array_filter($allColumns);

                $columns = [];

                foreach ($allColumns as $key => $column) {
                    $columns[] = new IntegrationField([
                        'handle' => $column,
                        'name' => $column,
                    ]);
                }

                $sheets[] = [
                    'id' => $sheet['properties']['title'],
                    'name' => $sheet['properties']['title'],
                    'fields' => $columns,
                ];
            }

            $settings = [
                'sheets' => $sheets,
                'columns' => $savedColumns,
            ];
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Fetch the columns from our private stash
            $columns = $this->getFormSettings()->collections['columns'][$this->sheetId] ?? [];
            $rowValues = [];

            // Just in case...
            $columns = array_values(array_filter($columns));

            foreach ($columns as $key => $column) {
                $rowValues[$key] = $fieldValues[$column] ?? '';
            }

            $payload = [
                'values' => [$rowValues],
            ];

            // Statically set the first column to determine where to start our range. Google will sometimes set the
            // 'table' of content to be incorrect, if there are gaps in columns. Here, we account for that.
            $range = "'{$this->sheetId}'!A1";

            $response = $this->deliverPayload($submission, "values/{$range}:append?valueInputOption=RAW&insertDataOption=INSERT_ROWS", $payload);

            if ($response === false) {
                return true;
            }
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $token = $this->getToken();

        $spreadsheetId = Craft::parseEnv($this->spreadsheetId);

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/",
            'headers' => [
                'Authorization' => 'Bearer ' . $token->accessToken ?? '',
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', '');
        } catch (\Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/",
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token->accessToken ?? '',
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }
}