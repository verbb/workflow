# Interfaces & Types

Workflow adds one interface and one type to Craft’s GraphQL schema. Every result of the [`workflowSubmissions` query](docs:graphql/queries) implements `SubmissionInterface`, and its concrete type is `Submission`.

## Submission Interface

### The `SubmissionInterface` Interface

The interface implemented by all submissions. It includes Craft’s common element fields, described in Craft’s [GraphQL API](https://craftcms.com/docs/5.x/development/graphql.html) docs, along with the fields below.

::: reference
#### `id`

**Type:** `ID`

The ID of the submission.
:::

::: reference
#### `uid`

**Type:** `String`

The UID of the submission.
:::

::: reference
#### `status`

**Type:** `String`

The submission’s status, taken from its most recent review. This is `pending`, `approved`, `rejected` or `revoked`.
:::

::: reference
#### `enabled`

**Type:** `Boolean`

Whether the submission is enabled.
:::

::: reference
#### `siteId`

**Type:** `Int`

The ID of the site the submission is associated with.
:::

::: reference
#### `siteHandle`

**Type:** `String`

The handle of the site the submission is associated with.
:::

::: reference
#### `dateCreated`

**Type:** `DateTime`

The date the submission was created, which is when the entry was first submitted for review.
:::

::: reference
#### `dateUpdated`

**Type:** `DateTime`

The date the submission was last updated.
:::

::: reference
#### `ownerId`

**Type:** `Int`

The ID of the entry the submission was made on.
:::

::: reference
#### `owner`

**Type:** `ElementInterface`

The entry the submission was made on. This is `null` when the schema can’t read that entry, such as when its section isn’t enabled in the schema. Use an inline fragment for the entry type to request its custom fields.

```graphql
{
  workflowSubmissions(limit: 1) {
    owner {
      title
      url
      ... on news_Entry {
        summary
      }
    }
  }
}
```

```json
{
  "data": {
    "workflowSubmissions": [
      {
        "owner": {
          "title": "Spring Menu Launch",
          "url": "https://example.com/news/spring-menu-launch",
          "summary": "Our new seasonal menu starts on the first of September."
        }
      }
    ]
  }
}
```
:::

## Submission Type

### The `Submission` Type

The concrete type for every submission. It implements `SubmissionInterface`, so it has all of the fields above. You don’t usually need to name it, but you can use it in a fragment.

```graphql
fragment SubmissionSummary on Submission {
  id
  status
  dateCreated
  ownerId
}

{
  workflowSubmissions(limit: 2) {
    ...SubmissionSummary
  }
}
```

```json
{
  "data": {
    "workflowSubmissions": [
      {
        "id": "5673",
        "status": "pending",
        "dateCreated": "2026-11-10T09:12:44+10:00",
        "ownerId": 5602
      },
      {
        "id": "5671",
        "status": "approved",
        "dateCreated": "2026-11-09T14:03:27+10:00",
        "ownerId": 5588
      }
    ]
  }
}
```
