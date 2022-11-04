# GraphQL
Workflow supports accessing [Submission](docs:developers/submission) objects via GraphQL. Be sure to read about [Craft's GraphQL support](https://craftcms.com/docs/4.x/graphql.html).

## Submissions

### Example

:::code
```graphql GraphQL
{
    workflowSubmissions (status: "pending") {
        id
        status

        owner {
            title
        }
    }
}
```

```json JSON Response
{
    "data": {
        "workflowSubmissions": [
            {
                "id": "5673",
                "title": "pending",
                "owner": {
                    "title": "My First Blog Post"
                }
            }
        ]
    }
}
```
:::

### The `workflowSubmissions` query
This query is used to query for submissions.

| Argument | Type | Description
| - | - | -
| `id`| `[QueryArgument]` | Narrows the query results based on the elements’ IDs.
| `uid`| `[String]` | Narrows the query results based on the elements’ UIDs.
| `status`| `[String]` | Narrows the query results based on the elements’ statuses.
| `archived`| `Boolean` | Narrows the query results to only elements that have been archived.
| `trashed`| `Boolean` | Narrows the query results to only elements that have been soft-deleted.
| `site`| `[String]` | Determines which site(s) the elements should be queried in. Defaults to the current (requested) site.
| `siteId`| `String` | Determines which site(s) the elements should be queried in. Defaults to the current (requested) site.
| `unique`| `Boolean` | Determines whether only elements with unique IDs should be returned by the query.
| `enabledForSite`| `Boolean` | Narrows the query results based on whether the elements are enabled in the site they’re being queried in, per the `site` argument.
| `title`| `[String]` | Narrows the query results based on the elements’ titles.
| `search`| `String` | Narrows the query results to only elements that match a search query.
| `relatedTo`| `[Int]` | Narrows the query results to elements that relate to *any* of the provided element IDs. This argument is ignored, if `relatedToAll` is also used.
| `relatedToAll`| `[Int]` | Narrows the query results to elements that relate to *all* the provided element IDs. Using this argument will cause `relatedTo` argument to be ignored.
| `ref`| `[String]` | Narrows the query results based on a reference string.
| `fixedOrder`| `Boolean` | Causes the query results to be returned in the order specified by the `id` argument.
| `inReverse`| `Boolean` | Causes the query results to be returned in reverse order.
| `dateCreated`| `[String]` | Narrows the query results based on the elements’ creation dates.
| `dateUpdated`| `[String]` | Narrows the query results based on the elements’ last-updated dates.
| `offset`| `Int` | Sets the offset for paginated results.
| `limit`| `Int` | Sets the limit for paginated results.
| `orderBy`| `String` | Sets the field the returned elements should be ordered by
| `ownerId`| `[QueryArgument]` | Narrows the query results based on the owner element the submission was made on, per the owners’ IDs.

### The `SubmissionInterface` interface
This is the interface implemented by all submissions.

| Field | Type | Description
| - | - | -
| `id`| `ID` | The id of the entity
| `uid`| `String` | The uid of the entity
| `_count`| `Int` | Return a number of related elements for a field.
| `enabled`| `Boolean` | Whether the element is enabled or not.
| `archived`| `Boolean` | Whether the element is archived or not.
| `siteId`| `Int` | The ID of the site the element is associated with.
| `searchScore`| `String` | The element’s search score, if the `search` parameter was used when querying for the element.
| `trashed`| `Boolean` | Whether the element has been soft-deleted or not.
| `status`| `String` | The element's status.
| `dateCreated`| `DateTime` | The date the element was created.
| `dateUpdated`| `DateTime` | The date the element was last updated.
| `ownerId`| `Int` | The ID of the element that the submission relates to.
| `owner`| `ElementInterface` | The element that the submission relates to.
