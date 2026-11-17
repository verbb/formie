<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use verbb\auth\models\Token;
use verbb\formie\integrations\miscellaneous\GoogleSheets;

class GoogleSheetsRequestFixture extends GoogleSheets
{
    public mixed $transport;

    public function getToken(): ?Token
    {
        $token = new Token();
        $token->setToken(new AccessToken(['access_token' => 'synthetic-sheets-token', 'expires' => time() + 3600]));

        return $token;
    }

    public function getFieldMappingValues($submission, $mapping, $settings = [])
    {
        return ['Name' => 'Synthetic person', 'Email' => 'fixture@example.test'];
    }

    protected function createDeliveryHttpHandler(): callable
    {
        return $this->transport;
    }
}

function googleSheetsRequestFixture(array &$history, array $responses): GoogleSheetsRequestFixture
{
    // Keep the real OAuth request builder and origin guards, replacing only network I/O.
    $transport = HandlerStack::create(new MockHandler($responses));
    $transport->push(Middleware::history($history));
    $integration = new GoogleSheetsRequestFixture([
        'name' => 'Google Sheets',
        'handle' => 'googleSheets' . uniqid(),
        'spreadsheetId' => 'synthetic-spreadsheet',
        'sheetId' => 'Sheet1',
    ]);
    $integration->transport = $transport;

    return $integration;
}

function googleSheetsJson(array $body): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
}

it('requests the spreadsheet once in its URLs when refreshing and appending', function(): void {
    $history = [];
    $integration = googleSheetsRequestFixture($history, [
        googleSheetsJson(['sheets' => [['properties' => ['title' => 'Sheet1']]]]),
        googleSheetsJson(['values' => [['Name', '', 'Email']]]),
        googleSheetsJson(['updates' => ['updatedRows' => 1]]),
    ]);

    $config = $integration->refreshConfig()->all();

    expect(array_column($config['sheets'], 'name'))->toBe(['Sheet1'])
        ->and($config['columns'])->toBe(['Sheet1' => ['Name', '', 'Email']]);

    $form = formie()->form()->singleLineTextField('name')->create();
    $result = $integration->sendPayload(formie()->submission($form)->save());

    expect($result)->toBeTrue()
        ->and(array_map(fn($entry) => $entry['request']->getMethod() . ' ' . rawurldecode((string)$entry['request']->getUri()), $history))->toBe([
            'GET https://sheets.googleapis.com/v4/spreadsheets/synthetic-spreadsheet/',
            "GET https://sheets.googleapis.com/v4/spreadsheets/synthetic-spreadsheet/values/'Sheet1'!A1:ZZZ1?majorDimension=ROWS",
            "POST https://sheets.googleapis.com/v4/spreadsheets/synthetic-spreadsheet/values/'Sheet1'!A1:append?valueInputOption=RAW&insertDataOption=INSERT_ROWS",
        ])
        ->and(json_decode((string)$history[2]['request']->getBody(), true))->toBe(['values' => [['Synthetic person', 'fixture@example.test']]]);
});

it('offers a Refresh Data button on the Sheet field', function(): void {
    $history = [];
    $integration = googleSheetsRequestFixture($history, []);
    $form = formie()->form()->singleLineTextField('name')->create();
    $sheet = array_values(array_filter($integration->getFormSettingsSchema($form), fn($item) => ($item['name'] ?? null) === 'sheetId'));

    expect($sheet)->toHaveCount(1)
        ->and($sheet[0]['$field'])->toBe('integrationRefreshCombobox')
        ->and($history)->toBeEmpty();
});
