# Submission Queries
You can fetch submissions in your templates or PHP submission using **submission queries**.

:::code
```twig Twig
{# Create a new submission query #}
{% set myQuery = craft.workflow.submissions() %}
```

```php PHP
// Create a new submission query
$myQuery = \verbb\workflow\elements\Submission::find();
```
:::

Once you’ve created a submission query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Submission](docs:developers/submission) objects will be returned.

:::tip
See Introduction to [Element Queries](https://docs.craftcms.com/v3/dev/element-queries/) in the Craft docs to learn about how element queries work.
:::

## Example
We can display submissions for a given entry by doing the following:

1. Create a submission query with `craft.workflow.submissions()`.
2. Set the [ownerId](#ownerId), [status](#status) and [limit](#limit) parameters on it.
3. Fetch all submissions with `.all()` and output.
4. Loop through the submissions using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to output the contents.

```twig
{# Create a submissions query with the 'ownerId', 'status' and 'limit' parameters #}
{% set submissionsQuery = craft.workflow.submissions()
    .ownerId(entry.id)
    .status('pending')
    .limit(10) %}

{# Fetch the Submissions #}
{% set submissions = submissionsQuery.all() %}

{# Display their contents #}
{% for submission in submissions %}
    <p>{{ submission.id }}</p>
{% endfor %}
```

## Parameters
Submission queries support the following parameters:

<!-- BEGIN PARAMS -->

### `anyStatus`

Clears out the [status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) parameters.

::: code
```twig Twig
{# Fetch all submissions, regardless of status #}
{% set submissions = craft.workflow.submissions()
    .anyStatus()
    .all() %}
```

```php PHP
// Fetch all submissions, regardless of status
$submissions = \verbb\workflow\elements\Submission::find()
    ->anyStatus()
    ->all();
```
:::



### `asArray`

Causes the query to return matching submissions as arrays of data, rather than [Submission](docs:developers/submission) objects.

::: code
```twig Twig
{# Fetch submissions as arrays #}
{% set submissions = craft.workflow.submissions()
    .asArray()
    .all() %}
```

```php PHP
// Fetch submissions as arrays
$submissions = \verbb\workflow\elements\Submission::find()
    ->asArray()
    ->all();
```
:::



### `dateApproved`

Narrows the query results based on the submissions’ creation approved date.

Possible values include:

| Value | Fetches submissions…
| - | -
| `'>= 2018-04-01'` | that were approved on or after 2018-04-01.
| `'< 2018-05-01'` | that were approved before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were approved between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch submissions approved last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set submissions = craft.workflow.submissions()
    .dateApproved(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch submissions approved last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$submissions = \verbb\workflow\elements\Submission::find()
    ->dateApproved(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateCreated`

Narrows the query results based on the submissions’ creation dates.

Possible values include:

| Value | Fetches submissions…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch submissions created last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set submissions = craft.workflow.submissions()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch submissions created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$submissions = \verbb\workflow\elements\Submission::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateRejected`

Narrows the query results based on the submissions’ rejected dates.

Possible values include:

| Value | Fetches submissions…
| - | -
| `'>= 2018-04-01'` | that were rejected on or after 2018-04-01.
| `'< 2018-05-01'` | that were rejected before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were rejected between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch submissions rejected last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set submissions = craft.workflow.submissions()
    .dateRejected(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch submissions rejected last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$submissions = \verbb\workflow\elements\Submission::find()
    ->dateRejected(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateRevoked`

Narrows the query results based on the submissions’ revoked dates.

Possible values include:

| Value | Fetches submissions…
| - | -
| `'>= 2018-04-01'` | that were revoked on or after 2018-04-01.
| `'< 2018-05-01'` | that were revoked before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were revoked between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch submissions revoked last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set submissions = craft.workflow.submissions()
    .dateRevoked(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch submissions revoked last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$submissions = \verbb\workflow\elements\Submission::find()
    ->dateRevoked(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateUpdated`

Narrows the query results based on the submissions’ last-updated dates.

Possible values include:

| Value | Fetches submissions…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch submissions updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set submissions = craft.workflow.submissions()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch submissions updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$submissions = \verbb\workflow\elements\Submission::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::



### `editorId`

Narrows the query results based on the editor, per their ID.

Possible values include:

| Value | Fetches submissions…
| - | -
| `1` | with a user with an ID of 1.
| `'not 1'` | not with a user with an ID of 1.
| `[1, 2]` | with a user with an ID of 1 or 2.
| `['not', 1, 2]` | not with a user with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch the current user's submissions #}
{% set submissions = craft.workflow.submissions()
    .editorId(currentUser.id)
    .all() %}
```

```php PHP
// Fetch the current user's submissions
$user = Craft::$app->getUser()->getIdentity();

$submissions = \verbb\workflow\elements\Submission::find()
    ->editorId($user->id)
    ->all();
```
:::



### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig Twig
{# Fetch submissions in a specific order #}
{% set submissions = craft.workflow.submissions()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch submissions in a specific order
$submissions = \verbb\workflow\elements\Submission::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::



### `id`

Narrows the query results based on the submissions’ IDs.

Possible values include:

| Value | Fetches submissions…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch the submission by its ID #}
{% set submission = craft.workflow.submissions()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the submission by its ID
$submission = \verbb\workflow\elements\Submission::find()
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
{# Fetch submissions in reverse #}
{% set submissions = craft.workflow.submissions()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch submissions in reverse
$submissions = \verbb\workflow\elements\Submission::find()
    ->inReverse()
    ->all();
```
:::



### `limit`

Determines the number of submissions that should be returned.

::: code
```twig Twig
{# Fetch up to 10 submissions  #}
{% set submissions = craft.workflow.submissions()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 submissions
$submissions = \verbb\workflow\elements\Submission::find()
    ->limit(10)
    ->all();
```
:::



### `offset`

Determines how many submissions should be skipped in the results.

::: code
```twig Twig
{# Fetch all submissions except for the first 3 #}
{% set submissions = craft.workflow.submissions()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all submissions except for the first 3
$submissions = \verbb\workflow\elements\Submission::find()
    ->offset(3)
    ->all();
```
:::



### `orderBy`

Determines the order that the submissions should be returned in.

::: code
```twig Twig
{# Fetch all submissions in order of date created #}
{% set submissions = craft.workflow.submissions()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all submissions in order of date created
$submissions = \verbb\workflow\elements\Submission::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::



### `owner`

Sets the [ownerId](#ownerid) and [siteId](#siteid) parameters based on a given element.

::: code
```twig Twig
{# Fetch submissions created for this entry #}
{% set submissions = craft.workflow.submissions()
    .owner(myEntry)
    .all() %}
```

```php PHP
// Fetch submissions created for this entry
$submissions = \verbb\workflow\elements\Submission::find()
    ->owner($myEntry)
    ->all();
```
:::



### `ownerId`

Narrows the query results based on the owner element of the submissions, per the owners’ IDs.

Possible values include:

| Value | Fetches submissions…
| - | -
| `1` | created for an element with an ID of 1.
| `'not 1'` | not created for an element with an ID of 1.
| `[1, 2]` | created for an element with an ID of 1 or 2.
| `['not', 1, 2]` | not created for an element with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch submissions created for an element with an ID of 1 #}
{% set submissions = craft.workflow.submissions()
    .ownerId(1)
    .all() %}
```

```php PHP
// Fetch submissions created for an element with an ID of 1
$submissions = \verbb\workflow\elements\Submission::find()
    ->ownerId(1)
    ->all();
```
:::



### `ownerSite`

Narrows the query results based on the site the owner element was saved for.

Possible values include:

| Value | Fetches submissions…
| - | -
| `'foo'` | created for an element in a site with a handle of `foo`.
| `a [Site](https://docs.craftcms.com/api/v3/craft-models-site.html)` object | created for an element in the site represented by the object.

::: code
```twig Twig
{# Fetch submissions created for an element with an ID of 1, for a site with a handle of 'foo' #}
{% set submissions = craft.workflow.submissions()
    .ownerId(1)
    .ownerSite('foo')
    .all() %}
```

```php PHP
// Fetch submissions created for an element with an ID of 1, for a site with a handle of 'foo'
$submissions = \verbb\workflow\elements\Submission::find()
    ->ownerId(1)
    .ownerSite('foo')
    ->all();
```
:::



### `ownerSiteId`

Narrows the query results based on the site the owner element was saved for, per the site’s ID.

Possible values include:

| Value | Fetches submissions…
| - | -
| `1` | created for an element in a site with an ID of 1.
| `':empty:'` | created in a field that isn’t set to manage blocks on a per-site basis.

::: code
```twig Twig
{# Fetch submissions created for an element with an ID of 1, for a site with an ID of 2 #}
{% set submissions = craft.workflow.submissions()
    .ownerId(1)
    .ownerSiteId(2)
    .all() %}
```

```php PHP
// Fetch submissions created for an element with an ID of 1, for a site with an ID of 2
$submissions = \verbb\workflow\elements\Submission::find()
    ->ownerId(1)
    .ownerSiteId(2)
    ->all();
```
:::



### `publisherId`

Narrows the query results based on the publisher, per their ID.

Possible values include:

| Value | Fetches submissions…
| - | -
| `1` | with a user with an ID of 1.
| `'not 1'` | not with a user with an ID of 1.
| `[1, 2]` | with a user with an ID of 1 or 2.
| `['not', 1, 2]` | not with a user with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch the current user's submissions #}
{% set submissions = craft.workflow.submissions()
    .publisherId(currentUser.id)
    .all() %}
```

```php PHP
// Fetch the current user's submissions
$user = Craft::$app->getUser()->getIdentity();

$submissions = \verbb\workflow\elements\Submission::find()
    ->publisherId($user->id)
    ->all();
```
:::



### `status`

Narrows the query results based on the submissions’ statuses.

Possible values include:

| Value | Fetches submissions…
| - | -
| `'live'` _(default)_ | that are live.
| `'pending'` | that are pending (enabled with a Post Date in the future).
| `'expired'` | that are expired (enabled with an Expiry Date in the past).
| `'disabled'` | that are disabled.
| `['live', 'pending']` | that are live or pending.

::: code
```twig Twig
{# Fetch disabled submissions #}
{% set submissions = craft.workflow.submissions()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled submissions
$submissions = \verbb\workflow\elements\Submission::find()
    ->status('disabled')
    ->all();
```
:::



### `uid`

Narrows the query results based on the submissions’ UIDs.

::: code
```twig Twig
{# Fetch the submission by its UID #}
{% set submission = craft.workflow.submissions()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the submission by its UID
$submission = \verbb\workflow\elements\Submission::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::


<!-- END PARAMS -->
