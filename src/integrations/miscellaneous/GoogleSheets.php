<?php
namespace verbb\formie\integrations\miscellaneous;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;

use League\OAuth1\Client\Server\Server as Oauth1Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Google as GoogleProvider;

use Throwable;

use GuzzleHttp\Client;

class GoogleSheets extends Miscellaneous
{
    // Static Methods
    // =========================================================================

    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Google Sheets');
    }
    

    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $proxyRedirect = null;
    public ?string $spreadsheetId = null;
    public ?string $sheetId = null;
    public ?array $fieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getAuthorizeUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://www.googleapis.com/oauth2/v4/token';
    }

    public function getResourceOwner(): string
    {
        return 'https://openidconnect.googleapis.com/v1/userinfo';
    }

    public function getClientId(): string
    {
        return App::parseEnv($this->clientId);
    }

    public function getClientSecret(): string
    {
        return App::parseEnv($this->clientSecret);
    }

    public function getProxyRedirect(): ?bool
    {
        return App::parseBooleanEnv($this->proxyRedirect);
    }

    public function getOauthScope(): array
    {
        return [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/spreadsheets',
        ];
    }

    public function getRedirectUri(): string
    {
        $uri = parent::getRedirectUri();

        // Allow a proxy to our server to forward on the request - just for local dev ease
        if ($this->getProxyRedirect()) {
            return "https://formie.verbb.io?return=$uri";
        }

        return $uri;
    }

    public function getOauthProviderConfig(): array
    {
        return array_merge(parent::getOauthProviderConfig(), [
            'accessType' => 'offline',
            'prompt' => 'consent',
        ]);
    }

    public function getOauthProvider(): AbstractProvider|Oauth1Provider
    {
        return new GoogleProvider($this->getOauthProviderConfig());
    }

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

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $spreadsheet = $this->request('GET', '');
            $allSheets = $spreadsheet['sheets'] ?? [];
            $sheets = [];
            $savedColumns = [];

            foreach ($allSheets as $sheet) {
                $response = $this->request('GET', "values/'{$sheet['properties']['title']}'!A1:ZZZ1", [
                    'query' => ['majorDimension' => 'ROWS'],
                ]);

                $allColumns = $response['values'][0] ?? [];

                // Save this for later, we need to when sending
                $savedColumns[$sheet['properties']['title']] = $allColumns;

                // But we want to only show columns with a header to map
                $allColumns = array_filter($allColumns);

                $columns = [];

                foreach ($allColumns as $column) {
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
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

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
            //
            // This does require column `A` to not be hidden, otherwise it won't append values.
            $range = "'{$this->sheetId}'!A1";

            $response = $this->deliverPayload($submission, "values/{$range}:append?valueInputOption=RAW&insertDataOption=INSERT_ROWS", $payload);

            if ($response === false) {
                return true;
            }
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

        $token = $this->getToken();

        if (!$token) {
            Integration::apiError($this, 'Token not found for integration.', true);
        }

        $spreadsheetId = App::parseEnv($this->spreadsheetId);

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/",
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', '');
        } catch (Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/",
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }
}