# Captcha Integration

Captcha integrations extend `Captcha`. They do not send payloads to another app like CRM or email marketing integrations do. Their job is to render whatever the captcha provider needs on the front end, collect a token or response value, then verify that value before Formie continues processing the submission.

The front-end and back-end pieces work together:

1. `renderHtml()` outputs the captcha placeholder or hidden input.
2. `getClientModule()` registers any front-end module the captcha needs.
3. The front-end module loads the provider script, renders the widget and writes the provider token into a normal posted value.
4. When the form is submitted, Formie runs the captcha during the submission screening stage.
5. `validateSubmission()` reads the submitted value and verifies it with the provider.
6. If validation returns `true`, Formie continues processing the submission. If it returns `false`, the submission is blocked as spam.

Formie calls `validateSubmission()` through `runValidation()`, which wraps validation events and catches validation exceptions. Override `validateSubmission()`, not `runValidation()`.

```php
use Craft;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Json;
use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

class ExampleCaptcha extends Captcha
{
    public ?string $siteKey = null;
    public ?string $secretKey = null;

    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Captcha');
    }

    public function renderHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return Html::tag('div', null, [
            'class' => 'formie-captcha example-captcha-placeholder',
            'data-example-captcha-placeholder' => true,
        ]);
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        if (!$context->form) {
            return null;
        }

        return new ClientModule([
            'id' => 'example-captcha',
            'src' => '/assets/formie/example-captcha.js',
            'config' => [
                'placeholderSelector' => '[data-example-captcha-placeholder]',
                'siteKey' => App::parseEnv($this->siteKey),
                'formId' => $context->form->getRenderId(),
            ],
        ]);
    }

    public function validateSubmission(Submission $submission): bool
    {
        $token = $this->getCaptchaValue($submission, 'example-captcha-token');

        if (!$token) {
            $this->spamReason = Craft::t('formie', 'Missing captcha token.');

            return false;
        }

        $response = $this->request('POST', 'https://captcha.example.test/siteverify', [
            'json' => [
                'secret' => App::parseEnv($this->secretKey),
                'response' => $token,
                'remoteip' => Craft::$app->getRequest()->getRemoteIP(),
            ],
        ]);

        $success = (bool)($response['success'] ?? false);

        if (!$success) {
            $this->spamReason = Json::encode($response);
        }

        return $success;
    }
}
```

## Methods
Captcha integrations usually focus on these methods:

Method | Use
--- | ---
`renderHtml()` | Outputs the captcha markup for the form.
`getClientModule()` | Registers front-end behavior for the captcha, when needed.
`getRefreshJsVariables()` | Returns data used when refreshing captcha tokens.
`getGqlVariables()` | Returns GraphQL mutation variables for the captcha.
`validateSubmission()` | Validates the submitted captcha value.

## Form Settings
Captcha integrations inherit the standard form settings schema from `Captcha`, including the `enabled` setting and the `showAllPages` setting for multi-page forms.

If your captcha needs additional per-form settings, override `defineFormSettingsSchema()` and append your schema fields after the parent schema.

```php
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);

    $schema[] = SchemaHelper::lightswitchField([
        'label' => Craft::t('formie', 'Strict Validation'),
        'instructions' => Craft::t('formie', 'Whether this captcha should fail closed when validation cannot be completed.'),
        'name' => 'strictValidation',
    ]);

    return $schema;
}
```

## Front-End Behavior
Use `renderHtml()` for markup and `getClientModule()` when the captcha needs JavaScript behavior. The client module keeps captcha behavior attached to the form lifecycle, so it can mount when the form appears, clean itself up when the form is replaced, and run checks before Formie submits the form.

Captcha modules should usually use `defineCaptchaModule()` from `@verbb/formie-browser`. It gives you shared services for finding placeholders, writing submitted token values, rendering inline errors and blocking the screen stage when the provider cannot produce a token.

```ts
import { defineCaptchaModule } from '@verbb/formie-browser';

type ExampleCaptchaApi = {
  render: (container: HTMLElement, options: Record<string, unknown>) => {
    destroy: () => void;
    execute: () => Promise<string>;
    reset: () => void;
  };
};

type ExampleCaptchaOptions = {
  siteKey?: string | null;
};

async function loadExampleCaptchaApi(): Promise<ExampleCaptchaApi> {
  const existing = window.ExampleCaptcha as ExampleCaptchaApi | undefined;

  if (existing) {
    return existing;
  }

  await import('https://captcha.example.test/sdk.js');

  return window.ExampleCaptcha as ExampleCaptchaApi;
}

export default defineCaptchaModule<ExampleCaptchaOptions, ExampleCaptchaApi, ReturnType<ExampleCaptchaApi['render']>>({
  id: 'example-captcha',
  defaultPlaceholderSelector: '[data-example-captcha-placeholder]',
  defaultTokenFieldNames: ['example-captcha-token'],

  load: () => {
    return loadExampleCaptchaApi();
  },

  mount: ({ api, container, provider, services }) => {
    return api.render(container, {
      siteKey: provider.siteKey || '',
      callback: (token?: string) => {
        if (token) {
          services.tokens.write(token);
        }

        services.errors.clear();
      },
      expiredCallback: () => {
        services.tokens.clear();
      },
    });
  },

  screen: async ({ widget, placeholder, services, stageCtx }) => {
    if (!services.tokens.has()) {
      const token = await widget.execute();

      if (token) {
        services.tokens.write(token);
      }
    }

    if (!services.tokens.has()) {
      const message = services.errors.getDefaultMessage();

      services.errors.show(message, placeholder);
      stageCtx.abort(message);
    }
  },

  unmount: ({ widget, services }) => {
    widget.destroy();
    services.tokens.clear();
  },

  reset: ({ widget, services }) => {
    widget.reset();
    services.tokens.clear();
  },
});
```

In this example, the PHP integration registers the module with `new ClientModule([...])`, and the browser module writes the solved token to `example-captcha-token`. That is the same name `validateSubmission()` reads with `getCaptchaValue()`.

`getRefreshJsVariables()` is available for captcha providers that need data when Formie refreshes captcha-related front-end state. `getGqlVariables()` is available when a captcha needs to expose values for GraphQL mutation handling.
