# Front-end Submission
Workflow submissions can also be triggered for entries created through the front-end. You'll need to adjust your templates to include a special tag that tells Workflow to check the incoming entry and send it as a submission to be approved.

```twig
<form method="post" accept-charset="UTF-8">
    <input type="hidden" name="action" value="entry-revisions/save-draft">
    <input type="hidden" name="sectionId" value="1">
    <input type="hidden" name="typeId" value="2">
    <input type="hidden" name="enabled" value="0">
    {{ redirectInput('viewentry/{slug}') }}
    {{ csrfInput() }}

    {# Add our 'hook' for the entry to be submitted for review #}
    <input type="hidden" name="workflow-action" value="save-submission">

    {# Optionally, include some notes #}
    <input type="hidden" name="editorNotes" value="This is an amazing post">

    {# Enter your entry fields as normal... #}
    <input type="text" name="title" value="Some Title">
    <input type="text" name="fields[requiredField]" value="Some Text">

    <input type="submit" value="Submit">
</form>
```

When triggered by a user that's an [Editor](docs:feature-tour/editors), this entry will be created in your section (as disabled), a new draft will be created, and automatically submitted for review by the [Publisher](docs:feature-tour/publishers). If notifications are set up, publishers will receive a notification to action this submission.

There are also a number of other values you can give your `workflow-action` input, to do various tasks, should you like to build out more front-end functionality.

Value | Description
--- | ---
`revoke-submission` | For an editor to revoke their submission, only once submitted.
`approve-submission` | For a publisher to approve and publish a submission.
`reject-submission` | For a publisher to reject a submission.

For publishers, you can optionally include a notes' field in your form submission:

```twig
<input type="hidden" name="publisherNotes" value="I agree, this post is amazing">
```

For example, we can set up forms to revoke a submission, by first fetching any pending submissions in Workflow and constructing similar forms for each action.

:::tip
Workflow always deals with drafts, which is why we use the `entry-revisions/save-draft` endpoint for all actions.
:::

```twig
{# Find any pending Workflow submissions #}
{% set submissions = craft.workflow.submissions.status('pending').all() %}

{% for submission in submissions %}
    {# Get the 'owner' entry for the submission #}
    {% set entry = submission.getOwner() %}

    <form method="post" accept-charset="UTF-8">
        <input type="hidden" name="action" value="entry-revisions/save-draft">
        <input type="hidden" name="entryId" value="{{ entry.id }}">
        <input type="hidden" name="submissionId" value="{{ submission.id }}">
        {{ redirectInput('viewentry/{slug}') }}
        {{ csrfInput() }}

        <input type="hidden" name="workflow-action" value="revoke-submission">

        <input type="submit" value="Submit">
    </form>
{% endfor %}
```

Or, of course - being able to approve a submission for the publisher users, which is exactly the same form as above, with the `workflow-action` modified:

```twig
<input type="hidden" name="workflow-action" value="approve-submission">
```
