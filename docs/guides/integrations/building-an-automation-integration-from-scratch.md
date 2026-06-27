# Building an Automation integration from scratch

Formie ships automation integrations out of the box — Web Request, Zapier, Make, and others — but you can register your own when you need a custom endpoint, payload shape, or authentication header. This walkthrough builds an automation integration from a Craft module: editors configure a URL per form, test the payload from the builder, and Formie POSTs submission data when someone submits.

We also cover extending the built-in Web Request integration when you only need to tweak the payload.

Read the [Automation Integration](/developers/custom-integration/automation-integration) reference first if you have not built an integration before — this guide walks through a complete example and explains how the pieces connect.

## Create your module

First, you need a [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html). All of the PHP in this guide lives in that module.

When creating your module, set the namespace to `modules\formieintegration` and the module ID to `formie-integration`. Create the following directory and file structure:

```treeview
my-project/
├── modules/
│   └── formieintegration/
│       └── src/
│           ├── integrations/
│           │   └── ExampleAutomation.php
│           └── FormieIntegration.php
└── ...
```

Unlike CRM or email marketing integrations, automation integrations usually store their connection details **per form** (URL, HTTP method, request type) rather than in global plugin settings — so you typically do not need a `_plugin-settings.html` template.

Register the module in your project config, then wire it up to Formie in `FormieIntegration.php`:

```php [modules/formieintegration/src/FormieIntegration.php]
<?php
namespace modules\formieintegration;

use Craft;
use modules\formieintegration\integrations\ExampleAutomation;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;
use yii\base\Module;

class FormieIntegration extends Module
{
    public function init(): void
    {
        parent::init();

        Craft::setAlias('@formie-integration', __DIR__);

        Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
            $event->automations[] = ExampleAutomation::class;
        });
    }
}
```

The module file stays deliberately small. You tell Formie which integration class to load by pushing it onto `$event->automations[]`. Automation integrations register under **Automations**, not CRM or Email Marketing.

The bulk of the work lives in `ExampleAutomation.php`.

## The integration class

Create `modules/formieintegration/src/integrations/ExampleAutomation.php`. The class extends Formie's `Automation` base class and implements three moments editors care about:

1. **Configure** — URL, HTTP method, and request type on the form's Integrations tab
2. **Test** — send a sample payload while editing the form
3. **Submit** — POST real submission data when the form is submitted

Here is the full class. We walk through each method in the sections below.

```php [modules/formieintegration/src/integrations/ExampleAutomation.php]
<?php
namespace modules\formieintegration\integrations;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use GuzzleHttp\Client;
use Throwable;
use verbb\formie\base\Automation;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationFormSettings;

class ExampleAutomation extends Automation
{
    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Automation Integration');
    }

    public ?string $url = null;
    public string $method = 'POST';
    public string $requestType = 'json';

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to any URL you provide.');
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['url'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $payload = [];

        try {
            $formId = Craft::$app->getRequest()->getParam('formId');
            $form = Formie::$plugin->getForms()->getFormById($formId);

            $submission = new Submission();
            $submission->setForm($form);

            Formie::$plugin->getSubmissions()->populateFakeSubmission($submission);
            $payload = $this->generatePayloadValues($submission);

            $url = $form->settings->integrations[$this->handle]['url'] ?? $this->url;

            $this->deliverPayload(
                $submission,
                $this->getEndpointUrl($url, $submission),
                $payload,
                $this->method,
                $this->requestType,
            );
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}”', [
                'message' => $e->getMessage(),
            ]));

            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings(['payload' => $payload]);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $payload = $this->generatePayloadValues($submission);

            $response = $this->deliverPayload(
                $submission,
                $this->getEndpointUrl($this->url, $submission),
                $payload,
                $this->method,
                $this->requestType,
            );

            return $response !== false;
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}”. Payload: “{payload}”.', [
                'message' => $e->getMessage(),
                'payload' => Json::encode($payload ?? []),
            ]));

            Integration::apiError($this, $e);

            return false;
        }
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient();
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);

        $schema[] = SchemaHelper::textField([
            'label' => Craft::t('formie', 'URL'),
            'instructions' => Craft::t('formie', 'Enter the URL that will be triggered when a submission is made.'),
            'name' => 'url',
            'required' => true,
        ]);

        $schema[] = SchemaHelper::selectField([
            'label' => Craft::t('formie', 'HTTP Method'),
            'instructions' => Craft::t('formie', 'Select the HTTP method used to send data.'),
            'name' => 'method',
            'required' => true,
            'defaultValue' => $this->method ?: 'POST',
            'options' => [
                ['label' => 'GET', 'value' => 'GET'],
                ['label' => 'POST', 'value' => 'POST'],
                ['label' => 'PUT', 'value' => 'PUT'],
                ['label' => 'PATCH', 'value' => 'PATCH'],
                ['label' => 'DELETE', 'value' => 'DELETE'],
            ],
        ]);

        $schema[] = SchemaHelper::selectField([
            'label' => Craft::t('formie', 'Request Type'),
            'instructions' => Craft::t('formie', 'Select the request type used to send data.'),
            'name' => 'requestType',
            'required' => true,
            'defaultValue' => $this->requestType ?: 'json',
            'options' => [
                ['label' => Craft::t('formie', 'JSON Body'), 'value' => 'json'],
                ['label' => Craft::t('formie', 'Raw Body'), 'value' => 'body'],
                ['label' => Craft::t('formie', 'Query String'), 'value' => 'query'],
                ['label' => Craft::t('formie', 'Form Params'), 'value' => 'form_params'],
                ['label' => Craft::t('formie', 'Multipart'), 'value' => 'multipart'],
            ],
        ]);

        return $schema;
    }
}
```

This follows the same pattern as Formie's built-in [Web Request](/integrations/automations/web-request) integration — a useful reference while you adapt URLs and payload handling to your endpoint.

Every endpoint has different requirements — treat method names and payload shapes as patterns to adapt.

### Per-form settings

Automation integrations store connection details as public properties on the class (`$url`, `$method`, `$requestType`). Editors configure them per form on the **Integrations** tab — not in global plugin settings.

Define the form builder UI in `defineFormSettingsSchema()`. Start with `parent::defineFormSettingsSchema($form)` — the parent schema includes **Enabled**, **Conditions**, and **Opt-in field**.

Mark the URL as required when saving form settings using `Integration::SCENARIO_FORM` in `defineRules()`.

URLs can use Craft environment variables. The `Automation` base class resolves them through `getEndpointUrl()` — pass `$MY_AUTOMATION_URL` in the control panel and read the resolved value at send time with `App::parseEnv()` if you build custom URL logic.

Per-form integration UI is declared as schema nodes — see [Everything you need to know about Formie schemas](/guides/developers/everything-you-need-to-know-about-formie-schemas) for helper details.

### Guzzle client

Implement `defineClient()` when you need custom headers, authentication, or timeouts. The example uses a plain Guzzle client; add default headers for bearer tokens or API keys if your endpoint requires them.

Prefer `$this->deliverPayload()` over calling the client directly — it fires payload events, respects opt-in settings, and integrates with Formie's error logging.

### Test payload from the builder

`fetchFormSettings()` runs when an editor refreshes or tests the integration while editing a form. That is your chance to send a **sample request** to the configured URL so the editor can verify the endpoint before going live.

The pattern:

1. Load the current form from the request.
2. Create a fake submission with `populateFakeSubmission()` — realistic field values without a real submit.
3. Build the payload with `generatePayloadValues($submission)`.
4. Call `deliverPayload()` against the URL from form settings.

Return `IntegrationFormSettings` with the payload so the control panel can display what was sent. Errors flow through `Integration::apiError()` and appear in the UI.

### Sending the payload on submit

`sendPayload()` runs when a real submission completes and this integration is dispatched.

1. Build the payload with `generatePayloadValues($submission)` — Formie's standard shape with field handles, labels, and values — or override that method for a custom structure.
2. Call `deliverPayload()` with the form's URL, method, and request type.

Log failures with `Integration::error()` for detail in logs, and `Integration::apiError()` so the submission's integration run shows failed in the control panel.

Return `false` on failure so Formie records the integration run correctly.

## Extending an existing integration

When Formie's built-in Web Request integration is close to what you need, extend it and override payload generation:

```php [modules/formieintegration/src/integrations/WebRequestCustom.php]
<?php
namespace modules\formieintegration\integrations;

use verbb\formie\elements\Submission;
use verbb\formie\integrations\automations\WebRequest;

class WebRequestCustom extends WebRequest
{
    protected function generatePayloadValues(Submission $submission): array
    {
        $payload = parent::generatePayloadValues($submission);

        // Adjust payload shape for your endpoint.
        return $payload;
    }
}
```

Register `WebRequestCustom::class` on `$event->automations[]` instead of `ExampleAutomation::class`.

## Finishing up

With the module in place:

1. Go to **Formie → Integrations → Automations** and create a new **Example Automation Integration** instance.
2. Edit a form, open the **Integrations** tab, attach the integration, enter the URL, and send a test payload.
3. Submit the form on the front end and confirm your endpoint receives the request.

Integrations run as part of the [submission workflow](/developers/submission-workflow). For execution order, queue vs immediate mode, and conditions, see [Integration dispatch and policies](/guides/integrations/integration-dispatch-and-policies).

## Related

- [Automation Integration](/developers/custom-integration/automation-integration)
- [Web Request integration](/integrations/automations/web-request)
- [Using Guzzle clients from Formie integrations in your own code](/guides/integrations/using-guzzle-clients-from-formie-integrations-in-your-own-code)
- [Submission Workflow](/developers/submission-workflow)
