# Submission Queries

You can fetch submissions in your templates or PHP code using **submission queries**.

::: code
```twig Twig
{# Create a new submission query #}
{% set myQuery = craft.workflow.submissions() %}
```

```php PHP
// Create a new submission query
$myQuery = \verbb\workflow\elements\Submission::find();
```
:::

Once you’ve created a submission query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Submission](docs:reference/submission) objects will be returned.

::: tip
See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn about how element queries work.
:::

## Example

We can display the pending submissions for an entry by doing the following:

1. Create a submission query with `craft.workflow.submissions()`.
2. Set the [ownerId](#ownerid), [status](#status) and [limit](#limit) parameters on it.
3. Fetch the submissions with `.all()`.
4. Loop through the submissions using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output their contents.

```twig
{# Create a submission query with the 'ownerId', 'status' and 'limit' parameters #}
{% set submissionsQuery = craft.workflow.submissions()
    .ownerId(entry.id)
    .status('pending')
    .limit(10) %}

{# Fetch the submissions #}
{% set submissions = submissionsQuery.all() %}

{# Display their contents #}
{% for submission in submissions %}
    <p>{{ submission.owner.title }} was submitted by {{ submission.editor.fullName ?? 'an unknown editor' }}.</p>
{% endfor %}
```

A submission’s status comes from its most recent review, so `.status('pending')` returns submissions that are still waiting for a reviewer or publisher.

## Parameters

Submission queries support the following parameters.

::: reference
### `asArray`

Causes the query to return matching submissions as arrays of data, rather than [Submission](docs:reference/submission) objects.

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
:::

::: reference
### `dateCreated`

Narrows the query results based on the submissions’ creation dates.

| Value | Fetches submissions…
| - | -
| `'>= 2026-04-01'` | that were created on or after 2026-04-01.
| `'< 2026-05-01'` | that were created before 2026-05-01.
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were created between 2026-04-01 and 2026-05-01.

::: code
```twig Twig
{# Fetch submissions created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set submissions = craft.workflow.submissions()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch submissions created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$submissions = \verbb\workflow\elements\Submission::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the submissions’ last-updated dates.

| Value | Fetches submissions…
| - | -
| `'>= 2026-04-01'` | that were updated on or after 2026-04-01.
| `'< 2026-05-01'` | that were updated before 2026-05-01.
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were updated between 2026-04-01 and 2026-05-01.

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
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$submissions = \verbb\workflow\elements\Submission::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `editorId`

Narrows the query results to submissions whose latest editor review was made by a user, per their ID. The editor is the person who submitted the entry for review.

| Value | Fetches submissions…
| - | -
| `1` | submitted by the user with an ID of 1.
| `[1, 2]` | submitted by the user with an ID of 1 or 2.

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
:::

::: reference
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
:::

::: reference
### `id`

Narrows the query results based on the submissions’ IDs.

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

This can be combined with [fixedOrder](#fixedorder) if you want the results to be returned in a specific order.
:::

::: reference
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
:::

::: reference
### `isComplete`

Narrows the query results based on whether the submission has finished. A submission is complete once a publisher approves it or the editor revokes it.

| Value | Fetches submissions…
| - | -
| `true` | that have been approved or revoked.
| `false` | that are still in progress, including ones sent back to the editor.

::: code
```twig Twig
{# Fetch submissions that are still in progress #}
{% set submissions = craft.workflow.submissions()
    .isComplete(false)
    .all() %}
```

```php PHP
// Fetch submissions that are still in progress
$submissions = \verbb\workflow\elements\Submission::find()
    ->isComplete(false)
    ->all();
```
:::
:::

::: reference
### `isPending`

Narrows the query results based on whether the submission is waiting for a reviewer or publisher to act on it. A rejected submission stops being pending until the editor submits it again.

| Value | Fetches submissions…
| - | -
| `true` | that are waiting for review or approval.
| `false` | that aren’t waiting on anyone.

::: code
```twig Twig
{# Fetch submissions waiting for review #}
{% set submissions = craft.workflow.submissions()
    .isPending(true)
    .all() %}
```

```php PHP
// Fetch submissions waiting for review
$submissions = \verbb\workflow\elements\Submission::find()
    ->isPending(true)
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of submissions that should be returned.

::: code
```twig Twig
{# Fetch up to 10 submissions #}
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
:::

::: reference
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
:::

::: reference
### `orderBy`

Determines the order that the submissions should be returned in. As well as the usual element columns, you can sort by `lastReviewDate`, the date of each submission’s most recent review.

::: code
```twig Twig
{# Fetch submissions with the most recently reviewed first #}
{% set submissions = craft.workflow.submissions()
    .orderBy('lastReviewDate desc')
    .all() %}
```

```php PHP
// Fetch submissions with the most recently reviewed first
$submissions = \verbb\workflow\elements\Submission::find()
    ->orderBy('lastReviewDate desc')
    ->all();
```
:::
:::

::: reference
### `ownerDraftId`

Narrows the query results to submissions whose most recent review was made on a particular draft of the entry, per the draft’s ID.

::: code
```twig Twig
{# Fetch submissions for the draft being edited #}
{% set submissions = craft.workflow.submissions()
    .ownerId(entry.canonicalId)
    .ownerDraftId(entry.draftId)
    .all() %}
```

```php PHP
// Fetch submissions for the draft being edited
$submissions = \verbb\workflow\elements\Submission::find()
    ->ownerId($entry->getCanonicalId())
    ->ownerDraftId($entry->draftId)
    ->all();
```
:::
:::

::: reference
### `ownerId`

Narrows the query results based on the entry the submission was made on, per the entry’s ID.

| Value | Fetches submissions…
| - | -
| `1` | made on the entry with an ID of 1.
| `'not 1'` | not made on the entry with an ID of 1.
| `[1, 2]` | made on the entry with an ID of 1 or 2.
| `['not', 1, 2]` | not made on the entry with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch submissions made on this entry #}
{% set submissions = craft.workflow.submissions()
    .ownerId(entry.id)
    .all() %}
```

```php PHP
// Fetch submissions made on this entry
$submissions = \verbb\workflow\elements\Submission::find()
    ->ownerId($entry->id)
    ->all();
```
:::
:::

::: reference
### `ownerSiteId`

Narrows the query results based on the site the entry was submitted from, per the site’s ID.

::: code
```twig Twig
{# Fetch submissions made on this entry in the current site #}
{% set submissions = craft.workflow.submissions()
    .ownerId(entry.id)
    .ownerSiteId(currentSite.id)
    .all() %}
```

```php PHP
// Fetch submissions made on this entry in the current site
$submissions = \verbb\workflow\elements\Submission::find()
    ->ownerId($entry->id)
    ->ownerSiteId(Craft::$app->getSites()->getCurrentSite()->id)
    ->all();
```
:::
:::

::: reference
### `publisherId`

Narrows the query results to submissions whose latest publisher review was made by a user, per their ID.

| Value | Fetches submissions…
| - | -
| `1` | approved or rejected by the user with an ID of 1.
| `[1, 2]` | approved or rejected by the user with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch submissions the current user has acted on as a publisher #}
{% set submissions = craft.workflow.submissions()
    .publisherId(currentUser.id)
    .all() %}
```

```php PHP
// Fetch submissions the current user has acted on as a publisher
$user = Craft::$app->getUser()->getIdentity();

$submissions = \verbb\workflow\elements\Submission::find()
    ->publisherId($user->id)
    ->all();
```
:::
:::

::: reference
### `reviewerId`

Narrows the query results to submissions whose latest reviewer review was made by a user, per their ID.

| Value | Fetches submissions…
| - | -
| `1` | reviewed by the user with an ID of 1.
| `[1, 2]` | reviewed by the user with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch submissions the current user has reviewed #}
{% set submissions = craft.workflow.submissions()
    .reviewerId(currentUser.id)
    .all() %}
```

```php PHP
// Fetch submissions the current user has reviewed
$user = Craft::$app->getUser()->getIdentity();

$submissions = \verbb\workflow\elements\Submission::find()
    ->reviewerId($user->id)
    ->all();
```
:::
:::

::: reference
### `role`

Narrows the query results based on the role of whoever made the submission’s most recent review.

| Value | Fetches submissions…
| - | -
| `'editor'` | last acted on by an editor, such as when it was submitted or revoked.
| `'reviewer'` | last acted on by a reviewer.
| `'publisher'` | last acted on by a publisher.

::: code
```twig Twig
{# Fetch submissions a reviewer has approved or rejected most recently #}
{% set submissions = craft.workflow.submissions()
    .role('reviewer')
    .all() %}
```

```php PHP
// Fetch submissions a reviewer has approved or rejected most recently
$submissions = \verbb\workflow\elements\Submission::find()
    ->role('reviewer')
    ->all();
```
:::
:::

::: reference
### `siteId`

Determines which site the submissions should be queried in, per the site’s ID. Defaults to the current site.

::: code
```twig Twig
{# Fetch submissions from the site with an ID of 2 #}
{% set submissions = craft.workflow.submissions()
    .siteId(2)
    .all() %}
```

```php PHP
// Fetch submissions from the site with an ID of 2
$submissions = \verbb\workflow\elements\Submission::find()
    ->siteId(2)
    ->all();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the submissions’ statuses, which come from each submission’s most recent review. Pass `null` to fetch submissions regardless of status.

| Value | Fetches submissions…
| - | -
| `'pending'` | that are waiting for review or approval.
| `'approved'` | that are approved.
| `'rejected'` | that are rejected.
| `'revoked'` | that are revoked.
| `['revoked', 'rejected']` | that are revoked or rejected.
| `null` | in any status.

::: code
```twig Twig
{# Fetch approved submissions #}
{% set submissions = craft.workflow.submissions()
    .status('approved')
    .all() %}

{# Fetch all submissions, regardless of status #}
{% set submissions = craft.workflow.submissions()
    .status(null)
    .all() %}
```

```php PHP
// Fetch approved submissions
$submissions = \verbb\workflow\elements\Submission::find()
    ->status('approved')
    ->all();

// Fetch all submissions, regardless of status
$submissions = \verbb\workflow\elements\Submission::find()
    ->status(null)
    ->all();
```
:::
:::

::: reference
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
:::
