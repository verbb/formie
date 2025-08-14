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

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Google as GoogleProvider;

class GoogleSheets extends Miscellaneous implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return GoogleProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Google Sheets');
    }
    

    // Properties
    // =========================================================================

    public ?string $proxyRedirect = null;
    public ?string $spreadsheetId = null;
    public ?string $sheetId = null;
    public ?array $fieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getProxyRedirect(): ?bool
    {
        return App::parseBooleanEnv($this->proxyRedirect);
    }

    public function getSpreadsheetId(): ?string
    {
        return App::parseEnv($this->spreadsheetId);
    }

    public function getSheetId(): ?string
    {
        return App::parseEnv($this->sheetId);
    }

    public function getBaseApiUrl(?Token $token): ?string
    {
        return "https://sheets.googleapis.com/v4/spreadsheets/";
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['baseApiUrl'] = fn(?Token $token) => $this->getBaseApiUrl($token);

        return $config;
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();
        $options['access_type'] = 'offline';
        $options['prompt'] = 'consent';

        $options['scope'] = [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/spreadsheets',
        ];
        
        return $options;
    }

    public function getRedirectUri(): string
    {
        $uri = parent::getRedirectUri();

        // Allow a proxy to our server to forward on the request - just for local dev ease
        if ($this->getProxyRedirect()) {
            return "https://proxy.verbb.io?return=$uri";
        }

        return $uri;
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Google Sheets.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $formId = Craft::$app->getRequest()->getParam('formId');
            $form = Formie::$plugin->getForms()->getFormById($formId);

            // Ensure we're fetching the spreadsheetId from the form settings, or global integration settings
            $spreadsheetId = $form->settings->integrations[$this->handle]['spreadsheetId'] ? App::parseEnv($form->settings->integrations[$this->handle]['spreadsheetId']) : $this->getSpreadSheetId();

            $spreadsheet = $this->request('GET', $spreadsheetId);
            $allSheets = $spreadsheet['sheets'] ?? [];
            $sheets = [];
            $savedColumns = [];

            foreach ($allSheets as $sheet) {
                $response = $this->request('GET', "{$spreadsheetId}/values/'{$sheet['properties']['title']}'!A1:ZZZ1", [
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

            $spreadsheetId = $this->getSpreadsheetId();

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

            $response = $this->deliverPayload($submission, "{$spreadsheetId}/values/{$range}:append?valueInputOption=RAW&insertDataOption=INSERT_ROWS", $payload);

            if ($response === false) {
                return true;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret', 'spreadsheetId'], 'required'];

        // Validate the following when saving form settings
        $rules[] = [['sheetId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }
}
