# Form Queries

You can fetch forms in your templates or PHP code using **form queries**.

:::code
```twig Twig
{# Create a new form query #}
{% set myQuery = craft.formie.forms() %}
```

```php PHP
// Create a new form query
$myQuery = \verbb\formie\elements\Form::find();
```
:::

Once you’ve created a form query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Form](docs:developers/form) objects will be returned.

:::tip
See Introduction to [Element Queries](https://craftcms.com/docs/4.x/element-queries/) in the Craft docs to learn about how element queries work.
:::

## Example

We can display a form for a given handle by doing the following:

1. Create a form query with `craft.formie.forms()`.
2. Set the [handle](#handle) parameter on it.
3. Fetch a single form with `.one()` and output.

```twig
{# Create a form query with the 'handle' parameter #}
{% set formQuery = craft.formie.forms()
    .handle('contactForm') %}

{# Fetch the Form #}
{% set form = formQuery.one() %}

{# Display their contents #}
<p>{{ form.title }}</p>
```

## Parameters

Form queries support the following parameters:

<!-- BEGIN PARAMS -->

| Param                                     | Description
| ----------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
| [asArray](#asarray)                           | Causes the query to return matching forms as arrays of data, rather than Form objects.
| [dateCreated](#datecreated)                   | Narrows the query results based on the forms’ creation dates.
| [dateUpdated](#dateupdated)                   | Narrows the query results based on the forms’ last-updated dates.
| [fixedOrder](#fixedorder)                     | Causes the query results to be returned in the order specified by [id](#id).
| [id](#id)                                     | Narrows the query results based on the forms’ IDs.
| [inReverse](#inreverse)                       | Causes the query results to be returned in reverse order.
| [limit](#limit)                               | Determines the number of forms that should be returned.
| [offset](#offset)                             | Determines how many forms should be skipped in the results.
| [orderBy](#orderby)                           | Determines the order that the forms should be returned in. (If empty, defaults to `postDate DESC`.)
| [relatedTo](#relatedto)                       | Narrows the query results to only forms that are related to certain other elements.
| [template](#template)                         | Narrows the query results based on the forms’ template.
| [templateId](#templateId)                     | Narrows the query results based on the forms’ template, per their IDs.
| [title](#title)                               | Narrows the query results based on the forms’ titles.
| [trashed](#trashed)                           | Narrows the query results to only forms that have been soft-deleted.
| [uid](#uid)                                   | Narrows the query results based on the forms’ UIDs.


### `asArray`

Causes the query to return matching forms as arrays of data, rather than [Form](docs:developers/form) objects.

::: code
```twig Twig
{# Fetch forms as arrays #}
{% set forms = craft.formie.forms()
    .asArray()
    .all() %}
```

```php PHP
// Fetch forms as arrays
$forms = \verbb\formie\elements\Form::find()
    ->asArray()
    ->all();
```
:::



### `dateCreated`

Narrows the query results based on the forms’ creation dates.

Possible values include:

| Value | Fetches forms…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch forms created last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set forms = craft.formie.forms()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch forms created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$forms = \verbb\formie\elements\Form::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateUpdated`

Narrows the query results based on the forms’ last-updated dates.

Possible values include:

| Value | Fetches forms…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch forms updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set forms = craft.formie.forms()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch forms updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$forms = \verbb\formie\elements\Form::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::



### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig Twig
{# Fetch forms in a specific order #}
{% set forms = craft.formie.forms()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch forms in a specific order
$forms = \verbb\formie\elements\Form::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::



### `id`

Narrows the query results based on the forms’ IDs.

Possible values include:

| Value | Fetches forms…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch the form by its ID #}
{% set form = craft.formie.forms()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the form by its ID
$form = \verbb\formie\elements\Form::find()
    ->id(1)
    ->one();
```
:::

::: tip
This can be combined with [fixedOrder](#fixedorder) if you want the results to be returned in a specific order.
:::



### `inReverse`

Causes the query results to be returned in reverse order.

::: code
```twig Twig
{# Fetch forms in reverse #}
{% set forms = craft.formie.forms()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch forms in reverse
$forms = \verbb\formie\elements\Form::find()
    ->inReverse()
    ->all();
```
:::



### `limit`

Determines the number of forms that should be returned.

::: code
```twig Twig
{# Fetch up to 10 forms  #}
{% set forms = craft.formie.forms()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 forms
$forms = \verbb\formie\elements\Form::find()
    ->limit(10)
    ->all();
```
:::



### `offset`

Determines how many forms should be skipped in the results.

::: code
```twig Twig
{# Fetch all forms except for the first 3 #}
{% set forms = craft.formie.forms()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all forms except for the first 3
$forms = \verbb\formie\elements\Form::find()
    ->offset(3)
    ->all();
```
:::



### `orderBy`

Determines the order that the forms should be returned in.

::: code
```twig Twig
{# Fetch all forms in order of date created #}
{% set forms = craft.formie.forms()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all forms in order of date created
$forms = \verbb\formie\elements\Form::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::



### `relatedTo`

Narrows the query results to only forms that are related to certain other elements.

See [Relations](https://craftcms.com/docs/4.x/relations.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all forms that are related to myCategory #}
{% set forms = craft.formie.forms()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all forms that are related to $myCategory
$forms = \verbb\formie\elements\Form::find()
    ->relatedTo($myCategory)
    ->all();
```
:::



### `template`

Narrows the query results based on the forms’ templates.

Possible values include:

| Value | Fetches forms…
| - | -
| `'foo'` | of a template with a handle of `foo`.
| `'not foo'` | not of a template with a handle of `foo`.
| `['foo', 'bar']` | of a template with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not of a template with a handle of `foo` or `bar`.
| a Form Template object | of a template represented by the object.

::: code
```twig Twig
{# Fetch forms with a Foo form template #}
{% set forms = craft.formie.forms()
    .template('foo')
    .all() %}
```

```php PHP
// Fetch forms with a Foo form template
$forms = \verbb\formie\elements\Form::find()
    ->template('foo')
    ->all();
```
:::



### `templateId`

Narrows the query results based on the forms’ templates, per the templates’ IDs.

Possible values include:

| Value | Fetches forms…
| - | -
| `1` | of a template with an ID of 1.
| `'not 1'` | not of a template with an ID of 1.
| `[1, 2]` | of a template with an ID of 1 or 2.
| `['not', 1, 2]` | not of a template with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch forms of the form template with an ID of 1 #}
{% set forms = craft.formie.forms()
    .templateId(1)
    .all() %}
```

```php PHP
// Fetch forms of the form template with an ID of 1
$forms = \verbb\formie\elements\Form::find()
    ->templateId(1)
    ->all();
```
:::


### `title`

Narrows the query results based on the forms’ titles.

Possible values include:

| Value | Fetches forms…
| - | -
| `'Foo'` | with a title of `Foo`.
| `'Foo*'` | with a title that begins with `Foo`.
| `'*Foo'` | with a title that ends with `Foo`.
| `'*Foo*'` | with a title that contains `Foo`.
| `'not *Foo*'` | with a title that doesn’t contain `Foo`.
| `['*Foo*', '*Bar*'` | with a title that contains `Foo` or `Bar`.
| `['not', '*Foo*', '*Bar*']` | with a title that doesn’t contain `Foo` or `Bar`.

::: code
```twig Twig
{# Fetch forms with a title that contains "Foo" #}
{% set forms = craft.formie.forms()
    .title('*Foo*')
    .all() %}
```

```php PHP
// Fetch forms with a title that contains "Foo"
$forms = \verbb\formie\elements\Form::find()
    ->title('*Foo*')
    ->all();
```
:::



### `trashed`

Narrows the query results to only forms that have been soft-deleted.

::: code
```twig Twig
{# Fetch trashed forms #}
{% set entries = craft.formie.forms()
    .trashed()
    .all() %}
```

```php PHP
// Fetch trashed forms
$forms = \verbb\formie\elements\Form::find()
    ->trashed()
    ->all();
```
:::



### `uid`

Narrows the query results based on the forms’ UIDs.

::: code
```twig Twig
{# Fetch the form by its UID #}
{% set form = craft.formie.forms()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the form by its UID
$form = \verbb\formie\elements\Form::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::

<!-- END PARAMS -->
