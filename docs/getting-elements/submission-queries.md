# Submission Queries
You can fetch submissions in your templates or PHP code using submission queries.

::: code
```twig Twig
{# Create a new submission query #}
{% set submissionQuery = craft.formie.submissions() %}
```

```php PHP
// Create a new submission query
$submissionQuery = \verbb\formie\elements\Submission::find();
```
:::

Once you’ve created a query, set any parameters you need, then fetch the result with `.one()` or `.all()`. A submission query returns [Submission](/reference/submission) objects unless you use `asArray()`.

::: tip
Formie submission queries build on Craft element queries. See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries) in the Craft docs if you want the broader query syntax.
:::

## Fetch submissions for a form
The most common use is fetching submissions for a specific form.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .form('contactForm')
    .limit(10)
    .all() %}

{% for submission in submissions %}
    <p>{{ submission.title }}</p>
{% endfor %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->form('contactForm')
    ->limit(10)
    ->all();
```
:::

## Query by field value
Submission queries can also filter by Formie field handles. Use `field(handle, value)` when the handle is dynamic, or call the field handle directly when it is known.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .form('contactForm')
    .field('emailAddress', 'jane@example.com')
    .all() %}

{% set matchingSubmissions = craft.formie.submissions()
    .form('contactForm')
    .emailAddress('jane@example.com')
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->form('contactForm')
    ->field('emailAddress', 'jane@example.com')
    ->all();

$matchingSubmissions = \verbb\formie\elements\Submission::find()
    ->form('contactForm')
    ->emailAddress('jane@example.com')
    ->all();
```
:::

When you query by a field handle, Formie uses that field type’s query handling. Text fields, option fields, element fields, and nested fields may not all compare values in exactly the same way.

## Parameters
Submission queries support Formie-specific parameters as well as Craft’s standard element query parameters.

Param | Description
--- | ---
[`after`](#after) | Narrows the query to submissions created on or after a date.
[`anyStatus`](#anystatus) | Clears Formie’s default incomplete and spam filters.
[`asArray`](#asarray) | Returns arrays instead of Submission objects.
[`before`](#before) | Narrows the query to submissions created before a date.
[`field`](#field) | Narrows the query by a Formie field value.
[`form`](#form) | Narrows the query by form handle or Form object.
[`formId`](#formid) | Narrows the query by form ID.
[`isIncomplete`](#isincomplete) | Narrows the query by incomplete state.
[`isSpam`](#isspam) | Narrows the query by spam state.
[`status`](#status) | Narrows the query by submission status handle.
[`statusId`](#statusid) | Narrows the query by submission status ID.
[`user`](#user) | Narrows the query by owner user object, username, or email.
[`userId`](#userid) | Narrows the query by owner user ID.
[`dateCreated`](#datecreated) | Narrows the query by creation date.
[`dateUpdated`](#dateupdated) | Narrows the query by last-updated date.
[`fixedOrder`](#fixedorder) | Returns results in the order specified by `id`.
[`id`](#id) | Narrows the query by element ID.
[`inReverse`](#inreverse) | Reverses the result order.
[`limit`](#limit) | Limits the number of results.
[`offset`](#offset) | Skips a number of results.
[`orderBy`](#orderby) | Sets the result order.
[`title`](#title) | Narrows the query by submission title.
[`trashed`](#trashed) | Returns soft-deleted submissions.
[`uid`](#uid) | Narrows the query by UID.

### `after`
Narrows the query to submissions created on or after a date.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .after(date('2026-01-01'))
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->after(new \DateTime('2026-01-01'))
    ->all();
```
:::

### `anyStatus`
Clears Formie’s default incomplete and spam filters.

By default, submission queries only return submissions where `isIncomplete` and `isSpam` are both `false`. Use `anyStatus()` when you need to include incomplete or spam submissions in the same query.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .anyStatus()
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->anyStatus()
    ->all();
```
:::

### `asArray`
Returns arrays of data instead of [Submission](/reference/submission) objects.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .asArray()
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->asArray()
    ->all();
```
:::

### `before`
Narrows the query to submissions created before a date.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .before(date('2026-02-01'))
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->before(new \DateTime('2026-02-01'))
    ->all();
```
:::

### `dateCreated`
Narrows the query by the submissions’ creation dates.

::: code
```twig Twig
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set submissions = craft.formie.submissions()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
$start = new \DateTime('first day of last month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$submissions = \verbb\formie\elements\Submission::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::

### `dateUpdated`
Narrows the query by the submissions’ last-updated dates.

::: code
```twig Twig
{% set lastWeek = date('1 week ago')|atom %}

{% set submissions = craft.formie.submissions()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$submissions = \verbb\formie\elements\Submission::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::

### `field`
Narrows the query by a Formie field value.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .form('contactForm')
    .field('emailAddress', 'jane@example.com')
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->form('contactForm')
    ->field('emailAddress', 'jane@example.com')
    ->all();
```
:::

You can also call the field handle directly when you know it ahead of time.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .form('contactForm')
    .emailAddress('jane@example.com')
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->form('contactForm')
    ->emailAddress('jane@example.com')
    ->all();
```
:::

### `fixedOrder`
Returns results in the same order as the IDs passed to `id`.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .id([3, 1, 2])
    .fixedOrder()
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->id([3, 1, 2])
    ->fixedOrder()
    ->all();
```
:::

### `form`
Narrows the query by form handle or [Form](/reference/form) object.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .form('contactForm')
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->form('contactForm')
    ->all();
```
:::

### `formId`
Narrows the query by form ID.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .formId(1)
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->formId(1)
    ->all();
```
:::

### `id`
Narrows the query by element ID.

::: code
```twig Twig
{% set submission = craft.formie.submissions()
    .id(1)
    .one() %}
```

```php PHP
$submission = \verbb\formie\elements\Submission::find()
    ->id(1)
    ->one();
```
:::

### `inReverse`
Reverses the result order.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .inReverse()
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->inReverse()
    ->all();
```
:::

### `isIncomplete`
Narrows the query by incomplete state.

::: code
```twig Twig
{% set incompleteSubmissions = craft.formie.submissions()
    .isIncomplete(true)
    .all() %}

{% set submissions = craft.formie.submissions()
    .isIncomplete(null)
    .all() %}
```

```php PHP
$incompleteSubmissions = \verbb\formie\elements\Submission::find()
    ->isIncomplete(true)
    ->all();

$submissions = \verbb\formie\elements\Submission::find()
    ->isIncomplete(null)
    ->all();
```
:::

### `isSpam`
Narrows the query by spam state.

::: code
```twig Twig
{% set spamSubmissions = craft.formie.submissions()
    .isSpam(true)
    .all() %}

{% set submissions = craft.formie.submissions()
    .isSpam(null)
    .all() %}
```

```php PHP
$spamSubmissions = \verbb\formie\elements\Submission::find()
    ->isSpam(true)
    ->all();

$submissions = \verbb\formie\elements\Submission::find()
    ->isSpam(null)
    ->all();
```
:::

### `limit`
Limits the number of submissions returned.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .limit(10)
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->limit(10)
    ->all();
```
:::

### `offset`
Skips a number of results.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .offset(3)
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->offset(3)
    ->all();
```
:::

### `orderBy`
Sets the result order.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::

### `status`
Narrows the query by submission status handle.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .status('approved')
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->status('approved')
    ->all();
```
:::

### `statusId`
Narrows the query by submission status ID.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .statusId(1)
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->statusId(1)
    ->all();
```
:::

### `title`
Narrows the query by submission title.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .title('*Jane*')
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->title('*Jane*')
    ->all();
```
:::

### `trashed`
Returns submissions that have been soft-deleted.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .trashed()
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->trashed()
    ->all();
```
:::

### `uid`
Narrows the query by UID.

::: code
```twig Twig
{% set submission = craft.formie.submissions()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
$submission = \verbb\formie\elements\Submission::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::

### `user`
Narrows the query by owner user object, username, or email.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .user(currentUser)
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->user($currentUser)
    ->all();
```
:::

### `userId`
Narrows the query by owner user ID.

::: code
```twig Twig
{% set submissions = craft.formie.submissions()
    .userId(currentUser.id)
    .all() %}
```

```php PHP
$submissions = \verbb\formie\elements\Submission::find()
    ->userId($currentUser->id)
    ->all();
```
:::
