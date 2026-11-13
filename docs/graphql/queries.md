# Queries

Workflow supports fetching [Submission](docs:reference/submission) elements through Craft’s GraphQL API, so a headless front end or an external dashboard can show which entries are waiting for review. Read Craft’s [GraphQL API](https://craftcms.com/docs/5.x/development/graphql.html) docs first if you haven’t set up a GraphQL endpoint before.

## Enabling Submissions in a Schema

Submissions aren’t available to a GraphQL schema until you allow them. Go to **GraphQL** → **Schemas** in the control panel, edit the schema your requests use (or the **Public Schema**), and enable **View submissions** under the **Workflow** heading. This adds the `workflowSubmissions` query.

A submission is only returned when the schema can also read the entry it was made on. Enable the sections those entries belong to, and the sites they were submitted in, in the same schema. A submission for an entry in a section the schema can’t see is left out of the results, and its `owner` field returns `null`.

Two more schema settings change what you can fetch.

- **Query for element drafts** lets the schema return submissions made on drafts. Most submissions are made on a draft, so enable this unless you only need submissions for live entries.
- **Query for non-enabled elements** adds the `status`, `archived` and `trashed` arguments, and returns submissions for disabled entries. Without it, you can’t filter submissions by status in GraphQL.

## Querying Submissions

::: reference
### The `workflowSubmissions` Query

Returns a list of submissions as `SubmissionInterface` objects. Use it to list the entries waiting for review, or the history of submissions for a particular entry.

| Argument | Type | Description
| - | - | -
| `id` | `[QueryArgument]` | Narrows the query results based on the submissions’ IDs.
| `uid` | `[String]` | Narrows the query results based on the submissions’ UIDs.
| `ownerId` | `[QueryArgument]` | Narrows the query results based on the entry the submission was made on, per the entry’s ID.
| `site` | `[String]` | Determines which sites the submissions should be queried in, per the site handles. Defaults to the current site.
| `siteId` | `[QueryArgument]` | Determines which sites the submissions should be queried in, per the site IDs. Defaults to the current site.
| `status` | `[String]` | Narrows the query results based on the submissions’ statuses, which are `pending`, `approved`, `rejected` or `revoked`. Needs **Query for non-enabled elements**.
| `dateCreated` | `[String]` | Narrows the query results based on the submissions’ creation dates.
| `dateUpdated` | `[String]` | Narrows the query results based on the submissions’ last-updated dates.
| `fixedOrder` | `Boolean` | Causes the query results to be returned in the order specified by the `id` argument.
| `inReverse` | `Boolean` | Causes the query results to be returned in reverse order.
| `limit` | `Int` | Sets the limit for paginated results.
| `offset` | `Int` | Sets the offset for paginated results.
| `orderBy` | `String` | Sets the field the returned submissions should be ordered by, such as `dateCreated DESC`.

Craft’s other element arguments, such as `unique`, `preferSites`, `search` and `relatedTo`, are available too. See [Submission Queries](docs:getting-elements/submission-queries) for how the matching query parameters behave. The submission-specific parameters such as `editorId`, `role` and `isPending` are available in Twig and PHP but not in GraphQL.

This query fetches the pending submissions, with the title of each submitted entry.

```graphql
{
  workflowSubmissions(status: "pending", orderBy: "dateCreated DESC") {
    id
    status
    dateCreated @formatDateTime(format: "Y-m-d")
    owner {
      id
      title
    }
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
        "dateCreated": "2026-11-10",
        "owner": {
          "id": "5602",
          "title": "Spring Menu Launch"
        }
      },
      {
        "id": "5671",
        "status": "pending",
        "dateCreated": "2026-11-09",
        "owner": {
          "id": "5588",
          "title": "Opening Hours Over the Holidays"
        }
      }
    ]
  }
}
```

To show the submission history for one entry, pass its ID as `ownerId`. A variable keeps the query reusable.

```graphql
query EntrySubmissions($entryId: [QueryArgument]) {
  workflowSubmissions(ownerId: $entryId, orderBy: "dateCreated DESC") {
    id
    status
    dateCreated
    dateUpdated
  }
}
```

```json
{
  "entryId": 5602
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
        "dateUpdated": "2026-11-10T09:12:44+10:00"
      },
      {
        "id": "5640",
        "status": "rejected",
        "dateCreated": "2026-11-03T15:30:02+10:00",
        "dateUpdated": "2026-11-04T08:41:19+10:00"
      }
    ]
  }
}
```

When the owner is a particular entry type, use an inline fragment to request its custom fields.

```graphql
{
  workflowSubmissions(limit: 5) {
    id
    status
    owner {
      title
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
        "id": "5673",
        "status": "pending",
        "owner": {
          "title": "Spring Menu Launch",
          "summary": "Our new seasonal menu starts on the first of September."
        }
      }
    ]
  }
}
```
:::
