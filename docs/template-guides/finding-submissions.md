# Finding Submissions

Each time an editor sends an entry for review, Workflow creates a submission, and every approval, rejection or revocation adds a review to it. If editors work from the front end of your site, such as through a [front-end submission form](docs:template-guides/front-end-submission), they can't see the control panel's submissions list. `craft.workflow.submissions()` returns a [submission query](docs:getting-elements/submission-queries), so your templates can show editors where their entries are up to. Submission Queries lists every parameter you can add to the query.

Submissions hold editorial notes and the names of the people reviewing them, so show them only to signed-in users who should see them, such as the editor who made them.

## Listing an Editor's Submissions

This example belongs in an account template for editors, and lists the entries the signed-in user has submitted, newest first, with where each one is up to and the latest reviewer's notes. `{% requireLogin %}` sends anyone who isn't signed in to the login page.

```twig
{% requireLogin %}

{% set submissions = craft.workflow.submissions()
    .editorId(currentUser.id)
    .status(null)
    .limit(20)
    .all() %}

<h1>Your Submissions</h1>

{% for submission in submissions %}
    {% set statusLabels = {
        pending: 'Waiting for review',
        approved: 'Approved',
        rejected: 'Changes requested',
        revoked: 'Withdrawn',
    } %}

    <article>
        <h2>{{ submission.owner ? submission.owner.title : 'A deleted entry' }}</h2>
        <p>Submitted {{ submission.dateCreated | date('j F Y') }}. {{ statusLabels[submission.status] ?? submission.status }}.</p>

        {% if submission.notes %}
            <blockquote>{{ submission.notes }}</blockquote>
        {% endif %}
    </article>
{% else %}
    <p>You haven't submitted anything for review yet.</p>
{% endfor %}
```

An editor with two submissions sees each entry's title and submission date, followed by “Waiting for review” for one still with a reviewer, or “Changes requested” with the reviewer's notes for one sent back. Someone who hasn't submitted anything sees “You haven't submitted anything for review yet.”

`.editorId(currentUser.id)` finds the submissions this user made. `.status(null)` includes every submission, whatever its review status. To show only the ones still in progress, add `.isComplete(false)`, which leaves out approved and withdrawn submissions.

A submission's `status` and `notes` come from its most recent review. Its `status` is `pending`, `approved`, `rejected` or `revoked`, which the example turns into friendlier wording. `owner` is the entry the submission was made on. To show every review rather than the latest, loop over `submission.getReviews()`, as described in the [Submission](docs:reference/submission) reference.

## Showing That an Entry Is Waiting for Review

When an editor reopens an entry on a front-end editing page, it helps to say whether it's already with a reviewer, so they don't submit it twice. This example goes near the top of that page, where `entry` is the entry being edited.

```twig
{% set isAwaitingReview = craft.workflow.submissions()
    .ownerId(entry.id)
    .isPending(true)
    .exists() %}

{% if isAwaitingReview %}
    <p class="notice">This entry is waiting for review.</p>
{% endif %}
```

`.ownerId(entry.id)` finds the entry's submissions, and `.isPending(true)` keeps the ones waiting for a reviewer or publisher. `.exists()` returns `true` if there's at least one, without fetching the submissions themselves. A submission that was sent back to the editor isn't pending, so the notice disappears until they submit the entry again.
