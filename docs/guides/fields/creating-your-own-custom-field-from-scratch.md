# Creating your own custom field from scratch (Formie 3)

In this guide, we'll create a brand-new field for your users to be able to pick in the form builder. We'll be building a URL field, which will be similar to a Single-Line Text field, but with some extra helpers for validation. So let's get stuck in!

:::tip
This guide is a good companion to the [custom field](https://verbb.io/craft-plugins/formie/docs/developers/custom-field) docs, so be sure to have a browse through the documentation first.
:::

#### Create your module
First, you'll need to get familiar with [creating a module](/blog/everything-you-need-to-know-about-modules). The code we will be writing will be in PHP, and added to our custom module. 

When creating your module, set the namespace to `modules\formieurlfield` and the module ID to `formie-url-field`.

Create the following directory and file structure:

```treeview
my-project/
├── modules/
│    └── formieurlfield/
│        └── src/
│            └── fields/
│                └── UrlField.php
│            └── templates/
│                └── email-template.html
│                └── form-template.html
│                └── icon.svg
│                └── input.html
│                └── preview.html
│            └── FormieUrlField.php
└── ...
```

```php
// modules/formieurlfield/src/FormieUrlField.php

<?php
namespace modules\formieurlfield;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use modules\formieurlfield\fields\UrlField;
use verbb\base\base\Module;
use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

class FormieUrlField extends Module
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        // Call the `Module::init()` method, which will do its own initializations
        parent::init();

        // Define a custom alias named after the namespace
        Craft::setAlias('@formie-url-field', __DIR__);

        // Register template roots to resolve our templates correctly
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $e) {
            $e->roots[$this->id] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
        });

        // Register our custom field
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, function(RegisterFieldsEvent $event) {
            $event->fields[] = UrlField::class;
        });
    }
}
```

Here our main module file is pretty simple. We tell Formie we want to register a new field class, which points to our `fields/UrlField.php` class. The bulk of our logic will be in this class.

We also set up a `@formie-url-field` alias, which we'll get to a little later on. It's also important we specify the "Template Roots" for the module, which help to resolve Twig templates correctly.

```php
// modules/formieurlfield/src/fields/UrlField.php

<?php
namespace modules\formieurlfield\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\errors\InvalidFieldException;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use Twig\Markup;
use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;

class UrlField extends Field
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'URL');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie-url-field/icon.svg';
    }


    // Public Methods
    // =========================================================================

    public function getFrontEndInputHtml(Form $form, mixed $value, array $renderOptions = []): Markup
    {
        $inputOptions = $this->getFrontEndInputOptions($form, $value, $renderOptions);
        $html = $form->renderTemplate('formie-url-field/form-template', $inputOptions);

        return Template::raw($html);
    }

    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        $inputOptions = $this->getEmailOptions($submission, $notification, $value, $renderOptions);
        $html = $notification->renderTemplate('formie-url-field/email-template', $inputOptions);

        return Template::raw($html);
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie-url-field/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie-url-field/preview', [
            'field' => $this
        ]);
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                'name' => 'defaultValue',
                'variables' => 'userVariables',
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $errors = $context['errors'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            return new HtmlTag('input', array_merge([
                'type' => 'text',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $dataId,
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes()));
        }

        return parent::defineHtmlTag($key, $context);
    }
}
```

Hopefully, most of the above functions should be self-documenting like naming the field and setting up templates. We also define "schema" functions, which allow us to create the HTML used when editing the field, while still being compatible with Vue (and FormKit). This is covered in further detail in [schema settings](https://verbb.io/craft-plugins/formie/docs/developers/custom-field#settings-schema).

We also use the `defineHtmlTag()` function to extend the Theme Config definition for the field, where we can control the HTML tag and attributes when rendering this field. In our case, we just want to manage the `<input>` attribute.

And finally, let's create some templates, which we'll use for the following:
- Twig template for rendering the field on the front-end.
- Twig template for rendering the field in an email.
- Twig template for when viewing a submission.
- Twig/Vue template the preview of the form in the form builder.
- An SVG icon, shown in the control panel.

```twig
// modules/formieurlfield/src/templates/form-template.html

{{ fieldtag('fieldInput', {
    value: value ?? false,
}) }}
```

This template is pretty lean because we're using Theme Config and the `defineHtmlTag()` to define the HTML and attributes. You could of course write just plain HTML `<input>`, or add more template code to this file if you wish.

```twig
// modules/formieurlfield/src/templates/email-template.html

<p>
    <strong>{{ field.name | t('formie') }}</strong><br>

    {% if value %}
        {{ value }}
    {% else %}
        {{ 'No response.' | t('formie') }}
    {% endif %}
</p>
```

When rendering the field in an email notification's content, this template will be used.

```twig
// modules/formieurlfield/src/templates/input.html

{% include '_includes/forms/text' %}
```

This template is shown when editing a submission in the control panel. As such, we can fall back on Craft's form macros to generate a text field for us.

```twig
// modules/formieurlfield/src/templates/preview.html

<input type="text" class="fui-field-input" :placeholder="field.settings.placeholder" :value="field.settings.defaultValue">
<span class="fui-field-icon">{{ field.getSvgIcon() | raw }}</span>
```

This is shown in the form builder when adding the field to the form. Any Twig or Vue code is allowed here.

```twig
// modules/formieurlfield/src/templates/icon.svg

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0zM256 480C272.7 480 296.4 465.6 317.9 422.7C327.8 402.9 336.1 378.1 341.1 352H170C175.9 378.1 184.2 402.9 194.1 422.7C215.6 465.6 239.3 480 256 480V480zM164.3 320H347.7C350.5 299.8 352 278.3 352 256C352 233.7 350.5 212.2 347.7 192H164.3C161.5 212.2 160 233.7 160 256C160 278.3 161.5 299.8 164.3 320V320zM341.1 160C336.1 133 327.8 109.1 317.9 89.29C296.4 46.37 272.7 32 256 32C239.3 32 215.6 46.37 194.1 89.29C184.2 109.1 175.9 133 170 160H341.1zM379.1 192C382.6 212.5 384 233.9 384 256C384 278.1 382.6 299.5 379.1 320H470.7C476.8 299.7 480 278.2 480 256C480 233.8 476.8 212.3 470.7 192H379.1zM327.5 43.66C348.5 71.99 365.1 112.4 374.7 160H458.4C432.6 105.5 385.3 63.12 327.5 43.66V43.66zM184.5 43.66C126.7 63.12 79.44 105.5 53.56 160H137.3C146.9 112.4 163.5 71.99 184.5 43.66V43.66zM32 256C32 278.2 35.24 299.7 41.28 320H132C129.4 299.5 128 278.1 128 256C128 233.9 129.4 212.5 132 192H41.28C35.24 212.3 32 233.8 32 256V256zM458.4 352H374.7C365.1 399.6 348.5 440 327.5 468.3C385.3 448.9 432.6 406.5 458.4 352zM137.3 352H53.56C79.44 406.5 126.7 448.9 184.5 468.3C163.5 440 146.9 399.6 137.3 352V352z"/></svg>
```

Give your field a nice icon! ✨

#### Test it out
That's it for the scaffolding of our module, let's test it out! Your new **URL** field should appear in the form builder for you to add to your form. Add the field and save your form, and preview your form on the front-end end. 

Everything look okay? Good, let's continue on!

### The URL field
Right now, our field acts as a pretty simple text-based field, but we're not really enforcing any validation about a URL, are we? We better make some additions to our class to make it feel a little more like a URL field. This'll also be an opportunity to show off some more common functionality with fields.

#### Validation
We want to validate the text entered into the field to only allow URLs. Any other content should throw an error. We can do this using `getElementValidationRules()`.

```php
use craft\validators\UrlValidator;

/**
 * @inheritDoc
 */
public function getElementValidationRules(): array
{
    $rules = parent::getElementValidationRules();

    $rules[] = [UrlValidator::class, 'pattern' => '/' . UrlValidator::URL_PATTERN . '/i'];

    return $rules;
}
```

Here, we're making use of Craft's own `UrlValidator` which makes things easy! Whenever the owner element (our Submission element) runs validation, it'll call each fields' `getElementValidationRules()` function, and run any validation rules supplied.

If you try and enter anything that isn't a URL, you'll now get an error message `Field is not a valid URL.`.

Continue reading about Yii's [validation syntax](https://www.yiiframework.com/doc/guide/2.0/en/input-validation) for more.

#### Front-end JavaScript
Some fields require companion JavaScript code to gain the functionality they require, and you can also define this in your field. Let's say for example we want to add some JavaScript to the front-end of the field that validates the URL as you type. 

Now sure, we could write this JavaScript as part of our overall project's front-end JavaScript code, but that makes it uncoupled from the field, and difficult to maintain. Not to mention, the JavaScript code for the field would run on **every** page load, even if it didn't contain a form, or if the form didn't contain a URL field. Not very efficient.

Instead, we can define a JavaScript "module" for Formie where we define a JavaScript file that the field should load, but it's only ever loaded if the field exists in the form.

To get started, we'll create some files, including an asset bundle that serves as instructions on where the JavaScript file exists on the file system.

```treeview
formieurlfield/
├── src/
│    └── assets/
│        └── urlfield/
│            └── UrlFieldAsset.php
│            └── js/
│                └── url-field.js
└── ...
```

```php
// modules/formieurlfield/src/assets/urlfield/UrlFieldAsset.php

<?php
namespace modules\formieurlfield\assets\urlfield;

use craft\web\AssetBundle;

class UrlFieldAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@formie-url-field/assets/urlfield';

        $this->js = [
            'js/url-field.js',
        ];

        parent::init();
    }
}
```

Here, using the alias we set up in our base module class, we set the base path of the asset bundle to the `modules/formieurlfield/src/assets/urlfield` directory. Then, we instruct where any JavaScript files are relative to that base path. This asset bundle will also serve the JavaScript file from your `cpresources` folder and handle caching.

Back to our `UrlField` class, create the following function to register it:

```php
// modules/formieurlfield/src/fields/UrlField.php

use modules\formieurlfield\assets\urlfield\UrlFieldAsset;

public function getFrontEndJsModules(): ?array
{
    // Prepare the asset bundle, and publish it to `cpresources`
    Craft::$app->getView()->registerAssetBundle(UrlFieldAsset::class);

    return [
        'src' => Craft::$app->getAssetManager()->getPublishedUrl('@formie-url-field/assets/urlfield/js/url-field.js', true),
        'module' => 'FormieUrlField',
    ];
}
```

By providing this function, when Formie renders the front-end, it'll lazy-load the `url-field.js` file from your `cpresources` directory — only if the form contains our URL field. Note that we've provided `FormieUrlField` as the module name, and this will relate to the JavaScript Class name we'll create next.

Now it's time to jump into the JavaScript side of things! Let's put together the scaffolding for this:

```js
window.FormieUrlField = class {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;

        console.log('URL field JS');
    }
}
```

All JavaScript code for the field should be contained within a [JavaScript Class](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Classes). Note that we've named this class `FormieUrlField` which must match the `module` setting we define in our `getFrontEndJsModules()` function.

When this class is created, it'll be provided with a `settings` object, which contains useful things like the DOM elements for the `<form>` and field (`$form` and `$field` respectively). These provide the required context for the form that this field is rendered in and the individual field.

The content of this JavaScript class is now entirely up to you to write however you like!

#### Front-end CSS
We might also like to add some styles to our field! We can follow the same steps as above with our asset bundle for adding JavaScript — this time adding CSS.

```php
// modules/formieurlfield/src/assets/urlfield/UrlFieldAsset.php

<?php
namespace modules\formieurlfield\assets\urlfield;

use craft\web\AssetBundle;

class UrlFieldAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@formie-url-field/assets/urlfield';

        $this->js = [
            'js/url-field.js',
        ];

        $this->css = [
            'css/url-field.css',
        ];

        parent::init();
    }
}
```

### Finishing up
That covers the basics of getting up and running with your very own custom field.
