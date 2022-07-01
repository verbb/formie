# Custom Integration
You can add your own custom integrations to be compatible with Formie by using the provided events.

```php
use modules\ExampleCaptcha;
use modules\ExampleAddressProvider;
use modules\ExampleElement;

use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
    $event->captchas[] = ExampleCaptcha::class;
    $event->addressProviders[] = ExampleAddressProvider::class;
    $event->elements[] = ExampleElement::class;
    $event->emailMarketing[] = ExampleEmailMarketing::class;
    $event->crm[] = ExampleCrm::class;
    $event->webhooks[] = ExampleWebhooks::class;
    $event->miscellaneous[] = ExampleMiscellaneous::class;
    // ...
});
```

## Captcha
For a Captcha integration, you should extend from the `Captcha` class, which in turn implements the `IntegrationInterface`.

### Attributes

Attribute | Description
--- | ---
`handle` | Create a handle for the captcha.

### Methods

Method | Description
--- | ---
`getName()` | Returns the name to be used for the captcha.
`getIconUrl()` | Returns the icon to show in the control panel, if applicable.
`getDescription()` | Returns the description for the captcha.
`getFormSettingsHtml()` | Returns the HTML for the settings for this captcha, as shown when editing a form.
`getFrontEndHtml()` | Returns the HTML for the captcha, as shown on the front-end.
`getFrontEndJsVariables()` | Returns the JS for the captcha, as shown on the front-end. This should be an array of variables to pass to your JS class.
`getSettingsHtml()` | Returns the HTML for the settings for this captcha, as shown in Formie's settings.
`validateSubmission()` | Returns true/false on whether a submission is valid.

### Example

```php
<?php
namespace modules;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;

class ExampleCaptcha extends Captcha
{
    public $handle = 'exampleCaptcha';

    public function getName(): string
    {
        return Craft::t('formie', 'Example Captcha');
    }

    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl("@modules/mymodule/resources/icon.svg", true);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'This is an example captcha.');
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
        ]);
    }

    public function getFrontEndHtml(Form $form, $page = null): string
    {
        return '<input type="hidden" name="example-captcha" value="Testing captcha" />';
    }

    public function getFrontEndJsVariables(Form $form, $page = null)
    {
        $src = Craft::$app->getAssetManager()->getPublishedUrl('path-to-js.js', true);
        $onload = 'new MyCustomFunction();';

        return [
            'src' => $src,
            'onload' => $onload,
        ];
    }

    public function validateSubmission(Submission $submission): bool
    {
        // Check the provided value
        $value = Craft::$app->getRequest()->post('example-captcha');

        if ($value !== 'Testing captcha') {   
            return false;            
        }

        return true;
    }
}
```

## Address Provider
For an Address Provider integration, you should extend from the `AddressProvider` class, which in turn implements the `IntegrationInterface`.

### Methods

Method | Description
--- | ---
`displayName()` | Returns the name to be used for the integration.
`getIconUrl()` | Returns the icon to show in the control panel, if applicable.
`getDescription()` | Returns the description for the integration.
`getSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in Formie's settings.
`getFrontEndHtml()` | Returns the HTML for the front-end field.
`getFrontEndJsVariables()` | Returns the JS for the front-end field. This should be an array of variables to pass to your JS class.

### Example

```php
<?php
namespace modules;

use verbb\formie\base\AddressProvider;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;

class ExampleAddressProvider extends AddressProvider
{
    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Address Provider');
    }

    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl("@modules/mymodule/resources/icon.svg", true);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'This is an example address provider.');
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
        ]);
    }

    public function getFrontEndHtml($field, $options): string
    {
        return '<input type="hidden" />';
    }

    public function getFrontEndJsVariables(Form $form, $field = null)
    {
        $src = Craft::$app->getAssetManager()->getPublishedUrl('path-to-js.js', true);
        $onload = 'new MyCustomFunction();';

        return [
            'src' => $src,
            'onload' => $onload,
        ];
    }
}
```

## Element
For an Element integration, you should extend from the `Element` class, which in turn implements the `IntegrationInterface`.

### Methods

Method | Description
--- | ---
`displayName()` | Returns the name to be used for the integration.
`getIconUrl()` | Returns the icon to show in the control panel, if applicable.
`getDescription()` | Returns the description for the integration.
`getSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in Formie's settings.
`getFormSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in a form's settings.
`fetchFormSettings()` | Returns a `IntegrationFormSettings` available for the integration.
`getElementAttributes()` | Returns an array of attributes available for the element.
`sendPayload()` | This function is called by the queue job to actually save the element, after the submission has been successful.

### Example

```php
<?php
namespace modules;

use verbb\formie\base\Element;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;

class ExampleElement extends Element
{
    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Element');
    }

    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl("@modules/mymodule/resources/icon.svg", true);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'This is an example element.');
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function fetchFormSettings()
    {
        $customFields = [];

        // Create `IntegrationField` instances for every field
        foreach ($elementGroup->getFields() as $field) {
            $fields[] = new IntegrationField([
                'handle' => $field->id,
                'name' => $field->name,
                'type' => get_class($field),
                'required' => $field->required,
            ]);
        }

        return new IntegrationFormSettings([
            'elements' => $customFields,
            'attributes' => $this->getElementAttributes(),
        ]);
    }

    public function getElementAttributes()
    {
        return [
            new IntegrationField([
                'name' => Craft::t('app', 'Title'),
                'handle' => 'title',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Slug'),
                'handle' => 'slug',
            ]),
        ];
    }

    public function sendPayload(Submission $submission): bool
    {
        $element = new YourElement();

        // Fetch the attribute values from mapping
        $attributeValues = $this->getFieldMappingValues($submission, $this->attributeMapping, $this->getElementAttributes());
        
        // Populate the attributes from attribute-mapping
        foreach ($attributeValues as $elementFieldHandle => $fieldValue) {
            $element->{$elementFieldHandle} = $fieldValue;
        }

        // Fetch the form settings for `elements` - we need this to parse values correctly
        $fields = $this->getFormSettingValue('elements')->fields ?? [];

        // Fetch the field values from mapping
        $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);

        // Populate the custom field values
        $element->setFieldValues($fieldValues);

        // Save the element
        if (!Craft::$app->getElements()->saveElement($element)) {
            return false;
        }

        return true;
    }
}
```


## Email Marketing

For an Email Marketing integration, you should extend from the `EmailMarketing` class, which in turn implements the `IntegrationInterface`.

### Methods

Method | Description
--- | ---
`displayName()` | Returns the name to be used for the integration.
`getIconUrl()` | Returns the icon to show in the control panel, if applicable.
`getDescription()` | Returns the description for the integration.
`getSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in Formie's settings.
`getFormSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in a form's settings.
`fetchFormSettings()` | Returns a `IntegrationFormSettings` used on the form setting, and when refreshing the available lists.
`sendPayload()` | This function is called by the queue job to send a payload to the provider, after the submission has been successful.

### Example

```php
<?php
namespace modules;

use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;

class ExampleEmailMarketing extends EmailMarketing
{
    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Email Marketing');
    }

    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl("@modules/mymodule/resources/icon.svg", true);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'This is an example email marketing integration.');
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function fetchFormSettings()
    {
        $settings = [];

        // Fetch your lists from your provider's API
        $lists = $this->request('GET', 'lists');

        foreach ($lists as $list) {
            // Fetch your lists' fields from your provider's API
            $fields = $this->request('GET', 'fields');

            // Add some standard fields not included from the API
            $listFields = [
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
            ];

            // Add the custom fields to our collection
            foreach ($fields as $key => $field) {
                $listFields[] = new IntegrationField([
                    'handle' => $field['id'],
                    'name' => $field['name'],
                    'type' => $field['type'],
                ]);
            }

            // Add a new collection to our settings, including fields specific for it
            $settings['lists'][] = new IntegrationCollection([
                'id' => $list['id'],
                'name' => $list['name'],
                'fields' => $listFields,
            ]);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        // Fetch the form settings for our field mapping
        $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

        // Construct a payload to send
        $payload = [
            'listId' => $this->listId,
            'contact' => $fieldValues,
        ];

        // Send the payload to our API endpoint via POST request. This also handles the before/after sending payload
        // events, which we can check on next
        $response = $this->deliverPayload($submission, 'subscribe', $payload);

        // The response might have failed, or an event returned this as invalid
        if ($response === false) {
            return false;
        }

        return true;
    }
}
```


## CRM

For a CRM integration, you should extend from the `Crm` class, which in turn implements the `IntegrationInterface`.

### Methods

Method | Description
--- | ---
`displayName()` | Returns the name to be used for the integration.
`getIconUrl()` | Returns the icon to show in the control panel, if applicable.
`getDescription()` | Returns the description for the integration.
`getSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in Formie's settings.
`getFormSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in a form's settings.
`fetchFormSettings()` | Returns a `IntegrationFormSettings` used on the form setting, and when refreshing the available data.
`sendPayload()` | This function is called by the queue job to send a payload to the provider, after the submission has been successful.

### Example

```php
<?php
namespace modules;

use verbb\formie\base\Crm;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;

class ExampleCrm extends Crm
{
    public $contactFieldMapping;
    public $dealFieldMapping;
    public $leadFieldMapping;

    public static function displayName(): string
    {
        return Craft::t('formie', 'Example CRM');
    }

    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl("@modules/mymodule/resources/icon.svg", true);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'This is an example email marketing integration.');
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function fetchFormSettings()
    {
        // Build a collection of fields for each data object we want to map
        $contactFields = [
            new IntegrationField([
                'handle' => 'email',
                'name' => Craft::t('formie', 'Email'),
                'required' => true,
            ]),
            new IntegrationField([
                'handle' => 'name',
                'name' => Craft::t('formie', 'Name'),
            ]),
        ];

        $dealFields = [
            new IntegrationField([
                'handle' => 'title',
                'name' => Craft::t('formie', 'Title'),
                'required' => true,
            ]),
        ];

        $leadFields = [
            new IntegrationField([
                'handle' => 'title',
                'name' => Craft::t('formie', 'Title'),
                'required' => true,
            ]),
        ];

        return new IntegrationFormSettings([
            'contact' => $contactFields,
            'deal' => $dealFields,
            'lead' => $leadFields,
        ]);
    }

    public function sendPayload(Submission $submission): bool
    {
        // Fetch the form settings for our field mapping - for each collection of data
        $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');
        $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');
        $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');

        // Construct and send our Contact data
        $response = $this->deliverPayload($submission, 'contacts', [
            'contact' => $contactValues,
        ]);

        if ($response === false) {
            return false;
        }

        // Construct and send our Deal data
        $response = $this->deliverPayload($submission, 'deals', [
            'deal' => $dealValues,
        ]);

        if ($response === false) {
            return false;
        }

        // Construct and send our Lead data
        $response = $this->deliverPayload($submission, 'leads', [
            'lead' => $leadValues,
        ]);

        if ($response === false) {
            return false;
        }

        return true;
    }
}
```


## Webhook

For a Webhook integration, you should extend from the `Webhook` class, which in turn implements the `IntegrationInterface`.

### Methods

Method | Description
--- | ---
`displayName()` | Returns the name to be used for the integration.
`getIconUrl()` | Returns the icon to show in the control panel, if applicable.
`getDescription()` | Returns the description for the integration.
`getSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in Formie's settings.
`getFormSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in a form's settings.
`fetchFormSettings()` | Returns a `IntegrationFormSettings` used on the form setting, and when refreshing the available data.
`sendPayload()` | This function is called by the queue job to send a payload to the provider, after the submission has been successful.

### Example

```php
<?php
namespace modules;

use verbb\formie\base\Webhook;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;

class ExampleWebhook extends Webhook
{
    public $webhook;

    public static function displayName(): string
    {
        return Craft::t('formie', 'Example CRM');
    }

    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl("@modules/mymodule/resources/icon.svg", true);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'This is an example email marketing integration.');
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        return Craft::$app->getView()->renderTemplate('path/to/settings', [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function fetchFormSettings()
    {
        // Fetch the form from our POST param
        $formId = Craft::$app->getRequest()->getParam('formId');
        $form = Formie::$plugin->getForms()->getFormById($formId);

        // Generate a new submission
        $submission = new Submission();
        $submission->setForm($form);

        // Populate the submission with fake data, for testing
        Formie::$plugin->getSubmissions()->populateFakeSubmission($submission);

        // Use Formie's function to generate a payload to send
        $payload = $this->generatePayloadValues($submission);
        $response = $this->getClient()->request('POST', $this->webhook, $payload);

        $json = Json::decode((string)$response->getBody());

        return new IntegrationFormSettings([
            'response' => $response,
            'json' => $json,
        ]);
    }

    public function sendPayload(Submission $submission): bool
    {
        // Generate a payload of values to send to the webhook
        $payload = $this->generatePayloadValues($submission);

        // Send the content to the webhook URL
        $response = $this->getClient()->request('POST', $this->webhook, $payload);

        return true;
    }
}
```



## Miscellaneous

For a Miscellaneous integration, you should extend from the `Miscellaneous` class, which in turn implements the `IntegrationInterface`. These integrtions don't follow a particular pattern, but can be more-or-less the same as any of the above.

For instance, Formie's Slack integration acts similar to an Email Marketing integration, where you pick a channel to post a message to. But you can also provide a webhook URL to send a webhook payload to - essentially combining multiple integration types. As such, a Miscellaneous integration can be whatever you make it!



## Form Settings
For a number of integrations (CRM, Element, Email Marketing, Miscellaneous) there is a `fetchFormSettings()` function. This is used to render the options available when editing a form for the integration. Commonly, this will be to map fields for your form, with the available API fields - but depending on your needs, you can do what you require.

All that's required is you return a `IntegrationFormSettings` object from this function.

This function is also called when using some Vue components to refresh the available settings.

### `IntegrationFormSettings`
An `IntegrationFormSettings` defines the available 'collections' of information available to your integration for the form. When creating the object, you should provide an array, which key you'll use in your template. For example, for a CRM integration, there are a number of different collections of data we might need, for contacts, leads and deals.

```php
$contactFields = [
    new IntegrationField([
        'handle' => 'name',
        'name' => Craft::t('formie', 'Name'),
        'required' => true,
    ]),
    // ...
];

$dealFields = [
    new IntegrationField([
        'handle' => 'name',
        'name' => Craft::t('formie', 'Name'),
        'required' => true,
    ]),
    // ...
];

$leadFields = [
    new IntegrationField([
        'handle' => 'name',
        'name' => Craft::t('formie', 'Name'),
        'required' => true,
    ]),
    // ...
];


$settings = new IntegrationFormSettings([
    'contact' => $contactFields,
    'deal' => $dealFields,
    'lead' => $leadFields,
])
```

You'll notice that we're sending back a collection of `IntegrationField`, which we'll go through below. From this returned object, we'll be able to access `contact`, `deal`and `lead` data in our templates. In practice, you're free to name these whatever you like.

#### Attributes

Attribute | Description
--- | ---
`collections` | An array of all collections/settings when the object was created.

#### Methods

Method | Description
--- | ---
`getSettings()` | Returns the `collections`.
`getSettingsByKey()` | Returns a provided key-path in the collection. Support dot-notation keys.



### `IntegrationCollection`
In the above example, you can see we're returning a collection of fields under a particular namespace. You may also require an additional layer of grouping for fields. This is common for Email Marketing and Element integrations.

For example - let's consider mapping to Entry elements. There are multiple sections on your site, with each section having multiple entry types. Each entry type defined the field layout. We want to be able to provide a dropdown in the integration settings to select which section and entry type to map submissions to - but there can be multiple different field layouts.

```php
$customFields = [];

$sections = Craft::$app->getSections()->getAllSections();

foreach ($sections as $section) {
    if ($section->type === 'single') {
        continue;
    }

    foreach ($section->getEntryTypes() as $entryType) {
        $fields = [];

        foreach ($entryType->getFieldLayout()->getCustomFields() as $field) {
            $fields[] = new IntegrationField([
                'handle' => $field->handle,
                'name' => $field->name,
                'type' => get_class($field),
                'required' => (bool)$field->required,
            ]);
        }

        $customFields[$section->name][] = new IntegrationCollection([
            'id' => $entryType->id,
            'name' => $entryType->name,
            'fields' => $fields,
        ]);
    }
}

return new IntegrationFormSettings([
    'elements' => $customFields,
    'attributes' => $this->getElementAttributes(),
]);
```

Here, we build a nested structure comprised of a single `IntegrationFormSettings` containing multiple `IntegrationCollection`, containing multiple `IntegrationField`. This would produce an object, represented in JSON:

```json
{
    "elements": {
        "Blog": [
            {
                "id": "123",
                "name": "Regular Article",
                "fields": {
                    "handle": "plainText",
                    "name": "Plain Text",
                    "type": "craft\\elements\\Entry",
                    "required": false
                }
                //...
            },
            {
                "id": "342",
                "name": "Sponsored Article",
                "fields": {
                    "handle": "plainText",
                    "name": "Plain Text",
                    "type": "craft\\elements\\Entry",
                    "required": false
                }
                //...
            }
        ]
        //...
    }
}
```

With this nested structure, we can use the Vue components to render the correct fields for the correct entry type picked.

The example is similar for Email Marketing. Your account might have different lists, and due to having custom fields available to be customised per-list for most providers, we need a way to switch the available fields depending on the list chosen.

```php
$settings = [];

// Fetch your lists from your provider's API
$lists = $this->request('GET', 'lists');

foreach ($lists as $list) {
    // Fetch your lists' fields from your provider's API
    $fields = $this->request('GET', 'fields');

    // Add some standard fields not included from the API
    $listFields = [
        new IntegrationField([
            'handle' => 'email',
            'name' => Craft::t('formie', 'Email'),
            'required' => true,
        ]),
    ];

    // Add the custom fields to our collection
    foreach ($fields as $key => $field) {
        $listFields[] = new IntegrationField([
            'handle' => $field['id'],
            'name' => $field['name'],
            'type' => $field['type'],
        ]);
    }

    // Add a new collection to our settings, including fields specific for it
    $settings['lists'][] = new IntegrationCollection([
        'id' => $list['id'],
        'name' => $list['name'],
        'fields' => $listFields,
    ]);
}

return new IntegrationFormSettings($settings);
```

```json
{
    "lists": [
        {
            "id": "hjg34hsdfsjg",
            "name": "Newsletter",
            "fields": [
                //...
            ],
        },
        {
            "id": "213hjk342hk",
            "name": "Sponsors",
            "fields": [
                //...
            ],
        }
    ],
}
```

#### Attributes

Attribute | Description
--- | ---
`id` | The id for the collection. This should reflect the identifier for the item according to the provider.
`name` | The label or title for the collection.
`fields` | A collection of `IntegrationField`.



### `IntegrationField`
An `IntegrationField` object is used to represent a field from a third party. Whether that's from an email marketing API, or a Craft element field.

#### Attributes

Attribute | Description
--- | ---
`name` | The label or title for the field.
`handle` | The handle for the field. This should reflect the identifier for the field according to the provider.
`type` | The type for the field. This is used to convert Formie field data into the format your provider requires. You should use one of the following type constants.
`required` | Whether the field should be required.
`options` | An array of additional options, able to be selected.

#### Types
If not specified, the default type of `IntegrationField` will be `TYPE_STRING`.

Attribute
---
`TYPE_STRING`
`TYPE_NUMBER`
`TYPE_BOOLEAN`
`TYPE_DATE`
`TYPE_DATETIME`
`TYPE_ARRAY`


## Template Usage
In your templates, you should always call `getFormSettings()` which provides cached settings. When clicking the refresh buttons, it will bypass the cache and fetch fresh data from your `fetchFormSettings()` methods.

```twig
{% set handle = integration.handle %}
{% set formSettings = integration.getFormSettings().getSettings() %}

<integration-form-settings handle="{{ handle }}" :form-settings="{{ formSettings | json_encode }}" inline-template>
// ...
```

You can also use a number of our Vue components to mimic what Formie's core integrations do, or roll your own templates. 

### `IntegrationFormSettings` Component
This component acts as a wrapper around your HTML, and can facilitate refreshing form settings. You can have all manner of HTML in this component, and using the `inline-template` you can even include Vue template code.

As an example, most integrations need to refresh the available form settings data. As per the above, we populate the component immediately with Twig, but we'll need a way to refresh it on-demand. You can use the `refresh` method:

```twig
<button class="btn" :class="{ 'fui-loading fui-loading-sm': loading }" data-icon="refresh" @click.prevent="refresh">{{ 'Refresh Integration' | t('formie') }}</button>
```

### `IntegrationFieldMapping` Component
Most integrations feature a way to map your form fields to fields your integration provider uses. You can use this component to create a table of these fields, with the first column being the provider field, the second column being a dropdown of columns in your form.

This dropdown will include any `options` defined in the `IntegrationField`, Submission information, and all your form fields to pick from. These are then saved in your form settings, to be retrieved when sending the integration payload.

Refer to the below example:

```twig
<integration-field-mapping
    label="{{ 'Contact Field Mapping' | t('formie') }}"
    instructions="{{ 'Choose how your form fields should map to your {name} Contact fields.' | t('formie', { name: integration.displayName() }) }}"
    name-label="{{ integration.displayName() }}"
    id="contact-field-mapping"
    name="contactFieldMapping"
    :value="get(form, 'settings.integrations.{{ handle }}.contactFieldMapping')"
    :rows="get(settings, 'contact')"
></integration-field-mapping>
```

For `:value` and `:rows`, we're using the `get()` function from Lodash. This allows us to provide a dot-notation key to get values from a collection. For `:rows`, we're essentially returning `settings.contact`, `settings` being what's passed into the `IntegrationFormSettings` component's `:form-settings` prop. Likewise for `:value`, we're fetching an existing value on the form's settings, which is where your mapping configuration is stored.



## OAuth 
If your custom provider requires OAuth integration - Formie can help with that! We've done the heavy-lifting with tokens, providing you with just a few methods to implement depending on the providers requirements. Formie supports [OAuth 1](https://github.com/thephpleague/oauth1-client/blob/master/src/Client/Server/Server.php) and [OAuth 2](https://github.com/thephpleague/oauth2-client).

For your provider to support OAuth, implement the below methods in your class:

```php
public static function supportsOauthConnection(): bool
{
    return true;
}

public function getAuthorizeUrl(): string
{
    return 'http://provider.site/authorize-url';
}

public function getAccessTokenUrl(): string
{
    return 'http://provider.site/token-url';
}

public function getClientId(): string
{
    return 'xxxxxxxxxxxxxxxxxxxxxxxx';
}

public function getClientSecret(): string
{
    return 'xxxxxxxxxxxxxxxxxxxxxxxx';
}
```

### Methods

Method | Description
--- | ---
`supportsOauthConnection()` | Whether this integration supports OAuth.
`getAuthorizeUrl()` | Return the URL to authorize.
`getAccessTokenUrl()` | Return the URL for generating the access token.
`getResourceOwner()` | Return the URL for the resource owner.
`getClientId()` | Return the client ID.
`getClientSecret()` | Return the client secret.
`getOauthScope()` | Return an array of scopes applicable to your provider.
`getOauthAuthorizationOptions()` | Return an array of any options to be provided when building the authorization URL.
`oauthVersion()` | Return either `1` or `2` to denote the OAuth version.
`getOauthProviderConfig()` | The config for the provider.
`getOauthProvider()` | Provider your own OAuth provider.
`beforeFetchAccessToken()` | Function called before an access token is fetched.
`afterFetchAccessToken()` | Function called after an access token is fetched.
