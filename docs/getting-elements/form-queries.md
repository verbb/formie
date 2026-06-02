# Form Queries
You can fetch forms in your templates or PHP code using form queries.

::: code
```twig Twig
{# Create a new form query #}
{% set formQuery = craft.formie.forms() %}
```

```php PHP
// Create a new form query
$formQuery = \verbb\formie\elements\Form::find();
```
:::

Once you’ve created a query, set any parameters you need, then fetch the result with `.one()` or `.all()`. A form query returns [Form](/reference/form) objects unless you use `asArray()`.

::: tip
Formie form queries build on Craft element queries. See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries) in the Craft docs if you want the broader query syntax.
:::

## Fetch a form by handle
The most common use is fetching a form by its handle, then passing it to a render function or reading its properties.

::: code
```twig Twig
{% set form = craft.formie.forms()
    .handle('contactForm')
    .one() %}

{% if form %}
    {{ craft.formie.renderForm(form) }}
{% endif %}
```

```php PHP
$form = \verbb\formie\elements\Form::find()
    ->handle('contactForm')
    ->one();
```
:::

## Parameters
Form queries support Formie-specific parameters as well as Craft’s standard element query parameters.

Param | Description
--- | ---
[`handle`](#handle) | Narrows the query by form handle.
[`layoutId`](#layoutid) | Narrows the query by the form’s field layout ID.
[`pageCount`](#pagecount) | Narrows the query by the number of pages in the form.
[`template`](#template) | Narrows the query by form template handle or template object.
[`templateId`](#templateid) | Narrows the query by form template ID.
[`asArray`](#asarray) | Returns arrays instead of Form objects.
[`dateCreated`](#datecreated) | Narrows the query by creation date.
[`dateUpdated`](#dateupdated) | Narrows the query by last-updated date.
[`fixedOrder`](#fixedorder) | Returns results in the order specified by `id`.
[`id`](#id) | Narrows the query by element ID.
[`inReverse`](#inreverse) | Reverses the result order.
[`limit`](#limit) | Limits the number of results.
[`offset`](#offset) | Skips a number of results.
[`orderBy`](#orderby) | Sets the result order.
[`title`](#title) | Narrows the query by form title.
[`trashed`](#trashed) | Returns soft-deleted forms.
[`uid`](#uid) | Narrows the query by UID.

### `handle`
Narrows the query by form handle.

::: code
```twig Twig
{% set form = craft.formie.forms()
    .handle('contactForm')
    .one() %}
```

```php PHP
$form = \verbb\formie\elements\Form::find()
    ->handle('contactForm')
    ->one();
```
:::

### `layoutId`
Narrows the query by the form’s field layout ID.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .layoutId(12)
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->layoutId(12)
    ->all();
```
:::

### `pageCount`
Narrows the query by the number of pages in the form.

::: code
```twig Twig
{% set multiPageForms = craft.formie.forms()
    .pageCount('> 1')
    .all() %}
```

```php PHP
$multiPageForms = \verbb\formie\elements\Form::find()
    ->pageCount('> 1')
    ->all();
```
:::

### `template`
Narrows the query by form template handle or by a form template object.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .template('contact')
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->template('contact')
    ->all();
```
:::

### `templateId`
Narrows the query by form template ID.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .templateId(1)
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->templateId(1)
    ->all();
```
:::

### `asArray`
Returns arrays of data instead of [Form](/reference/form) objects.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .asArray()
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->asArray()
    ->all();
```
:::

### `dateCreated`
Narrows the query by the forms’ creation dates.

::: code
```twig Twig
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set forms = craft.formie.forms()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
$start = new \DateTime('first day of last month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$forms = \verbb\formie\elements\Form::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::

### `dateUpdated`
Narrows the query by the forms’ last-updated dates.

::: code
```twig Twig
{% set lastWeek = date('1 week ago')|atom %}

{% set forms = craft.formie.forms()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$forms = \verbb\formie\elements\Form::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::

### `fixedOrder`
Returns results in the same order as the IDs passed to `id`.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .id([3, 1, 2])
    .fixedOrder()
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->id([3, 1, 2])
    ->fixedOrder()
    ->all();
```
:::

### `id`
Narrows the query by element ID.

::: code
```twig Twig
{% set form = craft.formie.forms()
    .id(1)
    .one() %}
```

```php PHP
$form = \verbb\formie\elements\Form::find()
    ->id(1)
    ->one();
```
:::

### `inReverse`
Reverses the result order.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .inReverse()
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->inReverse()
    ->all();
```
:::

### `limit`
Limits the number of forms returned.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .limit(10)
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->limit(10)
    ->all();
```
:::

### `offset`
Skips a number of results.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .offset(3)
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->offset(3)
    ->all();
```
:::

### `orderBy`
Sets the result order.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::

### `title`
Narrows the query by form title.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .title('*Contact*')
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->title('*Contact*')
    ->all();
```
:::

### `trashed`
Returns forms that have been soft-deleted.

::: code
```twig Twig
{% set forms = craft.formie.forms()
    .trashed()
    .all() %}
```

```php PHP
$forms = \verbb\formie\elements\Form::find()
    ->trashed()
    ->all();
```
:::

### `uid`
Narrows the query by UID.

::: code
```twig Twig
{% set form = craft.formie.forms()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
$form = \verbb\formie\elements\Form::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
