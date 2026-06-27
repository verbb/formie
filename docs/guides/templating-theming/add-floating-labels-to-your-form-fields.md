# Add floating labels to your form fields

You might be familiar with the concept of floating labels for forms. If not, take a look at [Bootstrap's demo](https://getbootstrap.com/docs/5.0/forms/floating-labels) for how these work. Essentially, instead of rendering the label above an `<input>` element, it's rendered inside like you'd see a placeholder do. When you focus on the input to type your content, the label shifts out of the way in a subtle animation.

In fact, we're going to be implementing Bootstrap's approach, thanks to its lack of JavaScript and high accessibility. We can also achieve everything we want without touching Twig templates, which will make a much more maintainable customisation.

### Create your module
First, you'll need to get familiar with [creating a module](/blog/everything-you-need-to-know-about-modules). The code we will be writing will be in PHP and added to our custom module. 

When creating your module, set the namespace to `modules\formiefloatinglabels` and the module ID to `formie-floating-labels`. You could also add the following to an existing module if you prefer.

Create the following directory and file structure:

```treeview
my-project/
├── modules/
│    └── formiefloatinglabels/
│        └── src/
│            └── positions/
│                └── Floating.php
│            └── FormieFloatingLabels.php
└── ...
```

```php
// modules/formiefloatinglabels/src/FormieFloatingLabels.php

<?php
namespace modules\formiefloatinglabels;

use Craft;
use craft\web\View;
use modules\formiefloatinglabels\positions\Floating;
use verbb\formie\base\FormField;
use verbb\formie\events\ModifyFieldHtmlTagEvent;
use verbb\formie\events\RegisterFieldOptionsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;
use yii\base\Module;

class FormieFloatingLabels extends Module
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        // Call the `Module::init()` method, which will do its own initializations
        parent::init();

        // Register our custom label position
        Event::on(Fields::class, Fields::EVENT_REGISTER_LABEL_POSITIONS, function (RegisterFieldOptionsEvent $event) {
            array_unshift($event->options, Floating::class);
        });

        // Configure Theme Config to add some classes when a label is floating
        Event::on(FormField::class, FormField::EVENT_MODIFY_HTML_TAG, function(ModifyFieldHtmlTagEvent $event) {
            // If the field's label is set to floating, add a class to style easier
            if ($event->field->labelPosition === Floating::class) {
                if ($event->key === 'fieldInputContainer') {
                    $event->tag->attributes['class'][] = 'form-floating';
                }

                if ($event->key === 'fieldInput') {
                    $event->tag->attributes['class'][] = 'form-floating-control';

                    // Override the placeholder, the label is used
                    $event->tag->attributes['placeholder'] = $event->field->name;
                }
            }
        });

        // Render the label in the brand-new position in default templates
        Craft::$app->getView()->hook('formie.field.input-end', static function(array &$context) {
            // Add a new `position` variable so that we can trick `labelPosition.shouldDisplay()`.
            $context['position'] = 'floating';

            // Render the label for the `floating` position. This is only shown when floating position is set,
            return Craft::$app->getView()->renderTemplate('formie/_special/form-template/_includes/label', $context, View::TEMPLATE_MODE_CP);
        });
    }
}
```

Next, add our class for our custom position.

```php
// modules/formiefloatinglabels/src/positions/Floating.php

<?php
namespace modules\formiefloatinglabels\positions;

use Craft;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\Position;
use verbb\formie\fields\formfields\Dropdown;
use verbb\formie\fields\formfields\Email;
use verbb\formie\fields\formfields\FileUpload;
use verbb\formie\fields\formfields\MultiLineText;
use verbb\formie\fields\formfields\Name;
use verbb\formie\fields\formfields\Number;
use verbb\formie\fields\formfields\Phone;
use verbb\formie\fields\formfields\SingleLineText;
use verbb\formie\positions\BelowInput;

class Floating extends Position
{
    // Properties
    // =========================================================================

    protected static ?string $position = 'floating';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Floating');
    }

    public static function supports(FormFieldInterface $field = null): bool
    {
        if (!$field) {
            return true;
        }

        return in_array(get_class($field), [
            SingleLineText::class,
            MultiLineText::class,
            Dropdown::class,
            Number::class,
            Email::class,
            FileUpload::class,
            Name::class,
            Phone::class,
        ]);
    }

    public static function fallback(FormFieldInterface $field = null): ?string
    {
        return BelowInput::class;
    }
}
```

Here, we can configure `supports()` to only allow this on some field types.

### How we'll make it happen
We're going to create a custom **Position** for labels in Formie, so that this behaviour is opt-in and content managed by users. We'll also be using [hooks](/craft-plugins/formie/docs/developers/hooks) to add Twig code to the default field template file. And finally, utilise [Theme Config](/craft-plugins/formie/docs/theming/theme-config) to add some classes to our elements if set to have a floating label.

We've already created and registered the custom position. You can head to any form, edit a field, and you should be able to pick the **Floating** option as a label position.

### Adding the floating label HTML
Take a look at a snippet of the default `field.html` template that Formie uses to output a field.

```twig
{% fieldtag 'fieldContainer' %}
    {% hook 'formie.field.input-before' %}

    {{ formieInclude('_includes/label', { position: 'above' }) }}
    {{ formieInclude('_includes/instructions', { position: 'above' }) }}

    // ...

    {{ formieInclude('_includes/label', { position: 'below' }) }}
    {{ formieInclude('_includes/instructions', { position: 'below' }) }}

    {% hook 'formie.field.input-after' %}
{% endfieldtag %}
```

Let's focus on the label includes. You'll notice that we call:

```twig
{{ formieInclude('_includes/label', { position: 'above' }) }}
and
{{ formieInclude('_includes/label', { position: 'below' }) }}
```

What this does is say "if the `field.labelPosition == 'above'` then render this include. Similarly, the second line, which allows us to conditionally render the label in different places. However, as soon as we pick neither `above` nor `below` for our label position, the label will disappear! That's because neither of these matches.

So, we want to add in `{{ formieInclude('_includes/label', { position: 'floating' }) }}` to this template, but using template overrides would be annoying, as we'd have to override the `field.html` file. Instead, notice how we have some `{% hook %}` tags? These are [Twig hooks](/craft-plugins/formie/docs/developers/hooks) which we can call in PHP.

Looking at our implementation in the main plugin class:

```php
// Render the label in the brand-new position in default templates
Craft::$app->getView()->hook('formie.field.input-end', function(&$context) {
    // Add a new `position` variable so that we can trick `labelPosition.shouldDisplay()`.
    $context['position'] = 'floating';

    // Render the label for the `floating` position. This is only shown when floating position is set,
    return Craft::$app->getView()->renderTemplate('formie/_special/form-template/_includes/label', $context, View::TEMPLATE_MODE_CP);
});
```

We're essentially doing just that, by rendering `formie/_special/form-template/_includes/label` (Formie's default label template), passing in `{ position: 'floating' }` as a variable. The label template will be looking for a `position` variable, which we supply here.

:::tip
We're using `formie.field.input-end` so that the label actually renders **after** the `<input>` which is required so we can utilize a CSS sibling selector for a CSS-only solution.
:::

As such, we've dynamically inserted Twig into the template! Now your label should render, but we're not quite done.

### Theme Config
With the label rendering in the right spot (after the `<input>`), we'll need to implement some CSS to get this working properly. To do that, we'll want to add some classes to the field's HTML. We can do this with Theme Config without touching any templates.

```php
Event::on(FormField::class, FormField::EVENT_MODIFY_HTML_TAG, function(ModifyFieldHtmlTagEvent $event) {
    // If the field's label is set to floating, add a class to style easier
    if ($event->field->labelPosition === Floating::class) {
        if ($event->key === 'fieldInputContainer') {
            $event->tag->attributes['class'][] = 'form-floating';
        }

        if ($event->key === 'fieldInput') {
            $event->tag->attributes['class'][] = 'form-floating-control';

            // Override the placeholder, the label is used
            $event->tag->attributes['placeholder'] = $event->field->name;
        }
    }
});
```

Here, we're checking if the field's label is positioned floating, adding a `form-floating` class to the field input container, and a `form-floating-control` to the input. We're also enforcing the placeholder attribute to be the field's label, as our method of CSS-only floating labels uses the `:placeholder-shown` pseudo-element.

We should be getting those classes output on form elements now when rendering the form.

Finally, let's add the CSS we need to make it all come together.

```twig
{% css %}

.form-floating {
    position: relative;
}

.form-floating > .form-floating-control {
    height: calc(3rem + 2px);
    line-height: 1.25;
}

.form-floating > label {
    position: absolute;
    top: 0;
    left: 0;
    padding: 1rem 0.75rem;
    pointer-events: none;
    transform-origin: 0 0;
    transition: opacity .1s ease-in-out,transform .1s ease-in-out;
    height: 100%;
    border: 1px solid transparent;
}

.form-floating > .form-floating-control {
    padding: 1rem .75rem;
}

.form-floating > .form-floating-control::placeholder {
    color: transparent;
}

.form-floating > .form-floating-control:focus,
.form-floating > .form-floating-control:not(:placeholder-shown) {
    padding-top: 1.625rem;
    padding-bottom: 0.625rem;
}

.form-floating > .form-floating-control:-webkit-autofill {
    padding-top: 1.625rem;
    padding-bottom: 0.625rem;
}

.form-floating > .form-floating-control:focus ~ label,
.form-floating > .form-floating-control:not(:placeholder-shown) ~ label {
    opacity: 0.65;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

.form-floating > .form-floating-control:-webkit-autofill ~ label {
    opacity: 0.65;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

{% endcss %}
```

:::tip
Of course, you don't have to write this in CSS-in-Twig! We just want a quick way to demo this for you.
:::

### Finishing up
With all that in place, you should have a working floating labels solution. We hope that helps the cogs turn with how creative you can get with extending Formie's rendering in a non-destructive and maintainable way.
