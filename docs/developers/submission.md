# Submission
Whenever you're dealing with a submission in your template, you're actually working with a `Submission` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the submission.
:::

::: reference
### `owner`

**Type:** `craft\elements\Entry|null`

The [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) this submission was made on.
:::

::: reference
### `ownerId`

**Type:** `int|null`

The [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) ID this submission was made on.
:::

::: reference
### `ownerSiteId`

**Type:** `int|null`

The site ID for the entry this submission was made on.
:::

::: reference
### `isComplete`

**Type:** `bool|null`

Whether the submission is considered complete. This can be done only through approving or revoking a submission.
:::

::: reference
### `isPending`

**Type:** `bool|null`

Whether the submission is considered pending (a submission is currently waiting for approval/review).
:::

::: reference
### `dateCreated`

**Type:** `DateTime|null`

The date this submission was submitted by an editor.
:::


## Methods

::: reference
### `getReviews()`

**Returns:** `array`

Returns a collection of [Review](docs:developers/review) objects.
:::

::: reference
### `getLastReview()`

**Returns:** `verbb\workflow\models\Review|null`

Returns the most recent (last-submitted) [Review](docs:developers/review).
:::



In additon, there are several other attributes and functions that are set at the submission level, but they in fact target the last (current) [Review](docs:developers/review). They are available here for convenience.

::: reference
### `editor`

**Type:** `craft\elements\User|null`

The user as the editor role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
:::

::: reference
### `editorId`

**Type:** `int|null`

The ID of the user as the editor role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
:::

::: reference
### `reviewer`

**Type:** `craft\elements\User|null`

The user as the reviewer role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
:::

::: reference
### `reviewerId`

**Type:** `int|null`

The ID of the ruser as the eviewer role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
:::

::: reference
### `publisher`

**Type:** `craft\elements\User|null`

The user as the publisher role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
:::

::: reference
### `publisherId`

**Type:** `int|null`

The ID of the user as the publisher role, if applicable to the most recent (last-submitted) [Review](docs:developers/review).
:::

::: reference
### `notes`

**Type:** `string|null`

The notes of this most recent (last-submitted) [Review](docs:developers/review).
:::

::: reference
### `status`

**Type:** `string|null`

The status of this most recent (last-submitted) [Review](docs:developers/review).
:::

::: reference
### `role`

**Type:** `string|null`

The role of this most recent (last-submitted) [Review](docs:developers/review).
:::
