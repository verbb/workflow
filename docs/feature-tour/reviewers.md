# Reviewers
Reviewers exists as an optional middle-step in the editor-publisher approval workflow. This provides a means to have multiple reviewing parties, before finally approving and publishing a change.

To visualise this, let's take an example hierarchy:
- Junior Editor
- Senior Editor
- Managing Editor

In this scenario, an entry might start at the Junior Editor, before going to the Senior Editor for review and approval, which then must go on to the Managing Editor for final sign-off and approval. The Senior Editor can approve or deny a submission without having to go to the Managing Editor.

:::tip
Workflow also allows for multiple reviewers - so if you need more than one middle level of reviewers, you can add as many as required.
:::

To add Reviewers, use the Workflow settings to add user groups as reviewer levels. This will be similar to how you would have nominated user groups for Publishers and Editors.

The overall review process will be top-down. So for example, you might have the following:

![](/docs/screenshots/review-pane5.png)

Where there are two Reviewer groups - Reviewers 1 and Reviewers 2. Submissions will be placed by users in the Editors' user group, and submitted to the Reviewers 1 users group. After successful review and approval, this will be submitted to the Reviewers 2 user group. After successful review and approval, this will then be submitted to the Publishers' user group for final review.

Visually, this process would look similar to:

- "Editor" creates and submits entry
- "Reviewers 1" receives & reviews submission
  - If rejected, back to "Editor"
- "Reviewers 2" receives & reviews submission
  - If rejected, back to "Reviewers 1"
- "Publisher" receives & reviews submission
  - If rejected, back to "Reviewers 2"
  - If approved, publishes entry
