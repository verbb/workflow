# Review
A Review model is created for each submission to record the state of editors, reviewers and publishers.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the review.
:::

::: reference
### `submission`

**Type:** `verbb\workflow\elements\Submission|null`

The submission this review is related to.
:::

::: reference
### `submissionId`

**Type:** `int|null`

The ID of the submission this review is related to.
:::

::: reference
### `element`

**Type:** `craft\base\ElementInterface|null`

The [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) this submission was made on.
:::

::: reference
### `elementId`

**Type:** `int|null`

The ID of the [Entry](https://docs.craftcms.com/api/v4/craft-elements-entry.html) this submission was made on.
:::

::: reference
### `draftId`

**Type:** `int|null`

The ID for the [Entry Draft](https://docs.craftcms.com/api/v4/craft-elements-entry.html) this submission was made on (if any).
:::

::: reference
### `user`

**Type:** `craft\elements\User|null`

The [User](https://docs.craftcms.com/api/v4/craft-elements-user.html) this review is related to.
:::

::: reference
### `userId`

**Type:** `int|null`

The ID of the [User](https://docs.craftcms.com/api/v4/craft-elements-user.html) this review is related to.
:::

::: reference
### `role`

**Type:** `string|null`

The Workflow "role" for this user. One of `editor`, `reviewer`, `publisher`.
:::

::: reference
### `status`

**Type:** `string|null`

The status of the review. One of `approved`, `pending`, `revoked`, `rejected`.
:::

::: reference
### `notes`

**Type:** `string|null`

Any notes left by the user.
:::

::: reference
### `dateCreated`

**Type:** `DateTime|null`

The date this review was created.
:::
